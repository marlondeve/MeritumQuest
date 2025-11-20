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
    <?php include '../includes/qr-modal.php'; ?>
</head>
<body style="background: #FAFAFA;">
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="ml-64 pt-4 p-8">
        <div class="container mx-auto">
            <div class="mb-6 slide-up">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <img src="<?php echo APP_URL; ?>/assets/avatar/13.png" alt="Quizzes" style="width: 60px; height: auto; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.1));">
                        <h1 style="font-size: 32px; font-weight: 900; color: var(--gray-900);">Mis Quizzes</h1>
                    </div>
                    <button onclick="openQuizModal()" class="btn-game btn-blue">
                        ➕ CREAR QUIZ
                    </button>
                </div>
                <div class="card-game" style="background: var(--pastel-blue); border-color: var(--duo-blue); padding: 16px; margin-bottom: 16px;">
                    <div class="flex items-start space-x-3">
                        <img src="<?php echo APP_URL; ?>/assets/avatar/14.png" alt="Info" style="width: 50px; height: auto; flex-shrink: 0;">
                        <div>
                            <p style="font-size: 14px; font-weight: 600; color: var(--gray-700); line-height: 1.6;">
                                <strong>¿Qué es esta sección?</strong><br>
                                Aquí puedes gestionar todos tus quizzes. Crea cuestionarios interactivos con preguntas de opción múltiple, configura puntos por pregunta, y genera códigos únicos para compartir. Cada quiz puede tener múltiples preguntas y opciones de respuesta correcta.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-game slide-up">
                <?php if (empty($quizzes)): ?>
                    <div class="text-center py-12">
                        <img src="<?php echo APP_URL; ?>/assets/avatar/14.png" alt="Sin quizzes" style="width: 120px; height: auto; margin: 0 auto 16px; display: block; filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1));">
                        <p style="font-size: 20px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px;">No hay quizzes aún</p>
                        <p style="font-size: 16px; font-weight: 600; color: var(--gray-500); margin-bottom: 24px;">¡Crea tu primer quiz y comienza a evaluar!</p>
                        <button onclick="openQuizModal()" class="btn-game btn-blue">
                            ✨ CREAR PRIMER QUIZ
                        </button>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="table-game">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Título</th>
                                    <?php if ($is_admin): ?>
                                    <th>Creado por</th>
                                    <?php endif; ?>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quizzes as $index => $quiz): ?>
                                <tr class="bounce-in quiz-row" 
                                    style="animation-delay: <?php echo $index * 0.05; ?>s; cursor: pointer;"
                                    onclick="window.location.href='quiz-view.php?id=<?php echo $quiz['id']; ?>'"
                                    onmouseover="this.style.backgroundColor='var(--pastel-blue)'; this.style.transform='translateX(4px)';"
                                    onmouseout="this.style.backgroundColor='white'; this.style.transform='translateX(0)';">
                                    <td>
                                        <span style="font-family: monospace; font-size: 13px; font-weight: 700; color: var(--duo-blue);"><?php echo htmlspecialchars($quiz['code']); ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <div style="font-size: 16px; font-weight: 700; color: var(--gray-900); margin-bottom: 4px;"><?php echo htmlspecialchars($quiz['title']); ?></div>
                                            <?php if (!empty($quiz['description'])): ?>
                                            <div style="font-size: 12px; font-weight: 600; color: var(--gray-500); line-height: 1.3;"><?php echo htmlspecialchars(mb_substr($quiz['description'], 0, 60)) . (mb_strlen($quiz['description']) > 60 ? '...' : ''); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php if ($is_admin): ?>
                                    <td style="font-size: 14px; font-weight: 600; color: var(--gray-700);"><?php echo htmlspecialchars($quiz['full_name'] ?? $quiz['username']); ?></td>
                                    <?php endif; ?>
                                    <td style="font-size: 13px; font-weight: 600; color: var(--gray-600);"><?php echo date('d/m/Y', strtotime($quiz['created_at'])); ?></td>
                                    <td onclick="event.stopPropagation();">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="event.stopPropagation(); openQRModal('quiz', <?php echo $quiz['id']; ?>);" 
                                                    class="w-9 h-9 flex items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 hover:bg-emerald-200 hover:scale-110 transition-all duration-300" 
                                                    title="Ver QR" 
                                                    style="box-shadow: 0 2px 0 rgba(0,0,0,0.1);">
                                                <i class="fas fa-qrcode text-sm"></i>
                                            </button>
                                            <button onclick="event.stopPropagation(); editQuiz(<?php echo $quiz['id']; ?>);" 
                                                    class="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 hover:scale-110 transition-all duration-300" 
                                                    title="Editar" 
                                                    style="box-shadow: 0 2px 0 rgba(0,0,0,0.1);">
                                                <i class="fas fa-edit text-sm"></i>
                                            </button>
                                            <button onclick="event.stopPropagation(); deleteQuiz(<?php echo $quiz['id']; ?>);" 
                                                    class="w-9 h-9 flex items-center justify-center rounded-lg bg-red-100 text-red-600 hover:bg-red-200 hover:scale-110 transition-all duration-300" 
                                                    title="Eliminar" 
                                                    style="box-shadow: 0 2px 0 rgba(0,0,0,0.1);">
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
                    <div class="flex items-center space-x-3">
                        <img src="<?php echo APP_URL; ?>/assets/avatar/10.png" alt="Crear Quiz" style="width: 50px; height: auto; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.2));">
                        <h2 id="modalTitle" class="modal-title">
                            <span id="modalTitleText">CREAR QUIZ</span>
                        </h2>
                    </div>
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
                document.getElementById('modalTitleText').textContent = 'EDITAR QUIZ';
                loadQuizData(quizId);
            } else {
                document.getElementById('modalTitleText').textContent = 'CREAR QUIZ';
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