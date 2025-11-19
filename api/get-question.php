<?php
require_once '../config/config.php';
requireAuth();

header('Content-Type: application/json');

$current_user = getCurrentUser();
$is_admin = isAdmin();
$db = getDB();

$question_id = intval($_GET['id'] ?? 0);

if ($question_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

// Obtener pregunta
$stmt = $db->prepare("SELECT q.*, qz.created_by FROM quiz_questions q INNER JOIN quizzes qz ON q.quiz_id = qz.id WHERE q.id = ?");
$stmt->execute([$question_id]);
$question = $stmt->fetch();

if (!$question) {
    echo json_encode(['success' => false, 'message' => 'Pregunta no encontrada']);
    exit;
}

// Verificar permisos
if (!$is_admin && $question['created_by'] != $current_user['id']) {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos']);
    exit;
}

// Obtener opciones
$optStmt = $db->prepare("SELECT * FROM quiz_question_options WHERE question_id = ? ORDER BY option_order ASC");
$optStmt->execute([$question_id]);
$options = $optStmt->fetchAll();

$question['options'] = $options;

echo json_encode(['success' => true, 'question' => $question]);
