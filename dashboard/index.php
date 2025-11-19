<?php
require_once '../config/config.php';
requireAuth();

$current_user = getCurrentUser();
$is_admin = isAdmin();
$db = getDB();

// Estadísticas
$stats = [];

// Quizzes del usuario
$quizQuery = $is_admin 
    ? "SELECT COUNT(*) as total FROM quizzes"
    : "SELECT COUNT(*) as total FROM quizzes WHERE created_by = ?";
$stmt = $db->prepare($quizQuery);
if ($is_admin) {
    $stmt->execute();
} else {
    $stmt->execute([$current_user['id']]);
}
$stats['quizzes'] = $stmt->fetch()['total'];

// Talleres del usuario
$workshopQuery = $is_admin 
    ? "SELECT COUNT(*) as total FROM workshops"
    : "SELECT COUNT(*) as total FROM workshops WHERE created_by = ?";
$stmt = $db->prepare($workshopQuery);
if ($is_admin) {
    $stmt->execute();
} else {
    $stmt->execute([$current_user['id']]);
}
$stats['workshops'] = $stmt->fetch()['total'];

// Códigos QR generados
$qrQuery = $is_admin 
    ? "SELECT COUNT(*) as total FROM qr_codes"
    : "SELECT COUNT(*) as total FROM qr_codes WHERE generated_by = ?";
$stmt = $db->prepare($qrQuery);
if ($is_admin) {
    $stmt->execute();
} else {
    $stmt->execute([$current_user['id']]);
}
$stats['qr_codes'] = $stmt->fetch()['total'];

// Usuarios totales (solo admin)
$stats['users'] = 0;
if ($is_admin) {
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $stats['users'] = $stmt->fetch()['total'];
}

// Quizzes recientes
$recentQuizzesQuery = $is_admin 
    ? "SELECT q.*, u.username, u.full_name FROM quizzes q LEFT JOIN users u ON q.created_by = u.id ORDER BY q.created_at DESC LIMIT 5"
    : "SELECT q.*, u.username, u.full_name FROM quizzes q LEFT JOIN users u ON q.created_by = u.id WHERE q.created_by = ? ORDER BY q.created_at DESC LIMIT 5";
