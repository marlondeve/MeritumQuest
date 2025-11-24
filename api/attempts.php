<?php
require_once '../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$db = getDBConnection();

switch ($method) {
    case 'POST':
        // Crear nuevo intento
        $data = json_decode(file_get_contents('php://input'), true);
        $sessionId = intval($data['session_id'] ?? 0);
        $quizId = intval($data['quiz_id'] ?? 0);
        $participantName = $data['participant_name'] ?? '';
        
        if (!$sessionId || !$quizId || empty($participantName)) {
            jsonResponse(['error' => 'session_id, quiz_id y participant_name son requeridos'], 400);
        }
        
        // Verificar si existe participante o crearlo
        $stmt = $db->prepare("SELECT id FROM participants WHERE name = ? LIMIT 1");
        $stmt->execute([$participantName]);
        $participant = $stmt->fetch();
        
        $participantId = $participant ? $participant['id'] : null;
        
        // Crear intento
        $stmt = $db->prepare("
            INSERT INTO quiz_attempts (session_id, quiz_id, participant_id, participant_name)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$sessionId, $quizId, $participantId, $participantName]);
        $attemptId = $db->lastInsertId();
        
        jsonResponse(['id' => $attemptId, 'message' => 'Intento creado exitosamente'], 201);
        break;
        
    case 'PUT':
        // Finalizar intento y guardar respuestas
        $data = json_decode(file_get_contents('php://input'), true);
        $attemptId = intval($data['attempt_id'] ?? 0);
        
        if (!$attemptId) {
            jsonResponse(['error' => 'attempt_id requerido'], 400);
        }
        
        // Obtener información del intento
        $stmt = $db->prepare("
            SELECT a.*, q.points_per_question, q.use_time_per_question
            FROM quiz_attempts a
            JOIN quizzes q ON a.quiz_id = q.id
            WHERE a.id = ?
        ");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch();
        
        if (!$attempt) {
            jsonResponse(['error' => 'Intento no encontrado'], 404);
        }
        
        // Guardar respuestas
        if (isset($data['answers']) && is_array($data['answers'])) {
            $totalPoints = 0;
            $maxPoints = 0;
            $correctAnswers = 0;
            $totalQuestions = 0;
            
            foreach ($data['answers'] as $answer) {
                $questionId = intval($answer['question_id']);
                $optionIds = is_array($answer['option_ids']) ? $answer['option_ids'] : [$answer['option_ids']];
                
                // Obtener pregunta y opciones correctas
                $stmt = $db->prepare("
                    SELECT q.id, q.allow_multiple_answers,
                           GROUP_CONCAT(o.id) as correct_option_ids
                    FROM quiz_questions q
                    LEFT JOIN quiz_question_options o ON q.id = o.question_id AND o.is_correct = 1
                    WHERE q.id = ?
                    GROUP BY q.id
                ");
                $stmt->execute([$questionId]);
                $question = $stmt->fetch();
                
                $totalQuestions++;
                $maxPoints += $attempt['points_per_question'];
                
                // Verificar si la respuesta es correcta
                $isCorrect = false;
                if ($question) {
                    $correctIds = $question['correct_option_ids'] ? explode(',', $question['correct_option_ids']) : [];
                    sort($correctIds);
                    sort($optionIds);
                    
                    if ($question['allow_multiple_answers']) {
                        $isCorrect = (count($correctIds) === count($optionIds) && 
                                     empty(array_diff($correctIds, $optionIds)));
                    } else {
                        $isCorrect = (count($optionIds) === 1 && in_array($optionIds[0], $correctIds));
                    }
                }
                
                if ($isCorrect) {
                    $correctAnswers++;
                    $totalPoints += $attempt['points_per_question'];
                }
                
                // Guardar cada respuesta
                foreach ($optionIds as $optionId) {
                    $stmt = $db->prepare("
                        INSERT INTO quiz_attempt_answers (attempt_id, question_id, option_id, is_selected, is_correct, answered_at)
                        VALUES (?, ?, ?, 1, ?, NOW())
                    ");
                    $stmt->execute([$attemptId, $questionId, $optionId, $isCorrect ? 1 : 0]);
                }
            }
            
            // Calcular porcentaje
            $percentage = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
            
            // Actualizar intento
            $stmt = $db->prepare("
                UPDATE quiz_attempts 
                SET finished_at = NOW(), total_points = ?, max_points = ?, percentage = ?
                WHERE id = ?
            ");
            $stmt->execute([$totalPoints, $maxPoints, $percentage, $attemptId]);
            
            jsonResponse([
                'message' => 'Intento finalizado exitosamente',
                'total_points' => $totalPoints,
                'max_points' => $maxPoints,
                'percentage' => round($percentage, 2),
                'correct_answers' => $correctAnswers,
                'total_questions' => $totalQuestions
            ]);
        } else {
            jsonResponse(['error' => 'Respuestas requeridas'], 400);
        }
        break;
        
    case 'GET':
        // Obtener ranking o resultados
        if (isset($_GET['session_id'])) {
            $sessionId = intval($_GET['session_id']);
            
            $stmt = $db->prepare("
                SELECT a.id, a.participant_name, a.total_points, a.max_points, a.percentage,
                       a.started_at, a.finished_at,
                       TIMESTAMPDIFF(SECOND, a.started_at, a.finished_at) as duration_seconds
                FROM quiz_attempts a
                WHERE a.session_id = ?
                ORDER BY a.total_points DESC, a.finished_at ASC
            ");
            $stmt->execute([$sessionId]);
            $ranking = $stmt->fetchAll();
            
            // Agregar posición
            $position = 1;
            foreach ($ranking as &$entry) {
                $entry['position'] = $position++;
            }
            
            jsonResponse($ranking);
        } elseif (isset($_GET['quiz_id'])) {
            // Ranking general del quiz
            $quizId = intval($_GET['quiz_id']);
            
            $stmt = $db->prepare("
                SELECT a.id, a.participant_name, a.total_points, a.max_points, a.percentage,
                       a.started_at, a.finished_at,
                       TIMESTAMPDIFF(SECOND, a.started_at, a.finished_at) as duration_seconds
                FROM quiz_attempts a
                WHERE a.quiz_id = ?
                ORDER BY a.total_points DESC, a.finished_at ASC
            ");
            $stmt->execute([$quizId]);
            $ranking = $stmt->fetchAll();
            
            $position = 1;
            foreach ($ranking as &$entry) {
                $entry['position'] = $position++;
            }
            
            jsonResponse($ranking);
        } else {
            jsonResponse(['error' => 'session_id o quiz_id requerido'], 400);
        }
        break;
        
    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
?>


