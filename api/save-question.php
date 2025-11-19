<?php
require_once '../config/config.php';
requireAuth();

header('Content-Type: application/json');

$current_user = getCurrentUser();
$is_admin = isAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$question_id = intval($_POST['question_id'] ?? 0);
$quiz_id = intval($_POST['quiz_id'] ?? 0);
$text = sanitize($_POST['text'] ?? '');
$allow_multiple_answers = isset($_POST['allow_multiple_answers']) ? 1 : 0;
$options = json_decode($_POST['options'] ?? '[]', true);

if (empty($text) || $quiz_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Texto de la pregunta y quiz son requeridos']);
    exit;
}

// Verificar permisos sobre el quiz
$quizQuery = $is_admin 
    ? "SELECT id FROM quizzes WHERE id = ?"
    : "SELECT id FROM quizzes WHERE id = ? AND created_by = ?";
$quizStmt = $db->prepare($quizQuery);
if ($is_admin) {
    $quizStmt->execute([$quiz_id]);
} else {
    $quizStmt->execute([$quiz_id, $current_user['id']]);
}

if (!$quizStmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos sobre este quiz']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Validar que si no permite múltiples respuestas, solo haya una correcta
    $correctCount = 0;
    foreach ($options as $option) {
        if (isset($option['is_correct']) && $option['is_correct']) {
            $correctCount++;
        }
    }
    
    if (!$allow_multiple_answers && $correctCount > 1) {
        echo json_encode(['success' => false, 'message' => 'Solo puede marcar una opción como correcta cuando "Permitir múltiples respuestas" está desactivado']);
        exit;
    }
    
    if ($question_id > 0) {
        // Actualizar pregunta
        $stmt = $db->prepare("UPDATE quiz_questions SET text = ?, allow_multiple_answers = ? WHERE id = ?");
        $stmt->execute([$text, $allow_multiple_answers, $question_id]);
        
        // Eliminar opciones existentes
        $deleteStmt = $db->prepare("DELETE FROM quiz_question_options WHERE question_id = ?");
        $deleteStmt->execute([$question_id]);
    } else {
        // Crear nueva pregunta - obtener el siguiente order
        $orderStmt = $db->prepare("SELECT COALESCE(MAX(question_order), 0) + 1 as next_order FROM quiz_questions WHERE quiz_id = ?");
        $orderStmt->execute([$quiz_id]);
        $next_order = $orderStmt->fetch()['next_order'];
        
        $stmt = $db->prepare("INSERT INTO quiz_questions (quiz_id, question_order, text, allow_multiple_answers) VALUES (?, ?, ?, ?)");
        $stmt->execute([$quiz_id, $next_order, $text, $allow_multiple_answers]);
        $question_id = $db->lastInsertId();
    }
    
    // Guardar opciones
    if (!empty($options) && is_array($options)) {
        foreach ($options as $index => $option) {
            $option_text = sanitize($option['text'] ?? '');
            $is_correct = isset($option['is_correct']) && $option['is_correct'] ? 1 : 0;
            
            if (!empty($option_text)) {
                $optStmt = $db->prepare("INSERT INTO quiz_question_options (question_id, option_order, text, is_correct) VALUES (?, ?, ?, ?)");
                $optStmt->execute([$question_id, $index + 1, $option_text, $is_correct]);
            }
        }
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Pregunta guardada correctamente', 'question_id' => $question_id]);
    
} catch (PDOException $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
