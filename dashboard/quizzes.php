<?php
require_once '../config/config.php';
requireAuth();

$current_user = getCurrentUser();
$is_admin = isAdmin();
$db = getDB();

// Listar quizzes (optimizado - solo campos necesarios)
$query = $is_admin 
    ? "SELECT q.id, q.code, q.title, q.description, q.created_at, u.username, u.full_name FROM quizzes q LEFT JOIN users u ON q.created_by = u.id ORDER BY q.created_at DESC LIMIT 100"
    : "SELECT q.id, q.code, q.title, q.description, q.created_at, u.username, u.full_name FROM quizzes q LEFT JOIN users u ON q.created_by = u.id WHERE q.created_by = ? ORDER BY q.created_at DESC LIMIT 100";
$stmt = $db->prepare($query);
if ($is_admin) {
    $stmt->execute();
} else {
    $stmt->execute([$current_user['id']]);
}
$quizzes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/gamified.css">
</head>
<body style="background: #FAFAFA;">
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="ml-64 pt-4 p-8">
        <div class="container mx-auto">
            <div class="flex items-center justify-between mb-6 slide-up">
                <div class="flex items-center space-x-3">
                    <span style="font-size: 48px;">❓</span>
                    <h1 style="font-size: 32px; font-weight: 900; color: var(--gray-900);">Mis Quizzes</h1>
                </div>
                <button onclick="openQuizModal()" class="btn-game btn-blue">
                    ➕ CREAR QUIZ
                </button>
            </div>
            
            <div class="card-game slide-up">
                <?php if (empty($quizzes)): ?>
                    <div class="text-center py-12">
                        <div class="emoji-sticker" style="font-size: 80px; margin-bottom: 16px;">📭</div>
                        <p style="font-size: 20px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px;">No hay quizzes aún</p>
                        <p style="font-size: 16px; font-weight: 600; color: var(--gray-500); margin-bottom: 24px;">¡Crea tu primer quiz y comienza!</p>
                        <button onclick="openQuizModal()" class="btn-game btn-blue">
                            ✨ CREAR PRIMER QUIZ
                        </button>
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
                                <?php foreach ($quizzes as $index => $quiz): ?>
                                <tr class="slide-in-right" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                                    <td class="px-6 py-4">
                                        <span class="font-mono text-sm font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent"><?php echo htmlspecialchars($quiz['code']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-gray-800"><?php echo htmlspecialchars($quiz['title']); ?></td>
                                    <?php if ($is_admin): ?>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($quiz['full_name'] ?? $quiz['username']); ?></td>
                                    <?php endif; ?>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?php echo date('d/m/Y H:i', strtotime($quiz['created_at'])); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <a href="quiz-view.php?id=<?php echo $quiz['id']; ?>" class="w-9 h-9 flex items-center justify-center rounded-lg bg-purple-100 text-purple-600 hover:bg-purple-200 hover:scale-110 transition-all duration-300 icon-hover" title="Ver detalles">
                                                <i class="fas fa-eye text-sm"></i>
                                            </a>
                                            <a href="qr-codes.php?generate=quiz&id=<?php echo $quiz['id']; ?>" class="w-9 h-9 flex items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 hover:bg-emerald-200 hover:scale-110 transition-all duration-300 icon-hover" title="Generar QR">
                                                <i class="fas fa-qrcode text-sm"></i>
                                            </a>
                                            <button onclick="editQuiz(<?php echo $quiz['id']; ?>)" class="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 hover:scale-110 transition-all duration-300 icon-hover" title="Editar">
                                                <i class="fas fa-edit text-sm"></i>
                                            </button>
                                            <button onclick="deleteQuiz(<?php echo $quiz['id']; ?>)" class="w-9 h-9 flex items-center justify-center rounded-lg bg-red-100 text-red-600 hover:bg-red-200 hover:scale-110 transition-all duration-300 icon-hover" title="Eliminar">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
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

    <!-- Modal para Crear/Editar Quiz -->
    <div id="quizModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="modal-game max-w-2xl w-full max-h-[90vh] overflow-y-auto bounce-in">
            <div class="modal-header">
                <div class="flex items-center justify-between">
                    <h2 id="modalTitle" class="modal-title flex items-center">
                        <span style="font-size: 28px; margin-right: 12px;">❓</span>
                        <span id="modalTitleText">CREAR QUIZ</span>
                    </h2>
                    <button onclick="closeQuizModal()" class="text-white hover:opacity-80 transition-opacity" style="font-size: 28px; font-weight: 700;">
                        ✕
                    </button>
                </div>
            </div>
            
            <form id="quizForm" style="padding: 32px;">
                <input type="hidden" id="quiz_id" name="quiz_id" value="0">
                <input type="hidden" id="code" name="code" value="">
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        ⭐ Puntos por Pregunta
                    </label>
                    <input type="number" id="points_per_question" name="points_per_question" value="100" min="1" class="input-game">
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        ✏️ Título *
                    </label>
                    <input type="text" id="title" name="title" required class="input-game" placeholder="Ej: Quiz de Matemáticas">
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        📝 Descripción
                    </label>
                    <textarea id="description" name="description" rows="4" class="input-game" placeholder="Describe tu quiz..."></textarea>
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
    
    <script>
        function openQuizModal(quizId = null) {
            const modal = document.getElementById('quizModal');
            const form = document.getElementById('quizForm');
            const title = document.getElementById('modalTitle');
            
            // Resetear formulario
            form.reset();
            document.getElementById('quiz_id').value = '0';
            document.getElementById('points_per_question').value = '100';
            
            if (quizId) {
                title.innerHTML = '<i class="fas fa-edit mr-2"></i>Editar Quiz';
                loadQuizData(quizId);
            } else {
                title.innerHTML = '<i class="fas fa-plus mr-2"></i>Crear Nuevo Quiz';
            }
            
            modal.classList.remove('hidden');
        }
        
        function closeQuizModal() {
            document.getElementById('quizModal').classList.add('hidden');
        }
        
        async function loadQuizData(quizId) {
            try {
                const response = await fetch(`../api/get-quiz.php?id=${quizId}`);
                const result = await response.json();
                
                if (result.success) {
                    const quiz = result.quiz;
                    document.getElementById('quiz_id').value = quiz.id;
                    document.getElementById('code').value = quiz.code;
                    document.getElementById('title').value = quiz.title;
                    document.getElementById('description').value = quiz.description || '';
                    document.getElementById('points_per_question').value = quiz.points_per_question;
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar los datos del quiz',
                    confirmButtonColor: '#3b82f6'
                });
            }
        }
        
        async function editQuiz(id) {
            openQuizModal(id);
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
        
        // Cerrar modal al hacer click fuera
        document.getElementById('quizModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeQuizModal();
            }
        });
        
        async function deleteQuiz(id) {
            const result = await Swal.fire({
                title: '¿Estás seguro?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });
            
            if (result.isConfirmed) {
                const response = await fetch('../api/delete-quiz.php?id=' + id, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'Quiz eliminado correctamente',
                        confirmButtonColor: '#3b82f6',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al eliminar',
                        confirmButtonColor: '#3b82f6'
                    });
                }
            }
        }
    </script>
</body>
</html>