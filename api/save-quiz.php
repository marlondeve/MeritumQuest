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

$quiz_id = intval($_POST['quiz_id'] ?? 0);
$code = sanitize($_POST['code'] ?? '');
$title = sanitize($_POST['title'] ?? '');
$description = sanitize($_POST['description'] ?? '');
$points_per_question = intval($_POST['points_per_question'] ?? 100);
$use_time_per_question = 0; // Deshabilitado por ahora

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'El título es requerido']);
    exit;
}

try {
    if ($quiz_id > 0) {
        // Actualizar - solo actualizar código si se proporciona
        if (!empty($code)) {
            $query = $is_admin 
                ? "UPDATE quizzes SET code = ?, title = ?, description = ?, points_per_question = ?, use_time_per_question = ? WHERE id = ?"
                : "UPDATE quizzes SET code = ?, title = ?, description = ?, points_per_question = ?, use_time_per_question = ? WHERE id = ? AND created_by = ?";
            $stmt = $db->prepare($query);
            if ($is_admin) {
                $stmt->execute([$code, $title, $description, $points_per_question, $use_time_per_question, $quiz_id]);
            } else {
                $stmt->execute([$code, $title, $description, $points_per_question, $use_time_per_question, $quiz_id, $current_user['id']]);
            }
        } else {
            $query = $is_admin 
                ? "UPDATE quizzes SET title = ?, description = ?, points_per_question = ?, use_time_per_question = ? WHERE id = ?"
                : "UPDATE quizzes SET title = ?, description = ?, points_per_question = ?, use_time_per_question = ? WHERE id = ? AND created_by = ?";
            $stmt = $db->prepare($query);
            if ($is_admin) {
                $stmt->execute([$title, $description, $points_per_question, $use_time_per_question, $quiz_id]);
            } else {
                $stmt->execute([$title, $description, $points_per_question, $use_time_per_question, $quiz_id, $current_user['id']]);
            }
        }
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Quiz actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el quiz o no tienes permisos']);
        }
    } else {
        // Crear - generar código automáticamente
        $code = generateUniqueCode('QUIZ', 'quiz');
        
        $stmt = $db->prepare("INSERT INTO quizzes (code, title, description, points_per_question, use_time_per_question, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $title, $description, $points_per_question, $use_time_per_question, $current_user['id']]);
        $new_id = $db->lastInsertId();
        
        // Crear código QR automáticamente
        $qrStmt = $db->prepare("INSERT INTO qr_codes (code, entity_type, entity_id, generated_by) VALUES (?, 'quiz', ?, ?)");
        $qrStmt->execute([$code, $new_id, $current_user['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Quiz creado correctamente', 'id' => $new_id, 'code' => $code]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
