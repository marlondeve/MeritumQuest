<?php
require_once '../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$db = getDBConnection();

switch ($method) {
    case 'GET':
        // Obtener todos los quizzes o uno específico
        if (isset($_GET['id'])) {
            $quizId = intval($_GET['id']);
            
            // Intentar desde cache
            $quiz = getQuizFromCache($quizId);
            if ($quiz === null) {
                $stmt = $db->prepare("SELECT * FROM quizzes WHERE id = ?");
                $stmt->execute([$quizId]);
                $quiz = $stmt->fetch();
                
                if ($quiz) {
                    // Cargar preguntas
                    $stmt = $db->prepare("
                        SELECT q.*
                        FROM quiz_questions q
                        WHERE q.quiz_id = ?
                        ORDER BY q.question_order
                    ");
                    $stmt->execute([$quizId]);
                    $questions = $stmt->fetchAll();
                    
                    // Cargar opciones para cada pregunta
                    foreach ($questions as &$q) {
                        $optStmt = $db->prepare("
                            SELECT id, option_order, text, is_correct
                            FROM quiz_question_options
                            WHERE question_id = ?
                            ORDER BY option_order
                        ");
                        $optStmt->execute([$q['id']]);
                        $q['options'] = $optStmt->fetchAll();
                    }
                    
                    $quiz['questions'] = $questions;
                    
                    // Cargar modos
                    $stmt = $db->prepare("SELECT * FROM quiz_modes WHERE quiz_id = ?");
                    $stmt->execute([$quizId]);
                    $quiz['modes'] = $stmt->fetchAll();
                    
                    saveQuizToCache($quizId, $quiz);
                }
            }
            
            jsonResponse($quiz ?: ['error' => 'Quiz no encontrado'], $quiz ? 200 : 404);
        } else {
            // Listar todos los quizzes
            $stmt = $db->query("SELECT id, code, title, description, points_per_question, use_time_per_question, created_at, updated_at FROM quizzes ORDER BY created_at DESC");
            $quizzes = $stmt->fetchAll();
            jsonResponse($quizzes);
        }
        break;
        
    case 'POST':
        // Crear nuevo quiz
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['title']) || empty($data['title'])) {
            jsonResponse(['error' => 'El título es requerido'], 400);
        }
        
        // Generar código único
        $code = generateUniqueCode(8);
        $stmt = $db->prepare("SELECT id FROM quizzes WHERE code = ?");
        $stmt->execute([$code]);
        while ($stmt->fetch()) {
            $code = generateUniqueCode(8);
            $stmt->execute([$code]);
        }
        
        $stmt = $db->prepare("
            INSERT INTO quizzes (code, title, description, points_per_question, use_time_per_question)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $code,
            $data['title'],
            $data['description'] ?? null,
            $data['points_per_question'] ?? 100,
            $data['use_time_per_question'] ?? 0
        ]);
        
        $quizId = $db->lastInsertId();
        
        // Crear modos si se especifican
        if (isset($data['modes'])) {
            foreach ($data['modes'] as $mode) {
                $stmt = $db->prepare("
                    INSERT INTO quiz_modes (quiz_id, mode_type, enabled, available_from, available_to, max_attempts, 
                                          show_correction_at_end, show_explanations, show_ranking, public_ranking, feedback_immediate)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $quizId,
                    $mode['mode_type'] ?? 'live',
                    $mode['enabled'] ?? 1,
                    $mode['available_from'] ?? null,
                    $mode['available_to'] ?? null,
                    $mode['max_attempts'] ?? 1,
                    $mode['show_correction_at_end'] ?? 1,
                    $mode['show_explanations'] ?? 1,
                    $mode['show_ranking'] ?? 1,
                    $mode['public_ranking'] ?? 1,
                    $mode['feedback_immediate'] ?? 0
                ]);
            }
        }
        
        jsonResponse(['id' => $quizId, 'code' => $code, 'message' => 'Quiz creado exitosamente'], 201);
        break;
        
    case 'PUT':
        // Actualizar quiz
        $data = json_decode(file_get_contents('php://input'), true);
        $quizId = intval($data['id'] ?? $_GET['id'] ?? 0);
        
        if (!$quizId) {
            jsonResponse(['error' => 'ID de quiz requerido'], 400);
        }
        
        $stmt = $db->prepare("
            UPDATE quizzes 
            SET title = ?, description = ?, points_per_question = ?, use_time_per_question = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['points_per_question'] ?? 100,
            $data['use_time_per_question'] ?? 0,
            $quizId
        ]);
        
        clearQuizCache($quizId);
        jsonResponse(['message' => 'Quiz actualizado exitosamente']);
        break;
        
    case 'DELETE':
        // Eliminar quiz
        $quizId = intval($_GET['id'] ?? 0);
        
        if (!$quizId) {
            jsonResponse(['error' => 'ID de quiz requerido'], 400);
        }
        
        $stmt = $db->prepare("DELETE FROM quizzes WHERE id = ?");
        $stmt->execute([$quizId]);
        
        clearQuizCache($quizId);
        jsonResponse(['message' => 'Quiz eliminado exitosamente']);
        break;
        
    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
?>

