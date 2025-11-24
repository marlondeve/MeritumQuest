<?php
require_once '../config/config.php';
requireAuth();

$current_user = getCurrentUser();
$is_admin = isAdmin();
$db = getDB();

// Generar QR si se solicita
if (isset($_GET['generate'])) {
    $entity_type = $_GET['generate']; // quiz, workshop, session
    $entity_id = intval($_GET['id'] ?? 0);
    
    if (in_array($entity_type, ['quiz', 'workshop']) && $entity_id > 0) {
        // Obtener el código del quiz o taller
        if ($entity_type === 'quiz') {
            $query = $is_admin 
                ? "SELECT code FROM quizzes WHERE id = ?"
                : "SELECT code FROM quizzes WHERE id = ? AND created_by = ?";
            $stmt = $db->prepare($query);
            if ($is_admin) {
                $stmt->execute([$entity_id]);
            } else {
                $stmt->execute([$entity_id, $current_user['id']]);
            }
        } else {
            $query = $is_admin 
                ? "SELECT code FROM workshops WHERE id = ?"
                : "SELECT code FROM workshops WHERE id = ? AND created_by = ?";
            $stmt = $db->prepare($query);
            if ($is_admin) {
                $stmt->execute([$entity_id]);
            } else {
                $stmt->execute([$entity_id, $current_user['id']]);
            }
        }
        
        $entity = $stmt->fetch();
        
        if ($entity) {
            // Verificar si ya existe un QR para este código
            $checkStmt = $db->prepare("SELECT id FROM qr_codes WHERE code = ? AND entity_type = ? AND entity_id = ?");
            $checkStmt->execute([$entity['code'], $entity_type, $entity_id]);
            
            if (!$checkStmt->fetch()) {
                // Crear nuevo código QR
                $insertStmt = $db->prepare("INSERT INTO qr_codes (code, entity_type, entity_id, generated_by) VALUES (?, ?, ?, ?)");
                $insertStmt->execute([$entity['code'], $entity_type, $entity_id, $current_user['id']]);
            }
        }
    }
}

// Listar códigos QR
$query = $is_admin 
    ? "SELECT qr.*, u.username, u.full_name FROM qr_codes qr LEFT JOIN users u ON qr.generated_by = u.id ORDER BY qr.created_at DESC"
    : "SELECT qr.*, u.username, u.full_name FROM qr_codes qr LEFT JOIN users u ON qr.generated_by = u.id WHERE qr.generated_by = ? ORDER BY qr.created_at DESC";
