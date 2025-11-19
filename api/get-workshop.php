<?php
require_once '../config/config.php';
requireAuth();

header('Content-Type: application/json');

$current_user = getCurrentUser();
$is_admin = isAdmin();
$db = getDB();

$workshop_id = intval($_GET['id'] ?? 0);

if ($workshop_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$query = $is_admin 
    ? "SELECT * FROM workshops WHERE id = ?"
    : "SELECT * FROM workshops WHERE id = ? AND created_by = ?";
$stmt = $db->prepare($query);
if ($is_admin) {
    $stmt->execute([$workshop_id]);
} else {
    $stmt->execute([$workshop_id, $current_user['id']]);
}

$workshop = $stmt->fetch();

if ($workshop) {
    echo json_encode(['success' => true, 'workshop' => $workshop]);
} else {
    echo json_encode(['success' => false, 'message' => 'Taller no encontrado']);
}
