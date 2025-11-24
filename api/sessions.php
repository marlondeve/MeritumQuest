<?php
require_once '../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$db = getDBConnection();

switch ($method) {
    case 'POST':
        // Crear nueva sesión (live o workshop)
        $data = json_decode(file_get_contents('php://input'), true);
        $quizId = intval($data['quiz_id'] ?? 0);
        $modeType = $data['mode_type'] ?? 'live';
        
        if (!$quizId) {
            jsonResponse(['error' => 'quiz_id requerido'], 400);
        }
        
        // Generar código único de sesión
        $sessionCode = generateUniqueCode(10);
        $stmt = $db->prepare("SELECT id FROM quiz_sessions WHERE session_code = ?");
        $stmt->execute([$sessionCode]);
        while ($stmt->fetch()) {
            $sessionCode = generateUniqueCode(10);
            $stmt->execute([$sessionCode]);
        }
        
        $stmt = $db->prepare("
            INSERT INTO quiz_sessions (quiz_id, mode_type, session_code)
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([$quizId, $modeType, $sessionCode]);
        
        $sessionId = $db->lastInsertId();
        
        jsonResponse([
            'id' => $sessionId,
            'session_code' => $sessionCode,
            'message' => 'Sesión creada exitosamente'
        ], 201);
        break;
        
    case 'GET':
        // Obtener sesión por código
        if (isset($_GET['code'])) {
            $sessionCode = $_GET['code'];
            
            $stmt = $db->prepare("
                SELECT s.*, q.title as quiz_title, q.description as quiz_description,
                       q.points_per_question, q.use_time_per_question
                FROM quiz_sessions s
                JOIN quizzes q ON s.quiz_id = q.id
                WHERE s.session_code = ? AND s.ended_at IS NULL
            ");
            $stmt->execute([$sessionCode]);
            $session = $stmt->fetch();
            
            if ($session) {
                // Cargar preguntas
                $stmt = $db->prepare("
                    SELECT q.*
                    FROM quiz_questions q
                    WHERE q.quiz_id = ?
                    ORDER BY q.question_order
                ");
                $stmt->execute([$session['quiz_id']]);
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
                
                $session['questions'] = $questions;
                
                // Cargar modo config
                $stmt = $db->prepare("SELECT * FROM quiz_modes WHERE quiz_id = ? AND mode_type = ?");
                $stmt->execute([$session['quiz_id'], $session['mode_type']]);
                $session['mode_config'] = $stmt->fetch();
            }
            
            jsonResponse($session ?: ['error' => 'Sesión no encontrada'], $session ? 200 : 404);
        } else {
            jsonResponse(['error' => 'Código de sesión requerido'], 400);
        }
        break;
        
    case 'PUT':
        // Actualizar sesión (cerrar, etc.)
        $data = json_decode(file_get_contents('php://input'), true);
        $sessionId = intval($data['id'] ?? $_GET['id'] ?? 0);
        
        if (!$sessionId) {
            jsonResponse(['error' => 'ID de sesión requerido'], 400);
        }
        
        if (isset($data['ended_at'])) {
            $stmt = $db->prepare("UPDATE quiz_sessions SET ended_at = NOW() WHERE id = ?");
            $stmt->execute([$sessionId]);
        }
        
        jsonResponse(['message' => 'Sesión actualizada exitosamente']);
        break;
        
    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
?>