$stmt = $db->prepare($query);
if ($is_admin) {
    $stmt->execute();
} else {
    $stmt->execute([$current_user['id']]);
}
$qr_codes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Códigos QR - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/gamified.css">
</head>
<body style="background: #FAFAFA;">
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="ml-64 pt-4 p-8">
        <div class="container mx-auto">
            <div class="mb-6 slide-up">
                <div class="flex items-center space-x-3 mb-4">
                    <img src="<?php echo APP_URL; ?>/assets/avatar/17.png" alt="QR Codes" style="width: 60px; height: auto; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.1));">
                    <h1 style="font-size: 32px; font-weight: 900; color: var(--gray-900);">Códigos QR</h1>
                </div>
                <div class="card-game" style="background: var(--pastel-yellow); border-color: var(--duo-yellow); padding: 16px; margin-bottom: 16px;">
                    <div class="flex items-start space-x-3">
                        <img src="<?php echo APP_URL; ?>/assets/avatar/1.png" alt="Info" style="width: 50px; height: auto; flex-shrink: 0;">
                        <div>
                            <p style="font-size: 14px; font-weight: 600; color: var(--gray-700); line-height: 1.6;">
                                <strong>¿Qué es esta sección?</strong><br>
                                Aquí encontrarás todos los códigos QR generados para tus quizzes y talleres. Puedes descargarlos, copiarlos o compartirlos fácilmente. Los códigos QR permiten acceso rápido a tus contenidos educativos con un simple escaneo desde cualquier dispositivo móvil.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-game slide-up">
                <?php if (empty($qr_codes)): ?>
                    <div class="text-center py-12">
                        <div class="emoji-sticker" style="font-size: 80px; margin-bottom: 16px;">📱</div>
                        <p style="font-size: 20px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px;">No hay códigos QR aún</p>
                        <p style="font-size: 16px; font-weight: 600; color: var(--gray-500);">Genera códigos desde tus quizzes o talleres</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($qr_codes as $index => $qr): ?>
                        <div class="card-game bounce-in" style="animation-delay: <?php echo $index * 0.1; ?>s; padding: 20px;">
                            <div class="text-center mb-4">
                                <div id="qrcode-<?php echo $qr['id']; ?>" class="flex justify-center mb-4" style="background: white; padding: 12px; border-radius: 16px; border: 2px solid var(--gray-200);"></div>
                                <p style="font-family: monospace; font-size: 14px; font-weight: 800; color: var(--duo-blue); margin-bottom: 8px;"><?php echo htmlspecialchars($qr['code']); ?></p>
                                <span class="badge-game <?php 
                                    echo $qr['entity_type'] === 'quiz' ? 'blue' : 
                                        ($qr['entity_type'] === 'workshop' ? 'green' : 'yellow'); 
                                ?>">
                                    <?php echo $qr['entity_type'] === 'quiz' ? '❓ QUIZ' : '📚 TALLER'; ?>
                                </span>
                            </div>
                            <div style="font-size: 13px; font-weight: 600; color: var(--gray-700); margin-bottom: 16px;">
                                <p style="margin-bottom: 8px;">👤 <?php echo htmlspecialchars($qr['full_name'] ?? $qr['username']); ?></p>
                                <p style="margin-bottom: 8px;">📅 <?php echo date('d/m/Y H:i', strtotime($qr['created_at'])); ?></p>
                                <p>👁️ <?php echo $qr['scan_count']; ?> escaneos</p>
                            </div>
                            <div style="display: flex; gap: 8px; margin-top: 16px;">
                                <button onclick="downloadQR(<?php echo $qr['id']; ?>, '<?php echo htmlspecialchars($qr['code']); ?>')" 
                                    class="flex-1 btn-game btn-blue" style="padding: 10px; font-size: 12px;">
                                    📥 DESCARGAR
                                </button>
                                <button onclick="copyCode('<?php echo htmlspecialchars($qr['code']); ?>')" 
                                    class="flex-1 btn-game btn-yellow" style="padding: 10px; font-size: 12px;">
                                    📋 COPIAR
                                </button>
                            </div>
                        </div>
                        <script>
                            new QRCode(document.getElementById("qrcode-<?php echo $qr['id']; ?>"), {
                                text: "<?php echo PUBLIC_APP_URL; ?>/join.php?code=<?php echo htmlspecialchars($qr['code']); ?>",
                                width: 200,
                                height: 200,
                                colorDark: "#000000",
                                colorLight: "#ffffff",
                                correctLevel: QRCode.CorrectLevel.H
                            });
                        </script>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script>
        function downloadQR(id, code) {
            const canvas = document.querySelector(`#qrcode-${id} canvas`);
            if (canvas) {
                const link = document.createElement('a');
                link.download = `QR-${code}.png`;
                link.href = canvas.toDataURL();
                link.click();
                
                Swal.fire({
                    icon: 'success',
                    title: '✅ ¡Descargado!',
                    text: 'Código QR descargado correctamente',
                    confirmButtonColor: '#1CB0F6',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        }
        
        function copyCode(code) {
            navigator.clipboard.writeText(code).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: '📋 ¡Copiado!',
                    text: 'Código copiado al portapapeles',
                    confirmButtonColor: '#1CB0F6',
                    timer: 2000,
                    showConfirmButton: false
                });
            });
        }
    </script>
</body>
</html>
