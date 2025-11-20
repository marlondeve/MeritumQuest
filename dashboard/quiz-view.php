<?php
require_once '../config/config.php';
requireAuth();

$current_user = getCurrentUser();
$is_admin = isAdmin();
$db = getDB();

$quiz_id = intval($_GET['id'] ?? 0);

if ($quiz_id <= 0) {
    header('Location: quizzes.php');
    exit;
}

// Obtener quiz
$query = $is_admin 
    ? "SELECT q.*, u.username, u.full_name FROM quizzes q LEFT JOIN users u ON q.created_by = u.id WHERE q.id = ?"
    : "SELECT q.*, u.username, u.full_name FROM quizzes q LEFT JOIN users u ON q.created_by = u.id WHERE q.id = ? AND q.created_by = ?";
$stmt = $db->prepare($query);
if ($is_admin) {
    $stmt->execute([$quiz_id]);
} else {
    $stmt->execute([$quiz_id, $current_user['id']]);
}

$quiz = $stmt->fetch();

if (!$quiz) {
    header('Location: quizzes.php');
    exit;
}

// Obtener preguntas del quiz
$stmt = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY question_order ASC");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

// Obtener estadísticas
$statsStmt = $db->prepare("SELECT COUNT(*) as total FROM quiz_attempts WHERE quiz_id = ?");
$statsStmt->execute([$quiz_id]);
$total_attempts = $statsStmt->fetch()['total'];

