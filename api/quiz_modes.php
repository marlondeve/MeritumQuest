<?php
require_once '../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$db = getDBConnection();

switch ($method) {
    case 'POST':
        // Crear o actualizar modos de un quiz
        $data = json_decode(file_get_contents('php://input'), true);
        $quizId = intval($data['quiz_id'] ?? 0);
        
        if (!$quizId) {
            jsonResponse(['error' => 'quiz_id requerido'], 400);
        }
        
        // Eliminar modos existentes
        $stmt = $db->prepare("DELETE FROM quiz_modes WHERE quiz_id = ?");
        $stmt->execute([$quizId]);
        
        // Crear nuevos modos
        if (isset($data['modes']) && is_array($data['modes'])) {
            foreach ($data['modes'] as $mode) {
                $stmt = $db->prepare("
                    INSERT INTO quiz_modes (quiz_id, mode_type, enabled, available_from, available_to, max_attempts, 
                                          show_correction_at_end, show_explanations, show_ranking, public_ranking, feedback_immediate)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $availableFrom = !empty($mode['available_from']) ? date('Y-m-d H:i:s', strtotime($mode['available_from'])) : null;
                $availableTo = !empty($mode['available_to']) ? date('Y-m-d H:i:s', strtotime($mode['available_to'])) : null;
                
                $stmt->execute([
                    $quizId,
                    $mode['mode_type'],
                    $mode['enabled'] ?? 1,
                    $availableFrom,
                    $availableTo,
                    $mode['max_attempts'] ?? 1,
                    $mode['show_correction_at_end'] ?? 1,
                    $mode['show_explanations'] ?? 1,
                    $mode['show_ranking'] ?? 1,
                    $mode['public_ranking'] ?? 1,
                    $mode['feedback_immediate'] ?? 0
                ]);
            }
        }
        
        clearQuizCache($quizId);
        jsonResponse(['message' => 'Modos actualizados exitosamente']);
        break;
        
    case 'GET':
        // Obtener modos de un quiz
        $quizId = intval($_GET['quiz_id'] ?? 0);
        
        if (!$quizId) {
            jsonResponse(['error' => 'quiz_id requerido'], 400);
        }
        
        $stmt = $db->prepare("SELECT * FROM quiz_modes WHERE quiz_id = ?");
        $stmt->execute([$quizId]);
        $modes = $stmt->fetchAll();
        
        jsonResponse($modes);
        break;
        
    default:
        jsonResponse(['error' => 'Método no permitido'], 405);
}
?>


