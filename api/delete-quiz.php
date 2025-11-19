<?php
require_once '../config/config.php';
requireAuth();

header('Content-Type: application/json');

$quiz_id = intval($_GET['id'] ?? 0);
$current_user = getCurrentUser();
$is_admin = isAdmin();

if ($quiz_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$db = getDB();

// Verificar permisos
$query = $is_admin 
    ? "SELECT id FROM quizzes WHERE id = ?"
    : "SELECT id FROM quizzes WHERE id = ? AND created_by = ?";
$stmt = $db->prepare($query);
if ($is_admin) {
    $stmt->execute([$quiz_id]);
} else {
    $stmt->execute([$quiz_id, $current_user['id']]);
}

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar este quiz']);
    exit;
}

// Eliminar quiz (las foreign keys se encargan de eliminar en cascada)
$deleteStmt = $db->prepare("DELETE FROM quizzes WHERE id = ?");
if ($deleteStmt->execute([$quiz_id])) {
    echo json_encode(['success' => true, 'message' => 'Quiz eliminado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el quiz']);
}