$qrStmt = $db->prepare("SELECT * FROM qr_codes WHERE entity_type = 'quiz' AND entity_id = ? ORDER BY created_at DESC LIMIT 1");
$qrStmt->execute([$quiz_id]);
$qr_code = $qrStmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title']); ?> - <?php echo APP_NAME; ?></title>
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
            <!-- Header con Mascota -->
            <div class="card-game mb-6 slide-up" style="background: linear-gradient(135deg, #1CB0F6 0%, #4FC3F7 100%); color: white; border-color: #1391C4;">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-4 mb-4">
                            <a href="quizzes.php" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white bg-opacity-20 text-white hover:bg-opacity-30 hover:scale-110 transition-all duration-300" style="backdrop-filter: blur(10px);">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div>
                                <h1 style="font-size: 32px; font-weight: 900; margin-bottom: 4px;"><?php echo htmlspecialchars($quiz['title']); ?></h1>
                                <p style="font-size: 14px; font-weight: 600; opacity: 0.9;">Código: <span style="font-family: monospace; font-weight: 700;"><?php echo htmlspecialchars($quiz['code']); ?></span></p>
                            </div>
                        </div>
                        <p style="font-size: 16px; font-weight: 600; opacity: 0.95; margin-bottom: 16px;"><?php echo htmlspecialchars($quiz['description'] ?? 'Sin descripción'); ?></p>
                        <div class="flex flex-wrap gap-4 text-sm" style="opacity: 0.9;">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-calendar"></i>
                                <span>Creado: <?php echo date('d/m/Y H:i', strtotime($quiz['created_at'])); ?></span>
                            </div>
                            <?php if ($is_admin): ?>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-user"></i>
                                <span>Por: <?php echo htmlspecialchars($quiz['full_name'] ?? $quiz['username']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex flex-col items-end space-y-3">
                        <img src="<?php echo APP_URL; ?>/assets/avatar/2.png" alt="Quiz Mascot" style="width: 120px; height: auto; filter: drop-shadow(0 5px 15px rgba(0,0,0,0.2));">
                        <div class="flex space-x-2">
                            <button onclick="openQRModal('quiz', <?php echo $quiz['id']; ?>)" class="btn-game btn-green" style="padding: 10px 20px; font-size: 12px;">
                                🔳 VER QR
                            </button>
                            <button onclick="editQuiz(<?php echo $quiz['id']; ?>)" class="btn-game" style="background: white; color: var(--duo-blue); padding: 10px 20px; font-size: 12px; box-shadow: 0 3px 0 rgba(255,255,255,0.3);">
                                ✏️ EDITAR
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Información Contextual -->
            <div class="card-game mb-6 slide-up" style="background: var(--pastel-blue); border-color: var(--duo-blue);">
                <div class="flex items-start space-x-4">
                    <img src="<?php echo APP_URL; ?>/assets/avatar/13.png" alt="Info" style="width: 80px; height: auto; flex-shrink: 0; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.1));">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 900; color: var(--gray-900); margin-bottom: 8px;">📝 Gestión de Preguntas</h3>
                        <p style="font-size: 14px; font-weight: 600; color: var(--gray-700); line-height: 1.6;">
                            En esta sección puedes agregar y editar las preguntas de tu quiz. Cada pregunta puede tener múltiples opciones de respuesta y puedes configurar si se permiten múltiples respuestas correctas. ¡Haz que tu quiz sea completo y desafiante!
                        </p>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="stat-game blue bounce-in" style="animation-delay: 0.1s;">
                    <div class="icon-game">
                        <span style="font-size: 32px;">⭐</span>
                    </div>
                    <div class="number"><?php echo $quiz['points_per_question']; ?></div>
                    <div class="label">Puntos por Pregunta</div>
                </div>

                <div class="stat-game green bounce-in" style="animation-delay: 0.2s;">
                    <div class="icon-game">
                        <span style="font-size: 32px;">❓</span>
                    </div>
                    <div class="number"><?php echo count($questions); ?></div>
                    <div class="label">Total de Preguntas</div>
                </div>

                <div class="stat-game yellow bounce-in" style="animation-delay: 0.3s;">
                    <div class="icon-game">
                        <span style="font-size: 32px;">📊</span>
                    </div>
                    <div class="number"><?php echo $total_attempts; ?></div>
                    <div class="label">Intentos Totales</div>
                </div>
            </div>

            <!-- Configuración -->
            <div class="card-game mb-6 slide-up">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-lg flex items-center justify-center" style="box-shadow: 0 3px 0 rgba(0,0,0,0.1);">
                        <i class="fas fa-cog text-white"></i>
                    </div>
                    <h2 style="font-size: 20px; font-weight: 900; color: var(--gray-900);">⚙️ Configuración del Quiz</h2>
                </div>
                <div class="flex items-center space-x-3 p-4" style="background: var(--pastel-yellow); border: 2px solid var(--duo-yellow); border-radius: 16px;">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg flex items-center justify-center" style="box-shadow: 0 3px 0 rgba(0,0,0,0.1);">
                        <span style="font-size: 24px;">⭐</span>
                    </div>
                    <div>
                        <p style="font-size: 14px; font-weight: 700; color: var(--gray-900);">Puntos por pregunta</p>
                        <p style="font-size: 12px; font-weight: 600; color: var(--gray-700);"><?php echo $quiz['points_per_question']; ?> puntos</p>
                    </div>
                </div>
            </div>

            <!-- Preguntas -->
            <div class="card-game slide-up">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-lg flex items-center justify-center" style="box-shadow: 0 3px 0 rgba(0,0,0,0.1);">
                            <span style="font-size: 20px;">❓</span>
                        </div>
                        <h2 style="font-size: 24px; font-weight: 900; color: var(--gray-900);">
                            Preguntas <span style="color: var(--gray-700); font-size: 18px;">(<?php echo count($questions); ?>)</span>
                        </h2>
                    </div>
                    <button onclick="openQuestionModal(<?php echo $quiz_id; ?>)" class="btn-game btn-blue">
                        ➕ AGREGAR PREGUNTA
                    </button>
                </div>
                
                <?php if (empty($questions)): ?>
                    <div class="text-center py-12">
                        <img src="<?php echo APP_URL; ?>/assets/avatar/14.png" alt="Sin preguntas" style="width: 120px; height: auto; margin: 0 auto 16px; display: block; filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1));">
                        <p style="font-size: 20px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px;">No hay preguntas aún</p>
                        <p style="font-size: 16px; font-weight: 600; color: var(--gray-500); margin-bottom: 24px;">Agrega preguntas para que el quiz esté completo</p>
                        <button onclick="openQuestionModal(<?php echo $quiz_id; ?>)" class="btn-game btn-blue">
                            ✨ CREAR PRIMERA PREGUNTA
                        </button>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($questions as $index => $question): 
                            // Obtener opciones de la pregunta
                            $optStmt = $db->prepare("SELECT * FROM quiz_question_options WHERE question_id = ? ORDER BY option_order ASC");
                            $optStmt->execute([$question['id']]);
                            $options = $optStmt->fetchAll();
                        ?>
                        <div class="card-game bounce-in" style="animation-delay: <?php echo $index * 0.1; ?>s; border-left: 4px solid var(--duo-blue);">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <span class="badge-game blue" style="font-size: 12px;">Pregunta <?php echo $index + 1; ?></span>
                                        <?php if ($question['allow_multiple_answers']): ?>
                                        <span class="badge-game green" style="font-size: 11px;">🔄 Múltiples respuestas</span>
                                        <?php endif; ?>
                                    </div>
                                    <p style="font-size: 18px; font-weight: 700; color: var(--gray-900); margin-bottom: 12px; line-height: 1.4;"><?php echo htmlspecialchars($question['text']); ?></p>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="openQuestionModal(<?php echo $quiz_id; ?>, <?php echo $question['id']; ?>)" class="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 hover:scale-110 transition-all duration-300" title="Editar" style="box-shadow: 0 2px 0 rgba(0,0,0,0.1);">
                                        <i class="fas fa-edit text-sm"></i>
                                    </button>
                                    <button onclick="deleteQuestion(<?php echo $question['id']; ?>)" class="w-9 h-9 flex items-center justify-center rounded-lg bg-red-100 text-red-600 hover:bg-red-200 hover:scale-110 transition-all duration-300" title="Eliminar" style="box-shadow: 0 2px 0 rgba(0,0,0,0.1);">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <?php if (!empty($options)): ?>
                            <div style="margin-left: 16px; padding-left: 16px; border-left: 3px solid var(--gray-200);">
                                <div class="space-y-2">
                                    <?php foreach ($options as $opt): ?>
                                    <div class="flex items-center space-x-3 p-3 rounded-lg" style="background: <?php echo $opt['is_correct'] ? 'var(--pastel-green)' : 'var(--gray-100)'; ?>; border: 2px solid <?php echo $opt['is_correct'] ? 'var(--duo-green)' : 'var(--gray-200)'; ?>;">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: <?php echo $opt['is_correct'] ? 'var(--duo-green)' : 'var(--gray-300)'; ?>; color: white; font-weight: 700; flex-shrink: 0;">
                                            <?php echo $opt['is_correct'] ? '✓' : '○'; ?>
                                        </div>
                                        <span style="font-size: 14px; font-weight: <?php echo $opt['is_correct'] ? '700' : '600'; ?>; color: <?php echo $opt['is_correct'] ? 'var(--gray-900)' : 'var(--gray-700)'; ?>;">
                                            <?php echo htmlspecialchars($opt['text']); ?>
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <p style="font-size: 14px; font-weight: 600; color: var(--gray-500); font-style: italic; margin-left: 16px;">Sin opciones definidas</p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal para Agregar/Editar Pregunta -->
    <div id="questionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="modal-game max-w-4xl w-full max-h-[90vh] overflow-y-auto bounce-in">
            <div class="modal-header">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <img src="<?php echo APP_URL; ?>/assets/avatar/10.png" alt="Agregar Pregunta" style="width: 50px; height: auto; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.2));">
                        <h2 id="questionModalTitle" class="modal-title flex items-center">
                            <span id="questionTitleText">AGREGAR PREGUNTA</span>
                        </h2>
                    </div>
                    <button onclick="closeQuestionModal()" class="text-white hover:opacity-80 transition-opacity" style="font-size: 28px; font-weight: 700;">
                        ✕
                    </button>
                </div>
            </div>
            
            <form id="questionForm" style="padding: 32px;">
                <input type="hidden" id="question_id" name="question_id" value="0">
                <input type="hidden" id="quiz_id_question" name="quiz_id" value="<?php echo $quiz_id; ?>">
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        ❓ Pregunta *
                    </label>
                    <textarea id="question_text" name="text" rows="3" required class="input-game" placeholder="Escribe aquí la pregunta..."></textarea>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; padding: 16px; background: var(--pastel-blue); border: 2px solid var(--duo-blue); border-radius: 16px; cursor: pointer; font-weight: 700;">
                        <input type="checkbox" id="question_multiple" name="allow_multiple_answers" value="1" onchange="toggleMultipleAnswers()" style="width: 20px; height: 20px; margin-right: 12px;">
                        <span style="font-size: 16px;">🔄 Permitir múltiples respuestas</span>
                    </label>
                </div>
                
                <!-- Opciones de Respuesta -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); text-transform: uppercase; letter-spacing: 0.5px;">
                            📋 Opciones de Respuesta *
                        </label>
                        <button type="button" onclick="addOption()" class="btn-game btn-green" style="padding: 8px 16px; font-size: 12px;">
                            ➕ AGREGAR OPCIÓN
                        </button>
                    </div>
                    <div id="optionsContainer" style="display: flex; flex-direction: column; gap: 12px;">
                        <!-- Las opciones se agregarán aquí dinámicamente -->
                    </div>
                    <div class="mt-4 p-3 rounded-lg" style="background: var(--pastel-blue); border: 2px solid var(--duo-blue);">
                        <p style="font-size: 12px; font-weight: 600; color: var(--gray-700); line-height: 1.5;">
                            💡 <strong>Tip:</strong> Agrega al menos 2 opciones. Marca las opciones correctas según el tipo de pregunta (una sola o múltiples).
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 16px; margin-top: 32px; padding-top: 24px; border-top: 2px solid var(--gray-200);">
                    <button type="submit" class="flex-1 btn-game btn-blue">
                        💾 GUARDAR PREGUNTA
                    </button>
                    <button type="button" onclick="closeQuestionModal()" style="flex: 1; background: var(--gray-200); color: var(--gray-700); padding: 14px 24px; border-radius: 16px; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.15s ease;">
                        CANCELAR
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let optionCount = 0;
        const quizId = <?php echo $quiz_id; ?>;
        
        function openQuestionModal(quizId, questionId = null) {
            const modal = document.getElementById('questionModal');
            const form = document.getElementById('questionForm');
            const title = document.getElementById('questionModalTitle');
            
            // Resetear formulario
            form.reset();
            document.getElementById('question_id').value = '0';
            document.getElementById('quiz_id_question').value = quizId;
            document.getElementById('optionsContainer').innerHTML = '';
            optionCount = 0;
            
            if (questionId) {
                document.getElementById('questionTitleText').textContent = 'EDITAR PREGUNTA';
                loadQuestionData(questionId);
            } else {
                document.getElementById('questionTitleText').textContent = 'AGREGAR PREGUNTA';
                addOption(); // Agregar primera opción por defecto
                addOption(); // Agregar segunda opción
                toggleMultipleAnswers(); // Aplicar la lógica inicial
            }
            
            modal.classList.remove('hidden');
        }
        
        function closeQuestionModal() {
            document.getElementById('questionModal').classList.add('hidden');
        }
        
        function addOption(isCorrect = false, text = '') {
            optionCount++;
            const container = document.getElementById('optionsContainer');
            const optionDiv = document.createElement('div');
            optionDiv.style.cssText = 'display: flex; align-items: center; gap: 12px; padding: 16px; background: white; border: 2px solid var(--gray-200); border-radius: 16px; transition: all 0.2s ease;';
            optionDiv.onmouseenter = function() { this.style.borderColor = 'var(--duo-blue)'; this.style.boxShadow = '0 3px 0 var(--gray-200)'; };
            optionDiv.onmouseleave = function() { this.style.borderColor = 'var(--gray-200)'; this.style.boxShadow = 'none'; };
            const multipleAllowed = document.getElementById('question_multiple').checked;
            const inputType = multipleAllowed ? 'checkbox' : 'radio';
            const inputName = multipleAllowed ? `option-correct-${optionCount}` : 'option-correct-single';
            optionDiv.innerHTML = `
                <div style="flex: 1;">
                    <input type="text" class="option-text input-game" 
                        placeholder="Escribe la opción de respuesta..." value="${text}" required style="font-size: 14px;">
                </div>
                <label style="display: flex; align-items: center; padding: 10px 16px; background: ${isCorrect ? 'var(--pastel-green)' : 'var(--gray-100)'}; border: 2px solid ${isCorrect ? 'var(--duo-green)' : 'var(--gray-300)'}; border-radius: 12px; cursor: pointer; font-weight: 700; font-size: 12px; white-space: nowrap; transition: all 0.2s ease; box-shadow: 0 2px 0 ${isCorrect ? '#46A302' : 'var(--gray-300)'};">
                    <input type="${inputType}" name="${inputName}" class="option-correct" ${isCorrect ? 'checked' : ''} onchange="handleCorrectChange(this)" style="width: 18px; height: 18px; margin-right: 8px; cursor: pointer;">
                    <span>${isCorrect ? '✅' : '☑️'} CORRECTA</span>
                </label>
                <button type="button" onclick="removeOption(this)" class="btn-game btn-red" style="padding: 10px; min-width: 40px; font-size: 14px;">
                    🗑️
                </button>
            `;
            container.appendChild(optionDiv);
        }
        
        function toggleMultipleAnswers() {
            const multipleAllowed = document.getElementById('question_multiple').checked;
            const correctInputs = document.querySelectorAll('.option-correct');
            
            correctInputs.forEach((input, index) => {
                const label = input.closest('label');
                if (multipleAllowed) {
                    // Cambiar a checkbox y nombres únicos
                    input.type = 'checkbox';
                    input.name = `option-correct-${Date.now()}-${Math.random()}`;
                } else {
                    // Cambiar a radio con mismo nombre (solo una seleccionada)
                    input.type = 'radio';
                    input.name = 'option-correct-single';
                    // Si hay más de una marcada, dejar solo la primera
                    const checked = document.querySelectorAll('.option-correct:checked');
                    if (checked.length > 1) {
                        for (let i = 1; i < checked.length; i++) {
                            checked[i].checked = false;
                            updateOptionStyle(checked[i]);
                        }
                    }
                }
                updateOptionStyle(input);
            });
        }
        
        function updateOptionStyle(input) {
            const label = input.closest('label');
            if (input.checked) {
                label.style.background = 'var(--pastel-green)';
                label.style.borderColor = 'var(--duo-green)';
                label.style.boxShadow = '0 2px 0 #46A302';
                label.querySelector('span').textContent = '✅ CORRECTA';
            } else {
                label.style.background = 'var(--gray-100)';
                label.style.borderColor = 'var(--gray-300)';
                label.style.boxShadow = '0 2px 0 var(--gray-300)';
                label.querySelector('span').textContent = '☑️ CORRECTA';
            }
        }
        
        function handleCorrectChange(changedInput) {
            const multipleAllowed = document.getElementById('question_multiple').checked;
            
            updateOptionStyle(changedInput);
            
            if (!multipleAllowed && changedInput.checked) {
                // Si no se permiten múltiples, desmarcar todas las demás
                const allCorrectInputs = document.querySelectorAll('.option-correct');
                allCorrectInputs.forEach(input => {
                    if (input !== changedInput) {
                        input.checked = false;
                        updateOptionStyle(input);
                    }
                });
            }
        }
        
        function removeOption(button) {
            const container = document.getElementById('optionsContainer');
            // Buscar el div padre que contiene la opción
            let optionDiv = button.parentElement;
            while (optionDiv && optionDiv !== container && !optionDiv.querySelector('.option-text')) {
                optionDiv = optionDiv.parentElement;
            }
            
            if (container.children.length > 1 && optionDiv && optionDiv !== container) {
                optionDiv.remove();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Debe haber al menos una opción',
                    confirmButtonColor: '#3b82f6',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        }
        
        async function loadQuestionData(questionId) {
            try {
                const response = await fetch(`../api/get-question.php?id=${questionId}`);
                const result = await response.json();
                
                if (result.success) {
                    const question = result.question;
                    document.getElementById('question_id').value = question.id;
                    document.getElementById('question_text').value = question.text;
                    document.getElementById('question_multiple').checked = question.allow_multiple_answers == 1;
                    toggleMultipleAnswers(); // Aplicar la lógica de múltiples respuestas
                    
                    // Cargar opciones
                    document.getElementById('optionsContainer').innerHTML = '';
                    if (question.options && question.options.length > 0) {
                        question.options.forEach(opt => {
                            addOption(opt.is_correct == 1, opt.text);
                        });
                    } else {
                        addOption();
                        addOption();
                    }
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar la pregunta',
                    confirmButtonColor: '#3b82f6'
                });
            }
        }
        
        document.getElementById('questionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validar que haya al menos una opción
            const options = [];
            const container = document.getElementById('optionsContainer');
            
            // Buscar todos los inputs de opciones directamente
            const textInputs = container.querySelectorAll('.option-text');
            
            if (textInputs.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Debe agregar al menos una opción',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
            
            // Recorrer cada input de texto y encontrar su contenedor y checkbox
            textInputs.forEach(textInput => {
                const optionDiv = textInput.closest('div[style*="display: flex"]') || textInput.closest('div');
                const correctCheckbox = optionDiv ? optionDiv.querySelector('.option-correct') : null;
                
                if (textInput && textInput.value.trim()) {
                    options.push({
                        text: textInput.value.trim(),
                        is_correct: correctCheckbox ? correctCheckbox.checked : false
                    });
                }
            });
            
            if (options.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Debe agregar al menos una opción válida',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
            
            // Validar que haya al menos una respuesta correcta
            const hasCorrect = options.some(opt => opt.is_correct);
            if (!hasCorrect) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Debe marcar al menos una opción como correcta',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
            
            // Validar que si no permite múltiples respuestas, solo haya una correcta
            const multipleAllowed = document.getElementById('question_multiple').checked;
            const correctCount = options.filter(opt => opt.is_correct).length;
            
            if (!multipleAllowed && correctCount > 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Solo puede marcar una opción como correcta cuando "Permitir múltiples respuestas" está desactivado',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
            
            const formData = new FormData(this);
            formData.append('question_id', document.getElementById('question_id').value);
            formData.append('options', JSON.stringify(options));
            
            try {
                const response = await fetch('../api/save-question.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: result.message,
                        confirmButtonColor: '#3b82f6',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        closeQuestionModal();
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
                    text: 'Error al guardar la pregunta',
                    confirmButtonColor: '#3b82f6'
                });
            }
        });
        
        async function deleteQuestion(questionId) {
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
                try {
                    const response = await fetch(`../api/delete-question.php?id=${questionId}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminado',
                            text: 'Pregunta eliminada correctamente',
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
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al eliminar la pregunta',
                        confirmButtonColor: '#3b82f6'
                    });
                }
            }
        }
        
        // Cerrar modal al hacer click fuera
        document.getElementById('questionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeQuestionModal();
            }
        });
        
        function editQuiz(id) {
            window.location.href = `quizzes.php`;
            setTimeout(() => {
                if (window.opener) {
                    window.opener.editQuiz(id);
                    window.close();
                }
            }, 100);
        }
    </script>
</body>
</html>
