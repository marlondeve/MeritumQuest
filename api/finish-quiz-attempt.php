<?php
require_once '../config/config.php';
$db = getDB();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$attempt_id = intval($data['attempt_id'] ?? 0);
$answers = $data['answers'] ?? [];

if (empty($attempt_id) || empty($answers)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Obtener información del intento
    $stmt = $db->prepare("SELECT qa.*, q.points_per_question FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id WHERE qa.id = ?");
    $stmt->execute([$attempt_id]);
    $attempt = $stmt->fetch();
    
    if (!$attempt) {
        echo json_encode(['success' => false, 'message' => 'Intento no encontrado']);
        exit;
    }
    
    $quiz_id = $attempt['quiz_id'];
    $points_per_question = $attempt['points_per_question'];
    $total_points = 0;
    $max_points = 0;
    
    // Procesar cada respuesta
    foreach ($answers as $question_id => $selected_option_ids) {
        // Obtener la pregunta
        $stmt = $db->prepare("SELECT * FROM quiz_questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $question = $stmt->fetch();
        
        if (!$question) continue;
        
        // Obtener todas las opciones correctas de esta pregunta
        $stmt = $db->prepare("SELECT id FROM quiz_question_options WHERE question_id = ? AND is_correct = 1");
        $stmt->execute([$question_id]);
        $correct_options = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $max_points += $points_per_question;
        
        // Verificar si todas las respuestas seleccionadas son correctas
        $allCorrect = true;
        $allSelected = count($selected_option_ids) === count($correct_options);
        
        foreach ($selected_option_ids as $option_id) {
            if (!in_array($option_id, $correct_options)) {
                $allCorrect = false;
                break;
            }
        }
        
        // Si todas las respuestas seleccionadas son correctas y se seleccionaron todas las correctas
        if ($allCorrect && $allSelected && count($correct_options) > 0) {
            $total_points += $points_per_question;
        }
        
        // Guardar cada respuesta
        foreach ($selected_option_ids as $option_id) {
            $is_correct = in_array($option_id, $correct_options) ? 1 : 0;
            $stmt = $db->prepare("INSERT INTO quiz_attempt_answers (attempt_id, question_id, option_id, is_selected, is_correct, answered_at) VALUES (?, ?, ?, 1, ?, NOW())");
            $stmt->execute([$attempt_id, $question_id, $option_id, $is_correct]);
        }
    }
    
    // Calcular porcentaje
    $percentage = $max_points > 0 ? round(($total_points / $max_points) * 100, 2) : 0;
    
    // Actualizar intento
    $stmt = $db->prepare("UPDATE quiz_attempts SET finished_at = NOW(), total_points = ?, max_points = ?, percentage = ? WHERE id = ?");
    $stmt->execute([$total_points, $max_points, $percentage, $attempt_id]);
    
    // Obtener ranking de todos los intentos de este quiz
    $stmt = $db->prepare("
        SELECT 
            qa.id,
            qa.participant_name,
            qa.total_points,
            qa.percentage,
            qa.finished_at
        FROM quiz_attempts qa
        WHERE qa.quiz_id = ? AND qa.finished_at IS NOT NULL
        ORDER BY qa.total_points DESC, qa.finished_at ASC
    ");
    $stmt->execute([$quiz_id]);
    $leaderboard = $stmt->fetchAll();
    
    // Encontrar posición del usuario actual
    $user_position = 0;
    foreach ($leaderboard as $index => $entry) {
        if ($entry['id'] == $attempt_id) {
            $user_position = $index + 1;
            break;
        }
    }
    
    // Preparar top 10 para el podio
    $top10 = array_slice($leaderboard, 0, 10);
    $leaderboard_data = array_map(function($entry) {
        return [
            'name' => $entry['participant_name'],
            'total_points' => intval($entry['total_points']),
            'percentage' => floatval($entry['percentage'])
        ];
    }, $top10);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'total_points' => $total_points,
        'max_points' => $max_points,
        'percentage' => $percentage,
        'position' => $user_position,
        'total_participants' => count($leaderboard),
        'leaderboard' => $leaderboard_data
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

