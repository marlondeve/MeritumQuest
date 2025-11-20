<?php
require_once '../config/config.php';
$db = getDB();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$quiz_id = intval($data['quiz_id'] ?? 0);
$quiz_code = trim($data['quiz_code'] ?? '');
$participant_name = trim($data['participant_name'] ?? '');
$participant_email = trim($data['participant_email'] ?? '');

if (empty($quiz_id) || empty($quiz_code) || empty($participant_name)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Verificar que el quiz existe
    $stmt = $db->prepare("SELECT id FROM quizzes WHERE id = ? AND code = ?");
    $stmt->execute([$quiz_id, $quiz_code]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        echo json_encode(['success' => false, 'message' => 'Quiz no encontrado']);
        exit;
    }
    
    // Crear o obtener participante
    $participant_id = null;
    if (!empty($participant_email)) {
        $stmt = $db->prepare("SELECT id FROM participants WHERE email = ?");
        $stmt->execute([$participant_email]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $participant_id = $existing['id'];
        } else {
            $stmt = $db->prepare("INSERT INTO participants (name, email) VALUES (?, ?)");
            $stmt->execute([$participant_name, $participant_email]);
            $participant_id = $db->lastInsertId();
        }
    } else {
        // Crear participante sin email
        $stmt = $db->prepare("INSERT INTO participants (name) VALUES (?)");
        $stmt->execute([$participant_name]);
        $participant_id = $db->lastInsertId();
    }
    
    // Crear sesión automática si no existe una activa
    $stmt = $db->prepare("SELECT id FROM quiz_sessions WHERE quiz_id = ? AND ended_at IS NULL ORDER BY started_at DESC LIMIT 1");
    $stmt->execute([$quiz_id]);
    $session = $stmt->fetch();
    
    if (!$session) {
        // Crear nueva sesión con código corto (máximo 20 caracteres)
        // Usar solo los últimos 6 dígitos del timestamp para mantener el código corto
        $timestamp_suffix = substr(time(), -6);
        $session_code = substr($quiz_code, 0, 13) . '-' . $timestamp_suffix; // Máximo 20 caracteres
        
        // Si aún es muy largo, usar solo el quiz_code
        if (strlen($session_code) > 20) {
            $session_code = substr($quiz_code, 0, 20);
        }
        
        // Asegurar que el código sea único
        $attempts = 0;
        while ($attempts < 10) {
            $checkStmt = $db->prepare("SELECT id FROM quiz_sessions WHERE session_code = ?");
            $checkStmt->execute([$session_code]);
            if (!$checkStmt->fetch()) {
                break;
            }
            // Si existe, agregar un número aleatorio
            $session_code = substr($session_code, 0, 18) . rand(10, 99);
            $attempts++;
        }
        
        $stmt = $db->prepare("INSERT INTO quiz_sessions (quiz_id, mode_type, session_code) VALUES (?, 'workshop', ?)");
        $stmt->execute([$quiz_id, $session_code]);
        $session_id = $db->lastInsertId();
    } else {
        $session_id = $session['id'];
    }
    
    // Crear intento
    $stmt = $db->prepare("INSERT INTO quiz_attempts (session_id, quiz_id, participant_id, participant_name) VALUES (?, ?, ?, ?)");
    $stmt->execute([$session_id, $quiz_id, $participant_id, $participant_name]);
    $attempt_id = $db->lastInsertId();
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'attempt_id' => $attempt_id,
        'session_id' => $session_id
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

