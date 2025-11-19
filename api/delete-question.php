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

// Verificar permisos
$stmt = $db->prepare("SELECT q.id, qz.created_by FROM quiz_questions q INNER JOIN quizzes qz ON q.quiz_id = qz.id WHERE q.id = ?");
$stmt->execute([$question_id]);
$question = $stmt->fetch();

if (!$question) {
    echo json_encode(['success' => false, 'message' => 'Pregunta no encontrada']);
    exit;
}

if (!$is_admin && $question['created_by'] != $current_user['id']) {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos']);
    exit;
}

// Eliminar pregunta (las opciones se eliminan en cascada)
$deleteStmt = $db->prepare("DELETE FROM quiz_questions WHERE id = ?");
if ($deleteStmt->execute([$question_id])) {
    echo json_encode(['success' => true, 'message' => 'Pregunta eliminada correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
}
