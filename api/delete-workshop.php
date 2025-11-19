<?php
require_once '../config/config.php';
requireAuth();

header('Content-Type: application/json');

$workshop_id = intval($_GET['id'] ?? 0);
$current_user = getCurrentUser();
$is_admin = isAdmin();

if ($workshop_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$db = getDB();

// Verificar permisos
$query = $is_admin 
    ? "SELECT id FROM workshops WHERE id = ?"
    : "SELECT id FROM workshops WHERE id = ? AND created_by = ?";
$stmt = $db->prepare($query);
if ($is_admin) {
    $stmt->execute([$workshop_id]);
} else {
    $stmt->execute([$workshop_id, $current_user['id']]);
}

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar este taller']);
    exit;
}

// Eliminar taller
$deleteStmt = $db->prepare("DELETE FROM workshops WHERE id = ?");
if ($deleteStmt->execute([$workshop_id])) {
    echo json_encode(['success' => true, 'message' => 'Taller eliminado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el taller']);
}
