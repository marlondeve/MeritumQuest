<?php
require_once '../config/config.php';
requireAuth();

$current_user = getCurrentUser();
$is_admin = isAdmin();
$db = getDB();

$workshop_id = intval($_GET['id'] ?? 0);

if ($workshop_id <= 0) {
    header('Location: workshops.php');
    exit;
}

// Obtener taller
$query = $is_admin 
    ? "SELECT w.*, u.username, u.full_name FROM workshops w LEFT JOIN users u ON w.created_by = u.id WHERE w.id = ?"
    : "SELECT w.*, u.username, u.full_name FROM workshops w LEFT JOIN users u ON w.created_by = u.id WHERE w.id = ? AND w.created_by = ?";
$stmt = $db->prepare($query);
if ($is_admin) {
    $stmt->execute([$workshop_id]);
} else {
    $stmt->execute([$workshop_id, $current_user['id']]);
}

$workshop = $stmt->fetch();

if (!$workshop) {
    header('Location: workshops.php');
    exit;
}

// Obtener código QR
$qrStmt = $db->prepare("SELECT * FROM qr_codes WHERE entity_type = 'workshop' AND entity_id = ? ORDER BY created_at DESC LIMIT 1");
$qrStmt->execute([$workshop_id]);
$qr_code = $qrStmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($workshop['title']); ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/gamified.css">
    <?php include '../includes/qr-modal.php'; ?>
</head>
<body style="background: #FAFAFA;">
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="ml-64 pt-4 p-8">
        <div class="container mx-auto max-w-6xl">
            <!-- Header -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                            <a href="workshops.php" class="text-gray-600 hover:text-gray-800">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($workshop['title']); ?></h1>
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $workshop['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $workshop['is_active'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </div>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($workshop['description'] ?? 'Sin descripción'); ?></p>
                        <div class="flex flex-wrap gap-4 text-sm">
                            <div class="flex items-center space-x-2">
                                <span class="font-mono text-purple-600 font-semibold"><?php echo htmlspecialchars($workshop['code']); ?></span>
                            </div>
                            <div class="flex items-center space-x-2 text-gray-600">
                                <i class="fas fa-calendar"></i>
                                <span>Creado: <?php echo date('d/m/Y H:i', strtotime($workshop['created_at'])); ?></span>
                            </div>
                            <?php if ($is_admin): ?>
                            <div class="flex items-center space-x-2 text-gray-600">
                                <i class="fas fa-user"></i>
                                <span>Por: <?php echo htmlspecialchars($workshop['full_name'] ?? $workshop['username']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="openQRModal('workshop', <?php echo $workshop['id']; ?>)" class="btn-game btn-green" style="padding: 10px 20px; font-size: 12px;">
                            🔳 VER QR
                        </button>
                        <button onclick="editWorkshop(<?php echo $workshop['id']; ?>)" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                            <i class="fas fa-edit mr-2"></i>Editar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Información del Taller -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-info-circle mr-2"></i>Información
                    </h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Estado:</span>
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $workshop['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $workshop['is_active'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </div>
                        <?php if ($workshop['max_participants']): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Máximo de participantes:</span>
                            <span class="font-semibold text-gray-800"><?php echo $workshop['max_participants']; ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($workshop['available_from']): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Disponible desde:</span>
                            <span class="font-semibold text-gray-800"><?php echo date('d/m/Y H:i', strtotime($workshop['available_from'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($workshop['available_to']): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Disponible hasta:</span>
                            <span class="font-semibold text-gray-800"><?php echo date('d/m/Y H:i', strtotime($workshop['available_to'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-qrcode mr-2"></i>Código QR
                    </h2>
                    <?php if ($qr_code): ?>
                    <div class="text-center">
                        <div class="inline-block p-4 bg-gray-50 rounded-lg mb-4">
                            <div id="qrcode-workshop" class="flex justify-center"></div>
                        </div>
                        <p class="font-mono text-sm font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($qr_code['code']); ?></p>
                        <p class="text-xs text-gray-600 mb-4">
                            Escaneos: <?php echo $qr_code['scan_count']; ?>
                        </p>
                        <div class="flex space-x-2">
                            <button onclick="downloadQR('workshop', '<?php echo htmlspecialchars($qr_code['code']); ?>')" 
                                class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm transition-colors">
                                <i class="fas fa-download mr-1"></i>Descargar
                            </button>
                            <button onclick="copyCode('<?php echo htmlspecialchars($qr_code['code']); ?>')" 
                                class="flex-1 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 text-sm transition-colors">
                                <i class="fas fa-copy mr-1"></i>Copiar
                            </button>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-qrcode text-4xl mb-4"></i>
                        <p class="mb-4">No hay código QR generado</p>
                        <button onclick="openQRModal('workshop', <?php echo $workshop['id']; ?>)" class="text-purple-600 hover:text-purple-800 font-medium" style="background: none; border: none; cursor: pointer;">
                            Generar código QR
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        <?php if ($qr_code): ?>
        new QRCode(document.getElementById("qrcode-workshop"), {
            text: "<?php echo PUBLIC_APP_URL; ?>/join.php?code=<?php echo htmlspecialchars($qr_code['code']); ?>",
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        <?php endif; ?>

        function editWorkshop(id) {
            window.location.href = `workshops.php`;
            // Abrir modal de edición desde la página de workshops
            setTimeout(() => {
                if (window.opener) {
                    window.opener.editWorkshop(id);
                    window.close();
                }
            }, 100);
        }

        function downloadQR(type, code) {
            const canvas = document.querySelector(`#qrcode-${type} canvas`);
            if (canvas) {
                const link = document.createElement('a');
                link.download = `QR-${code}.png`;
                link.href = canvas.toDataURL();
                link.click();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Descargado',
                    text: 'Código QR descargado correctamente',
                    confirmButtonColor: '#9333ea',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        }
        
        function copyCode(code) {
            navigator.clipboard.writeText(code).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Copiado',
                    text: 'Código copiado al portapapeles',
                    confirmButtonColor: '#9333ea',
                    timer: 2000,
                    showConfirmButton: false
                });
            });
        }
    </script>
</body>
</html>
