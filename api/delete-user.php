<?php
require_once '../config/config.php';
requireAdmin(); // Solo administradores

header('Content-Type: application/json');

$user_id = intval($_GET['id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

// No permitir auto-eliminación
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propia cuenta']);
    exit;
}

$db = getDB();

// Eliminar usuario
$deleteStmt = $db->prepare("DELETE FROM users WHERE id = ?");
if ($deleteStmt->execute([$user_id])) {
    echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el usuario']);
}
