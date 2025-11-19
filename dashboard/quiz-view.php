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
</head>
<body style="background: #FAFAFA;">
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="ml-64 pt-4 p-8">
        <div class="container mx-auto max-w-6xl">
            <!-- Header -->
            <div class="glass-card rounded-2xl p-6 mb-6 fade-in-up">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <div class="flex items-center space-x-4 mb-3">
                            <a href="quizzes.php" class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 hover:scale-110 transition-all duration-300 icon-hover">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div>
                                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent"><?php echo htmlspecialchars($quiz['title']); ?></h1>
                                <p class="text-sm text-gray-500 mt-1">Código: <span class="font-mono font-semibold text-blue-600"><?php echo htmlspecialchars($quiz['code']); ?></span></p>
                            </div>
                        </div>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($quiz['description'] ?? 'Sin descripción'); ?></p>
                        <div class="flex flex-wrap gap-4 text-sm">
                            <div class="flex items-center space-x-2">
                                <span class="font-mono text-blue-600 font-semibold"><?php echo htmlspecialchars($quiz['code']); ?></span>
                            </div>
                            <div class="flex items-center space-x-2 text-gray-600">
                                <i class="fas fa-calendar"></i>
                                <span>Creado: <?php echo date('d/m/Y H:i', strtotime($quiz['created_at'])); ?></span>
                            </div>
                            <?php if ($is_admin): ?>
                            <div class="flex items-center space-x-2 text-gray-600">
                                <i class="fas fa-user"></i>
                                <span>Por: <?php echo htmlspecialchars($quiz['full_name'] ?? $quiz['username']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="qr-codes.php?generate=quiz&id=<?php echo $quiz['id']; ?>" class="modern-btn bg-gradient-to-r from-emerald-500 to-teal-500 text-white px-5 py-2.5 rounded-xl shadow-lg">
                            <i class="fas fa-qrcode mr-2"></i>Ver QR
                        </a>
                        <button onclick="editQuiz(<?php echo $quiz['id']; ?>)" class="modern-btn bg-gradient-to-r from-blue-500 to-purple-500 text-white px-5 py-2.5 rounded-xl shadow-lg">
                            <i class="fas fa-edit mr-2"></i>Editar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="stat-card p-6 text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-10 rounded-full -mr-12 -mt-12"></div>
                    <div class="flex items-center justify-between relative z-10">
                        <div>
                            <p class="text-white text-opacity-80 text-xs font-medium mb-2 uppercase tracking-wide">Puntos por Pregunta</p>
                            <p class="text-4xl font-bold"><?php echo $quiz['points_per_question']; ?></p>
                        </div>
                        <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm transform hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-star text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card p-6 text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-10 rounded-full -mr-12 -mt-12"></div>
                    <div class="flex items-center justify-between relative z-10">
                        <div>
                            <p class="text-white text-opacity-80 text-xs font-medium mb-2 uppercase tracking-wide">Total de Preguntas</p>
                            <p class="text-4xl font-bold"><?php echo count($questions); ?></p>
                        </div>
                        <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm transform hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-question-circle text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card p-6 text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-10 rounded-full -mr-12 -mt-12"></div>
                    <div class="flex items-center justify-between relative z-10">
                        <div>
                            <p class="text-white text-opacity-80 text-xs font-medium mb-2 uppercase tracking-wide">Intentos Totales</p>
                            <p class="text-4xl font-bold"><?php echo $total_attempts; ?></p>
                        </div>
                        <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm transform hover:rotate-12 transition-transform duration-300">
                            <i class="fas fa-chart-line text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración -->
            <div class="glass-card rounded-2xl p-6 mb-6 fade-in-up">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-cog text-white"></i>
                    </div>
                    <h2 class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Configuración</h2>
                </div>
                <div class="flex items-center space-x-3 p-4 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl border border-yellow-200">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-star text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Puntos por pregunta</p>
                        <p class="text-xs text-gray-600"><?php echo $quiz['points_per_question']; ?> puntos</p>
                    </div>
                </div>
            </div>

            <!-- Preguntas -->
            <div class="glass-card rounded-2xl p-6 fade-in-up">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-list text-white"></i>
                        </div>
                        <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            Preguntas <span class="text-gray-600">(<?php echo count($questions); ?>)</span>
                        </h2>
                    </div>
                    <button onclick="openQuestionModal(<?php echo $quiz_id; ?>)" class="modern-btn bg-gradient-to-r from-blue-500 to-purple-500 text-white px-5 py-2.5 rounded-xl shadow-lg text-sm font-semibold">
                        <i class="fas fa-plus mr-2"></i>Agregar Pregunta
                    </button>
                </div>
                
                <?php if (empty($questions)): ?>
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-4"></i>
                        <p class="text-lg mb-2">No hay preguntas aún</p>
                        <p class="text-sm">Agrega preguntas para que el quiz esté completo</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($questions as $index => $question): 
                            // Obtener opciones de la pregunta
                            $optStmt = $db->prepare("SELECT * FROM quiz_question_options WHERE question_id = ? ORDER BY option_order ASC");
                            $optStmt->execute([$question['id']]);
                            $options = $optStmt->fetchAll();
                        ?>
                        <div class="glass-card rounded-xl p-5 mb-4 hover:scale-[1.02] transition-all duration-300 slide-in-right" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <span class="badge-modern bg-gradient-to-r from-blue-500 to-purple-500 text-white">Pregunta <?php echo $index + 1; ?></span>
                                    </div>
                                    <p class="font-semibold text-gray-800 text-lg mb-2"><?php echo htmlspecialchars($question['text']); ?></p>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="openQuestionModal(<?php echo $quiz_id; ?>, <?php echo $question['id']; ?>)" class="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 hover:scale-110 transition-all duration-300 icon-hover" title="Editar">
                                        <i class="fas fa-edit text-sm"></i>
                                    </button>
                                    <button onclick="deleteQuestion(<?php echo $question['id']; ?>)" class="w-9 h-9 flex items-center justify-center rounded-lg bg-red-100 text-red-600 hover:bg-red-200 hover:scale-110 transition-all duration-300 icon-hover" title="Eliminar">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <?php if (!empty($options)): ?>
                            <div class="ml-4 border-l-2 border-gray-200 pl-4 space-y-2">
                                <?php foreach ($options as $opt): ?>
                                <div class="flex items-center space-x-2 text-sm">
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center <?php echo $opt['is_correct'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'; ?>">
                                        <?php echo $opt['is_correct'] ? '<i class="fas fa-check"></i>' : '<i class="fas fa-circle"></i>'; ?>
                                    </span>
                                    <span class="<?php echo $opt['is_correct'] ? 'font-semibold text-green-800' : 'text-gray-700'; ?>">
                                        <?php echo htmlspecialchars($opt['text']); ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <p class="text-sm text-gray-500 italic ml-4">Sin opciones definidas</p>
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
                    <h2 id="questionModalTitle" class="modal-title flex items-center">
                        <span style="font-size: 28px; margin-right: 12px;">❓</span>
                        <span id="questionTitleText">AGREGAR PREGUNTA</span>
                    </h2>
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
                            ➕ AGREGAR
                        </button>
                    </div>
                    <div id="optionsContainer" style="display: flex; flex-direction: column; gap: 12px;">
                        <!-- Las opciones se agregarán aquí dinámicamente -->
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
            optionDiv.style.cssText = 'display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--gray-100); border: 2px solid var(--gray-200); border-radius: 16px;';
            const multipleAllowed = document.getElementById('question_multiple').checked;
            const inputType = multipleAllowed ? 'checkbox' : 'radio';
            const inputName = multipleAllowed ? `option-correct-${optionCount}` : 'option-correct-single';
            optionDiv.innerHTML = `
                <div style="flex: 1;">
                    <input type="text" class="option-text input-game" 
                        placeholder="Escribe la opción..." value="${text}" required>
                </div>
                <label style="display: flex; align-items: center; padding: 8px 12px; background: ${isCorrect ? 'var(--pastel-green)' : 'white'}; border: 2px solid ${isCorrect ? 'var(--duo-green)' : 'var(--gray-300)'}; border-radius: 12px; cursor: pointer; font-weight: 700; font-size: 12px; white-space: nowrap;">
                    <input type="${inputType}" name="${inputName}" class="option-correct" ${isCorrect ? 'checked' : ''} onchange="handleCorrectChange(this)" style="width: 18px; height: 18px; margin-right: 8px;">
                    <span>${isCorrect ? '✅' : '☑️'} CORRECTA</span>
                </label>
                <button type="button" onclick="removeOption(this)" style="background: var(--duo-red); color: white; width: 36px; height: 36px; border-radius: 12px; border: none; cursor: pointer; font-size: 18px; box-shadow: 0 2px 0 #CC3333;">
                    🗑️
                </button>
            `;
            container.appendChild(optionDiv);
        }
        
        function toggleMultipleAnswers() {
            const multipleAllowed = document.getElementById('question_multiple').checked;
            const correctInputs = document.querySelectorAll('.option-correct');
            
            correctInputs.forEach(input => {
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
                        }
                    }
                }
            });
        }
        
        function handleCorrectChange(changedInput) {
            const multipleAllowed = document.getElementById('question_multiple').checked;
            
            if (!multipleAllowed && changedInput.checked) {
                // Si no se permiten múltiples, desmarcar todas las demás
                const allCorrectInputs = document.querySelectorAll('.option-correct');
                allCorrectInputs.forEach(input => {
                    if (input !== changedInput) {
                        input.checked = false;
                    }
                });
            }
        }
        
        function removeOption(button) {
            const container = document.getElementById('optionsContainer');
            if (container.children.length > 1) {
                button.closest('.flex.items-start').remove();
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
            const optionElements = document.querySelectorAll('#optionsContainer .flex.items-start');
            
            if (optionElements.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Debe agregar al menos una opción',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
            
            optionElements.forEach(div => {
                const textInput = div.querySelector('.option-text');
                const correctCheckbox = div.querySelector('.option-correct');
                
                if (textInput && textInput.value.trim()) {
                    options.push({
                        text: textInput.value.trim(),
                        is_correct: correctCheckbox.checked
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
