<?php
require_once '../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$db = getDBConnection();

switch ($method) {
    case 'GET':
        // Obtener preguntas de un quiz
        $quizId = intval($_GET['quiz_id'] ?? 0);
        
        if (!$quizId) {
            jsonResponse(['error' => 'quiz_id requerido'], 400);
        }
        
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
            $q['allow_multiple_answers'] = (bool)$q['allow_multiple_answers'];
        }
        
        jsonResponse($questions);
        break;
        
    case 'POST':
        // Crear nueva pregunta
        $data = json_decode(file_get_contents('php://input'), true);
        $quizId = intval($data['quiz_id'] ?? 0);
        
        if (!$quizId || !isset($data['text']) || empty($data['text'])) {
            jsonResponse(['error' => 'quiz_id y text son requeridos'], 400);
        }
        
        if (!isset($data['options']) || count($data['options']) < 2) {
            jsonResponse(['error' => 'Se requieren al menos 2 opciones'], 400);
        }
        
        // Obtener el siguiente order
        $stmt = $db->prepare("SELECT COALESCE(MAX(question_order), 0) + 1 as next_order FROM quiz_questions WHERE quiz_id = ?");
        $stmt->execute([$quizId]);
        $nextOrder = $stmt->fetch()['next_order'];
        
        // Crear pregunta
        $stmt = $db->prepare("
            INSERT INTO quiz_questions (quiz_id, question_order, text, image_url, video_url, audio_url, 
                                      time_limit_sec, allow_multiple_answers, explanation)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $quizId,
            $nextOrder,
            $data['text'],
            $data['image_url'] ?? null,
            $data['video_url'] ?? null,
            $data['audio_url'] ?? null,
            $data['time_limit_sec'] ?? null,
            $data['allow_multiple_answers'] ?? 0,
            $data['explanation'] ?? null
        ]);
        
        $questionId = $db->lastInsertId();
        
        // Crear opciones
        foreach ($data['options'] as $index => $option) {
            $stmt = $db->prepare("
                INSERT INTO quiz_question_options (question_id, option_order, text, is_correct)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $questionId,
                $index + 1,
                $option['text'],
                $option['is_correct'] ?? 0
            ]);
        }
        
        clearQuizCache($quizId);
        jsonResponse(['id' => $questionId, 'message' => 'Pregunta creada exitosamente'], 201);
        break;
        
    case 'PUT':
        // Actualizar pregunta
        $data = json_decode(file_get_contents('php://input'), true);
        $questionId = intval($data['id'] ?? 0);
        
        if (!$questionId) {
            jsonResponse(['error' => 'ID de pregunta requerido'], 400);
        }
        
        // Obtener quiz_id para limpiar cache
        $stmt = $db->prepare("SELECT quiz_id FROM quiz_questions WHERE id = ?");
        $stmt->execute([$questionId]);
        $question = $stmt->fetch();
        
        if (!$question) {
            jsonResponse(['error' => 'Pregunta no encontrada'], 404);
        }
        
        $quizId = $question['quiz_id'];
        
        // Actualizar pregunta
        $stmt = $db->prepare("
            UPDATE quiz_questions 
            SET text = ?, image_url = ?, video_url = ?, audio_url = ?, 
                time_limit_sec = ?, allow_multiple_answers = ?, explanation = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['text'],
            $data['image_url'] ?? null,
            $data['video_url'] ?? null,
            $data['audio_url'] ?? null,
            $data['time_limit_sec'] ?? null,
            $data['allow_multiple_answers'] ?? 0,
            $data['explanation'] ?? null,
            $questionId
        ]);
        
        // Actualizar opciones si se proporcionan
        if (isset($data['options'])) {
            // Eliminar opciones existentes
            $stmt = $db->prepare("DELETE FROM quiz_question_options WHERE question_id = ?");
            $stmt->execute([$questionId]);
            
            // Crear nuevas opciones
            foreach ($data['options'] as $index => $option) {
                $stmt = $db->prepare("
                    INSERT INTO quiz_question_options (question_id, option_order, text, is_correct)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $questionId,
                    $index + 1,
                    $option['text'],
                    $option['is_correct'] ?? 0
                ]);
            }
        }
        
        clearQuizCache($quizId);
        jsonResponse(['message' => 'Pregunta actualizada exitosamente']);
        break;
        
    case 'DELETE':
        // Eliminar pregunta
        $questionId = intval($_GET['id'] ?? 0);
        
        if (!$questionId) {
            jsonResponse(['error' => 'ID de pregunta requerido'], 400);
        }
        
        // Obtener quiz_id para limpiar cache
        $stmt = $db->prepare("SELECT quiz_id FROM quiz_questions WHERE id = ?");
        $stmt->execute([$questionId]);
        $question = $stmt->fetch();
        
        if ($question) {
            $quizId = $question['quiz_id'];
            
            $stmt = $db->prepare("DELETE FROM quiz_questions WHERE id = ?");
            $stmt->execute([$questionId]);
            
            clearQuizCache($quizId);
            jsonResponse(['message' => 'Pregunta eliminada exitosamente']);
        } else {
            jsonResponse(['error' => 'Pregunta no encontrada'], 404);
        }
        break;
        
    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
?>

