<?php
require_once '../config/config.php';
requireAuth();

$current_user = getCurrentUser();
$is_admin = isAdmin();
$db = getDB();

header('Content-Type: application/json');

$entity_type = $_GET['type'] ?? ''; // quiz, workshop
$entity_id = intval($_GET['id'] ?? 0);

if (empty($entity_type) || $entity_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit;
}

if (!in_array($entity_type, ['quiz', 'workshop'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo de entidad inválido']);
    exit;
}

// Obtener el código del quiz o taller
if ($entity_type === 'quiz') {
    $query = $is_admin 
        ? "SELECT id, code, title FROM quizzes WHERE id = ?"
        : "SELECT id, code, title FROM quizzes WHERE id = ? AND created_by = ?";
    $stmt = $db->prepare($query);
    if ($is_admin) {
        $stmt->execute([$entity_id]);
    } else {
        $stmt->execute([$entity_id, $current_user['id']]);
    }
} else {
    $query = $is_admin 
        ? "SELECT id, code, title FROM workshops WHERE id = ?"
        : "SELECT id, code, title FROM workshops WHERE id = ? AND created_by = ?";
    $stmt = $db->prepare($query);
    if ($is_admin) {
        $stmt->execute([$entity_id]);
    } else {
        $stmt->execute([$entity_id, $current_user['id']]);
    }
}

$entity = $stmt->fetch();

if (!$entity) {
    echo json_encode(['success' => false, 'message' => 'No se encontró el ' . ($entity_type === 'quiz' ? 'quiz' : 'taller')]);
    exit;
}

// Verificar si ya existe un QR para este código
$checkStmt = $db->prepare("SELECT id FROM qr_codes WHERE code = ? AND entity_type = ? AND entity_id = ?");
$checkStmt->execute([$entity['code'], $entity_type, $entity_id]);

if (!$checkStmt->fetch()) {
    // Crear nuevo código QR
    $insertStmt = $db->prepare("INSERT INTO qr_codes (code, entity_type, entity_id, generated_by) VALUES (?, ?, ?, ?)");
    $insertStmt->execute([$entity['code'], $entity_type, $entity_id, $current_user['id']]);
}

// Obtener información del QR
$qrStmt = $db->prepare("SELECT * FROM qr_codes WHERE code = ? AND entity_type = ? AND entity_id = ? ORDER BY created_at DESC LIMIT 1");
$qrStmt->execute([$entity['code'], $entity_type, $entity_id]);
$qr = $qrStmt->fetch();

echo json_encode([
    'success' => true,
    'code' => $entity['code'],
    'title' => $entity['title'],
    'entity_type' => $entity_type,
    'qr_url' => APP_URL . '/join.php?code=' . $entity['code'],
    'qr_code' => $entity['code'], // Código para el QR (por ahora solo el código)
    'scan_count' => $qr['scan_count'] ?? 0
]);
?>