$stmt = $db->prepare($recentQuizzesQuery);
if ($is_admin) {
    $stmt->execute();
} else {
    $stmt->execute([$current_user['id']]);
}
$recent_quizzes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/gamified.css">
</head>
<body style="background: #FAFAFA;">
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="ml-64 pt-4 p-8">
        <div class="container mx-auto" style="max-width: 1400px;">
            <!-- Bienvenida -->
            <div class="mb-8 slide-up">
                <div class="card-game" style="background: linear-gradient(135deg, #1CB0F6 0%, #4FC3F7 100%); color: white; border-color: #1391C4;">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="emoji-sticker" style="font-size: 64px;">👋</div>
                            <div>
                                <h1 style="font-size: 36px; font-weight: 900; margin-bottom: 4px;">
                                    ¡Hola, <?php echo htmlspecialchars($current_user['full_name'] ?? $current_user['username']); ?>!
                                </h1>
                                <p style="font-size: 18px; font-weight: 600; opacity: 0.9;">¡Sigue aprendiendo y enseñando! 🚀</p>
                            </div>
                        </div>
                        <div class="emoji-sticker" style="font-size: 80px;">🎯</div>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-game blue bounce-in" style="animation-delay: 0.1s;">
                    <div class="icon-game">
                        <span style="font-size: 32px;">❓</span>
                    </div>
                    <div class="number"><?php echo $stats['quizzes']; ?></div>
                    <div class="label">Quizzes</div>
                </div>

                <div class="stat-game green bounce-in" style="animation-delay: 0.2s;">
                    <div class="icon-game">
                        <span style="font-size: 32px;">📚</span>
                    </div>
                    <div class="number"><?php echo $stats['workshops']; ?></div>
                    <div class="label">Talleres</div>
                </div>

                <div class="stat-game yellow bounce-in" style="animation-delay: 0.3s;">
                    <div class="icon-game">
                        <span style="font-size: 32px;">🔳</span>
                    </div>
                    <div class="number"><?php echo $stats['qr_codes']; ?></div>
                    <div class="label">Códigos QR</div>
                </div>

                <?php if ($is_admin): ?>
                <div class="stat-game pink bounce-in" style="animation-delay: 0.4s;">
                    <div class="icon-game">
                        <span style="font-size: 32px;">👥</span>
                    </div>
                    <div class="number"><?php echo $stats['users']; ?></div>
                    <div class="label">Usuarios</div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Acciones Rápidas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <button onclick="openQuizModal()" class="action-card-game slide-up" style="animation-delay: 0.1s;">
                    <div class="icon-large">❓</div>
                    <div class="title">Crear Quiz</div>
                    <div class="description">Crea un nuevo cuestionario divertido</div>
                    <div style="margin-top: 16px;">
                        <div class="btn-game btn-blue" style="width: 100%; padding: 12px;">¡EMPEZAR!</div>
                    </div>
                </button>

                <button onclick="openWorkshopModal()" class="action-card-game slide-up" style="animation-delay: 0.2s;">
                    <div class="icon-large">📚</div>
                    <div class="title">Crear Taller</div>
                    <div class="description">Organiza un taller educativo</div>
                    <div style="margin-top: 16px;">
                        <div class="btn-game btn-green" style="width: 100%; padding: 12px;">¡COMENZAR!</div>
                    </div>
                </button>

                <a href="qr-codes.php" class="action-card-game slide-up" style="animation-delay: 0.3s; display: block; text-decoration: none;">
                    <div class="icon-large">🔳</div>
                    <div class="title">Ver Códigos QR</div>
                    <div class="description">Gestiona tus códigos QR</div>
                    <div style="margin-top: 16px;">
                        <div class="btn-game btn-yellow" style="width: 100%; padding: 12px;">VER CÓDIGOS</div>
                    </div>
                </a>
            </div>

            <!-- Quizzes Recientes -->
            <div class="card-game slide-up">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <span style="font-size: 32px;">⏰</span>
                        <h2 style="font-size: 24px; font-weight: 900; color: var(--gray-900);">Quizzes Recientes</h2>
                    </div>
                    <a href="quizzes.php" class="btn-game btn-blue">
                        VER TODOS →
                    </a>
                </div>
                
                <?php if (empty($recent_quizzes)): ?>
                    <div class="text-center py-12 text-gray-500">
                        <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-inbox text-4xl text-gray-400"></i>
                        </div>
                        <p class="text-lg font-medium mb-2">No hay quizzes aún</p>
                        <p class="text-sm">¡Crea tu primer quiz!</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto modern-table rounded-xl">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Código</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Título</th>
                                    <?php if ($is_admin): ?>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Creado por</th>
                                    <?php endif; ?>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_quizzes as $index => $quiz): ?>
                                <tr class="slide-in-right" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                                    <td class="px-6 py-4">
                                        <span class="font-mono text-sm font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent"><?php echo htmlspecialchars($quiz['code']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-gray-800"><?php echo htmlspecialchars($quiz['title']); ?></td>
                                    <?php if ($is_admin): ?>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($quiz['full_name'] ?? $quiz['username']); ?></td>
                                    <?php endif; ?>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo date('d/m/Y', strtotime($quiz['created_at'])); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-3">
                                            <a href="quiz-view.php?id=<?php echo $quiz['id']; ?>" class="w-8 h-8 flex items-center justify-center rounded-lg bg-purple-100 text-purple-600 hover:bg-purple-200 hover:scale-110 transition-all duration-300 icon-hover" title="Ver detalles">
                                                <i class="fas fa-eye text-sm"></i>
                                            </a>
                                            <a href="qr-codes.php?generate=quiz&id=<?php echo $quiz['id']; ?>" class="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 hover:bg-emerald-200 hover:scale-110 transition-all duration-300 icon-hover" title="Generar QR">
                                                <i class="fas fa-qrcode text-sm"></i>
                                            </a>
                                            <a href="quizzes.php" class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 hover:scale-110 transition-all duration-300 icon-hover" title="Editar">
                                                <i class="fas fa-edit text-sm"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal para Crear Quiz -->
    <div id="quizModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="modal-game max-w-2xl w-full max-h-[90vh] overflow-y-auto bounce-in">
            <div class="modal-header">
                <div class="flex items-center justify-between">
                    <h2 class="modal-title flex items-center">
                        <span style="font-size: 28px; margin-right: 12px;">❓</span>
                        CREAR QUIZ
                    </h2>
                    <button onclick="closeQuizModal()" class="text-white hover:opacity-80 transition-opacity" style="font-size: 28px; font-weight: 700;">
                        ✕
                    </button>
                </div>
            </div>
            
            <form id="quizForm" class="p-6" style="padding: 32px;">
                <input type="hidden" id="quiz_id" name="quiz_id" value="0">
                <input type="hidden" id="quiz_code" name="code" value="">
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        ⭐ Puntos por Pregunta
                    </label>
                    <input type="number" id="quiz_points" name="points_per_question" value="100" min="1" class="input-game">
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        ✏️ Título *
                    </label>
                    <input type="text" id="quiz_title" name="title" required class="input-game" placeholder="Ej: Quiz de Matemáticas">
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        📝 Descripción
                    </label>
                    <textarea id="quiz_description" name="description" rows="4" class="input-game" placeholder="Describe tu quiz..."></textarea>
                </div>
                
                <div style="display: flex; gap: 16px; margin-top: 32px;">
                    <button type="submit" class="flex-1 btn-game btn-blue">
                        💾 GUARDAR QUIZ
                    </button>
                    <button type="button" onclick="closeQuizModal()" style="flex: 1; background: var(--gray-200); color: var(--gray-700); padding: 14px 24px; border-radius: 16px; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.15s ease;">
                        CANCELAR
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Crear Taller -->
    <div id="workshopModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="modal-game max-w-2xl w-full max-h-[90vh] overflow-y-auto bounce-in">
            <div class="modal-header" style="background: linear-gradient(135deg, #58CC02 0%, #89E219 100%); border-bottom-color: #46A302;">
                <div class="flex items-center justify-between">
                    <h2 class="modal-title flex items-center">
                        <span style="font-size: 28px; margin-right: 12px;">📚</span>
                        CREAR TALLER
                    </h2>
                    <button onclick="closeWorkshopModal()" class="text-white hover:opacity-80 transition-opacity" style="font-size: 28px; font-weight: 700;">
                        ✕
                    </button>
                </div>
            </div>
            
            <form id="workshopForm" class="p-6 space-y-6">
                <input type="hidden" id="workshop_id" name="workshop_id" value="0">
                <input type="hidden" id="workshop_code" name="code" value="">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Título *</label>
                    <input type="text" id="workshop_title" name="title" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                    <textarea id="workshop_description" name="description" rows="4"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Disponible desde</label>
                        <input type="datetime-local" id="workshop_from" name="available_from"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Disponible hasta</label>
                        <input type="datetime-local" id="workshop_to" name="available_to"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Máximo de participantes</label>
                    <input type="number" id="workshop_max" name="max_participants" min="1"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" id="workshop_active" name="is_active" value="1" checked
                            class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <span class="text-sm font-medium text-gray-700">Taller activo</span>
                    </label>
                </div>
                
                <div class="flex space-x-4 pt-4 border-t border-gray-200">
                    <button type="submit" class="flex-1 modern-btn bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-3 rounded-xl font-semibold">
                        <i class="fas fa-save mr-2"></i>Guardar
                    </button>
                    <button type="button" onclick="closeWorkshopModal()" class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-xl hover:bg-gray-300 transition-all font-semibold">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Funciones para Quiz Modal
        function openQuizModal() {
            const modal = document.getElementById('quizModal');
            const form = document.getElementById('quizForm');
            form.reset();
            document.getElementById('quiz_id').value = '0';
            document.getElementById('quiz_points').value = '100';
            modal.classList.remove('hidden');
        }
        
        function closeQuizModal() {
            document.getElementById('quizModal').classList.add('hidden');
        }
        
        document.getElementById('quizForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('quiz_id', document.getElementById('quiz_id').value);
            
            try {
                const response = await fetch('../api/save-quiz.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const message = result.code 
                        ? `${result.message}\n\nCódigo generado: ${result.code}`
                        : result.message;
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        html: message.replace(/\n/g, '<br>'),
                        confirmButtonColor: '#3b82f6',
                        timer: 3000,
                        showConfirmButton: true
                    }).then(() => {
                        closeQuizModal();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message,
                        confirmButtonColor: '#3b82f6'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al guardar el quiz',
                    confirmButtonColor: '#3b82f6'
                });
            }
        });
        
        // Funciones para Workshop Modal
        function openWorkshopModal() {
            const modal = document.getElementById('workshopModal');
            const form = document.getElementById('workshopForm');
            form.reset();
            document.getElementById('workshop_id').value = '0';
            document.getElementById('workshop_active').checked = true;
            modal.classList.remove('hidden');
        }
        
        function closeWorkshopModal() {
            document.getElementById('workshopModal').classList.add('hidden');
        }
        
        document.getElementById('workshopForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('workshop_id', document.getElementById('workshop_id').value);
            
            try {
                const response = await fetch('../api/save-workshop.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const message = result.code 
                        ? `${result.message}\n\nCódigo generado: ${result.code}`
                        : result.message;
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        html: message.replace(/\n/g, '<br>'),
                        confirmButtonColor: '#9333ea',
                        timer: 3000,
                        showConfirmButton: true
                    }).then(() => {
                        closeWorkshopModal();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message,
                        confirmButtonColor: '#9333ea'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al guardar el taller',
                    confirmButtonColor: '#9333ea'
                });
            }
        });
        
        // Cerrar modales al hacer click fuera
        document.getElementById('quizModal').addEventListener('click', function(e) {
            if (e.target === this) closeQuizModal();
        });
        
        document.getElementById('workshopModal').addEventListener('click', function(e) {
            if (e.target === this) closeWorkshopModal();
        });
    </script>
</body>
</html>
