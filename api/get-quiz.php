<?php
require_once '../config/config.php';
requireAuth();

header('Content-Type: application/json');

$current_user = getCurrentUser();
$is_admin = isAdmin();
$db = getDB();

$quiz_id = intval($_GET['id'] ?? 0);

if ($quiz_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$query = $is_admin 
    ? "SELECT * FROM quizzes WHERE id = ?"
    : "SELECT * FROM quizzes WHERE id = ? AND created_by = ?";
$stmt = $db->prepare($query);
if ($is_admin) {
    $stmt->execute([$quiz_id]);
} else {
    $stmt->execute([$quiz_id, $current_user['id']]);
}

$quiz = $stmt->fetch();

if ($quiz) {
    echo json_encode(['success' => true, 'quiz' => $quiz]);
} else {
    echo json_encode(['success' => false, 'message' => 'Quiz no encontrado']);
}
