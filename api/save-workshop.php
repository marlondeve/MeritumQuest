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

$workshop_id = intval($_POST['workshop_id'] ?? 0);
$code = sanitize($_POST['code'] ?? '');
$title = sanitize($_POST['title'] ?? '');
$description = sanitize($_POST['description'] ?? '');
$available_from = $_POST['available_from'] ?? null;
$available_to = $_POST['available_to'] ?? null;
$max_participants = !empty($_POST['max_participants']) ? intval($_POST['max_participants']) : null;
$is_active = isset($_POST['is_active']) ? 1 : 0;

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'El título es requerido']);
    exit;
}

try {
    if ($workshop_id > 0) {
        // Actualizar - solo actualizar código si se proporciona
        if (!empty($code)) {
            $query = $is_admin 
                ? "UPDATE workshops SET code = ?, title = ?, description = ?, available_from = ?, available_to = ?, max_participants = ?, is_active = ? WHERE id = ?"
                : "UPDATE workshops SET code = ?, title = ?, description = ?, available_from = ?, available_to = ?, max_participants = ?, is_active = ? WHERE id = ? AND created_by = ?";
            $stmt = $db->prepare($query);
            if ($is_admin) {
                $stmt->execute([$code, $title, $description, $available_from ?: null, $available_to ?: null, $max_participants, $is_active, $workshop_id]);
            } else {
                $stmt->execute([$code, $title, $description, $available_from ?: null, $available_to ?: null, $max_participants, $is_active, $workshop_id, $current_user['id']]);
            }
        } else {
            $query = $is_admin 
                ? "UPDATE workshops SET title = ?, description = ?, available_from = ?, available_to = ?, max_participants = ?, is_active = ? WHERE id = ?"
                : "UPDATE workshops SET title = ?, description = ?, available_from = ?, available_to = ?, max_participants = ?, is_active = ? WHERE id = ? AND created_by = ?";
            $stmt = $db->prepare($query);
            if ($is_admin) {
                $stmt->execute([$title, $description, $available_from ?: null, $available_to ?: null, $max_participants, $is_active, $workshop_id]);
            } else {
                $stmt->execute([$title, $description, $available_from ?: null, $available_to ?: null, $max_participants, $is_active, $workshop_id, $current_user['id']]);
            }
        }
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Taller actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el taller o no tienes permisos']);
        }
    } else {
        // Crear - generar código automáticamente
        $code = generateUniqueCode('WORK', 'workshop');
        
        $stmt = $db->prepare("INSERT INTO workshops (code, title, description, available_from, available_to, max_participants, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $title, $description, $available_from ?: null, $available_to ?: null, $max_participants, $is_active, $current_user['id']]);
        $new_id = $db->lastInsertId();
        
        // Crear código QR automáticamente
        $qrStmt = $db->prepare("INSERT INTO qr_codes (code, entity_type, entity_id, generated_by) VALUES (?, 'workshop', ?, ?)");
        $qrStmt->execute([$code, $new_id, $current_user['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Taller creado correctamente', 'id' => $new_id, 'code' => $code]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
