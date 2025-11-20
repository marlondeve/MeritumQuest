<?php
require_once 'config/config.php';

$code = strtoupper(trim($_GET['code'] ?? ''));

if (empty($code)) {
    header('Location: index.php');
    exit;
}

$db = getDB();

// Buscar quiz por código
$stmt = $db->prepare("SELECT * FROM quizzes WHERE code = ?");
$stmt->execute([$code]);
$quiz = $stmt->fetch();

if (!$quiz) {
    $error = "Quiz no encontrado con el código: " . htmlspecialchars($code);
    include 'error-quiz.php';
    exit;
}

// Verificar si tiene preguntas
$stmt = $db->prepare("SELECT COUNT(*) as total FROM quiz_questions WHERE quiz_id = ?");
$stmt->execute([$quiz['id']]);
$questionCount = $stmt->fetch()['total'];

// Obtener preguntas con opciones
$stmt = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY question_order ASC");
$stmt->execute([$quiz['id']]);
$questions = $stmt->fetchAll();

$questionsWithOptions = [];
foreach ($questions as $question) {
    $optStmt = $db->prepare("SELECT * FROM quiz_question_options WHERE question_id = ? ORDER BY option_order ASC");
    $optStmt->execute([$question['id']]);
    $question['options'] = $optStmt->fetchAll();
    $questionsWithOptions[] = $question;
}

// Calcular puntos máximos
$maxPoints = count($questions) * $quiz['points_per_question'];
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
    <style>
        .question-card {
            display: none;
        }
        .question-card.active {
            display: block;
        }
        .podium-item {
            transition: all 0.3s ease;
        }
        .podium-item:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body style="background: #FAFAFA;">
    <!-- Header -->
    <nav class="header-game">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <img src="<?php echo APP_URL; ?>/assets/avatar/2.png" alt="MeritumQ" style="width: 50px; height: auto; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.1));">
                    <span style="font-size: 24px; font-weight: 900; color: var(--gray-900); letter-spacing: 1px;"><?php echo APP_NAME; ?></span>
                </div>
                <a href="index.php" class="btn-game btn-blue" style="padding: 10px 20px; font-size: 14px;">
                    🏠 INICIO
                </a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-6 py-8" style="max-width: 900px;">
        <?php if ($questionCount == 0): ?>
            <!-- Sin preguntas -->
            <div class="card-game text-center py-16 slide-up">
                <img src="<?php echo APP_URL; ?>/assets/avatar/14.png" alt="Sin preguntas" style="width: 150px; height: auto; margin: 0 auto 24px; display: block; filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1));">
                <h1 style="font-size: 32px; font-weight: 900; color: var(--gray-900); margin-bottom: 16px;">
                    ⚠️ No hay preguntas registradas
                </h1>
                <p style="font-size: 18px; font-weight: 600; color: var(--gray-700); margin-bottom: 8px;">
                    Este quiz aún no tiene preguntas creadas.
                </p>
                <p style="font-size: 16px; font-weight: 600; color: var(--gray-500); margin-bottom: 32px;">
                    Por favor, contacta al administrador del quiz.
                </p>
                <div class="card-game" style="background: var(--pastel-blue); border-color: var(--duo-blue); padding: 20px; margin: 0 auto; max-width: 500px;">
                    <p style="font-size: 14px; font-weight: 600; color: var(--gray-700); line-height: 1.6;">
                        <strong>Código del Quiz:</strong><br>
                        <span style="font-family: monospace; font-size: 24px; font-weight: 900; color: var(--duo-blue);"><?php echo htmlspecialchars($quiz['code']); ?></span>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <!-- Formulario de inicio -->
            <div id="startForm" class="card-game slide-up">
                <div class="text-center mb-8">
                    <img src="<?php echo APP_URL; ?>/assets/avatar/10.png" alt="Quiz" style="width: 120px; height: auto; margin: 0 auto 24px; display: block; filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1));">
                    <h1 style="font-size: 36px; font-weight: 900; color: var(--gray-900); margin-bottom: 12px;">
                        <?php echo htmlspecialchars($quiz['title']); ?>
                    </h1>
                    <?php if (!empty($quiz['description'])): ?>
                    <p style="font-size: 18px; font-weight: 600; color: var(--gray-700); margin-bottom: 24px;">
                        <?php echo htmlspecialchars($quiz['description']); ?>
                    </p>
                    <?php endif; ?>
                    <div class="card-game" style="background: var(--pastel-blue); border-color: var(--duo-blue); padding: 16px; margin: 0 auto 24px; max-width: 400px;">
                        <div style="display: flex; justify-content: space-around; text-align: center;">
                            <div>
                                <div style="font-size: 32px; font-weight: 900; color: var(--duo-blue);"><?php echo $questionCount; ?></div>
                                <div style="font-size: 14px; font-weight: 700; color: var(--gray-700);">Preguntas</div>
                            </div>
                            <div>
                                <div style="font-size: 32px; font-weight: 900; color: var(--duo-green);"><?php echo $maxPoints; ?></div>
                                <div style="font-size: 14px; font-weight: 700; color: var(--gray-700);">Puntos</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form id="participantForm" style="max-width: 500px; margin: 0 auto;">
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                            👤 Tu Nombre *
                        </label>
                        <input type="text" id="participantName" name="name" required class="input-game" placeholder="Ingresa tu nombre" maxlength="150">
                    </div>
                    
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                            📧 Email (Opcional)
                        </label>
                        <input type="email" id="participantEmail" name="email" class="input-game" placeholder="tu@email.com" maxlength="255">
                    </div>
                    
                    <button type="submit" class="btn-game btn-blue" style="width: 100%; padding: 18px; font-size: 18px;">
                        🚀 COMENZAR QUIZ
                    </button>
                </form>
            </div>

            <!-- Quiz en progreso -->
            <div id="quizContainer" style="display: none;">
                <div class="card-game mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 style="font-size: 24px; font-weight: 900; color: var(--gray-900);" id="quizTitle"><?php echo htmlspecialchars($quiz['title']); ?></h2>
                            <p style="font-size: 14px; font-weight: 600; color: var(--gray-700);" id="participantInfo"></p>
                        </div>
                        <div class="text-right">
                            <div style="font-size: 20px; font-weight: 900; color: var(--duo-blue);" id="currentQuestion">1</div>
                            <div style="font-size: 12px; font-weight: 700; color: var(--gray-700);">de <?php echo $questionCount; ?></div>
                        </div>
                    </div>
                    <div class="progress-game" style="margin-bottom: 16px;">
                        <div class="progress-fill" id="progressBar" style="width: 0%;"></div>
                    </div>
                </div>

                <?php foreach ($questionsWithOptions as $index => $question): ?>
                <div class="question-card card-game mb-6" data-question-id="<?php echo $question['id']; ?>" data-question-index="<?php echo $index; ?>">
                    <div class="flex items-start space-x-4 mb-6">
                        <img src="<?php echo APP_URL; ?>/assets/avatar/<?php echo ($index % 17) + 1; ?>.png" alt="Mascota" style="width: 80px; height: auto; flex-shrink: 0; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.1));">
                        <div style="flex: 1;">
                            <div class="badge-game blue" style="margin-bottom: 16px;">
                                Pregunta <?php echo $index + 1; ?>
                            </div>
                            <h3 style="font-size: 24px; font-weight: 900; color: var(--gray-900); margin-bottom: 24px; line-height: 1.4;">
                                <?php echo htmlspecialchars($question['text']); ?>
                            </h3>
                            
                            <div class="space-y-3">
                                <?php foreach ($question['options'] as $opt): ?>
                                <label class="option-label card-game" style="display: flex; align-items: center; padding: 16px; cursor: pointer; transition: all 0.2s ease; border: 2px solid var(--gray-200);" 
                                       data-option-id="<?php echo $opt['id']; ?>" 
                                       data-is-correct="<?php echo $opt['is_correct']; ?>">
                                    <input type="<?php echo $question['allow_multiple_answers'] ? 'checkbox' : 'radio'; ?>" 
                                           name="question_<?php echo $question['id']; ?>" 
                                           value="<?php echo $opt['id']; ?>" 
                                           class="option-input" 
                                           style="width: 24px; height: 24px; margin-right: 16px; cursor: pointer;">
                                    <span style="font-size: 16px; font-weight: 600; color: var(--gray-900); flex: 1;">
                                        <?php echo htmlspecialchars($opt['text']); ?>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <button onclick="nextQuestion()" class="btn-game btn-blue" style="width: 100%; margin-top: 24px; padding: 16px; font-size: 16px;">
                                <?php echo ($index + 1) < count($questionsWithOptions) ? 'SIGUIENTE →' : 'FINALIZAR QUIZ ✓'; ?>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Resultados y Podio -->
            <div id="resultsContainer" style="display: none;">
                <div class="card-game text-center slide-up">
                    <img src="<?php echo APP_URL; ?>/assets/avatar/6.png" alt="Resultados" style="width: 150px; height: auto; margin: 0 auto 24px; display: block; filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1));">
                    <h1 style="font-size: 36px; font-weight: 900; color: var(--gray-900); margin-bottom: 16px;">
                        🎉 ¡Quiz Completado!
                    </h1>
                    <div class="card-game" style="background: linear-gradient(135deg, var(--duo-blue) 0%, var(--duo-blue-light) 100%); color: white; border-color: #1391C4; margin: 24px auto; max-width: 400px;">
                        <div style="font-size: 48px; font-weight: 900; margin-bottom: 8px;" id="finalScore">0</div>
                        <div style="font-size: 18px; font-weight: 600; opacity: 0.9;">Puntos Obtenidos</div>
                        <div style="font-size: 32px; font-weight: 900; margin-top: 16px;" id="finalPercentage">0%</div>
                    </div>
                </div>

                <!-- Podio -->
                <div class="card-game mt-6 slide-up">
                    <h2 style="font-size: 28px; font-weight: 900; color: var(--gray-900); text-align: center; margin-bottom: 32px;">
                        🏆 Podio de Participantes
                    </h2>
                    <div id="podiumContainer" style="display: flex; justify-content: center; align-items: flex-end; gap: 16px; min-height: 300px;">
                        <!-- El podio se generará aquí -->
                    </div>
                    <div id="userPosition" class="card-game mt-6" style="background: var(--pastel-blue); border-color: var(--duo-blue); padding: 20px; text-align: center;">
                        <p style="font-size: 18px; font-weight: 700; color: var(--gray-900);" id="positionText"></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        const quizId = <?php echo $quiz['id']; ?>;
        const quizCode = '<?php echo htmlspecialchars($quiz['code']); ?>';
        const questions = <?php echo json_encode($questionsWithOptions); ?>;
        const pointsPerQuestion = <?php echo $quiz['points_per_question']; ?>;
        const totalQuestions = <?php echo $questionCount; ?>;
        
        let currentQuestionIndex = 0;
        let answers = {};
        let participantName = '';
        let participantEmail = '';
        let attemptId = null;
        
        document.getElementById('participantForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            participantName = document.getElementById('participantName').value.trim();
            participantEmail = document.getElementById('participantEmail').value.trim();
            
            if (!participantName) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor ingresa tu nombre',
                    confirmButtonColor: '#1CB0F6'
                });
                return;
            }
            
            // Crear sesión e intento
            try {
                const response = await fetch('api/start-quiz-attempt.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        quiz_id: quizId,
                        quiz_code: quizCode,
                        participant_name: participantName,
                        participant_email: participantEmail
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    attemptId = result.attempt_id;
                    document.getElementById('startForm').style.display = 'none';
                    document.getElementById('quizContainer').style.display = 'block';
                    document.getElementById('participantInfo').textContent = 'Participante: ' + participantName;
                    showQuestion(0);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Error al iniciar el quiz',
                        confirmButtonColor: '#1CB0F6'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al iniciar el quiz',
                    confirmButtonColor: '#1CB0F6'
                });
            }
        });
        
        function showQuestion(index) {
            // Ocultar todas las preguntas
            document.querySelectorAll('.question-card').forEach(card => {
                card.classList.remove('active');
            });
            
            // Mostrar pregunta actual
            const currentCard = document.querySelector(`[data-question-index="${index}"]`);
            if (currentCard) {
                currentCard.classList.add('active');
                currentQuestionIndex = index;
                updateProgress();
            }
        }
        
        function updateProgress() {
            const progress = ((currentQuestionIndex + 1) / totalQuestions) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('currentQuestion').textContent = currentQuestionIndex + 1;
        }
        
        function nextQuestion() {
            // Guardar respuestas actuales
            const questionId = questions[currentQuestionIndex].id;
            const selectedOptions = [];
            
            document.querySelectorAll(`input[name="question_${questionId}"]:checked`).forEach(input => {
                selectedOptions.push(parseInt(input.value));
            });
            
            if (selectedOptions.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Por favor selecciona al menos una opción',
                    confirmButtonColor: '#1CB0F6',
                    timer: 2000,
                    showConfirmButton: false
                });
                return;
            }
            
            answers[questionId] = selectedOptions;
            
            // Avanzar a siguiente pregunta o finalizar
            if (currentQuestionIndex + 1 < totalQuestions) {
                showQuestion(currentQuestionIndex + 1);
            } else {
                finishQuiz();
            }
        }
        
        async function finishQuiz() {
            // Guardar respuestas y calcular puntaje
            try {
                const response = await fetch('api/finish-quiz-attempt.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        attempt_id: attemptId,
                        answers: answers
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('quizContainer').style.display = 'none';
                    document.getElementById('resultsContainer').style.display = 'block';
                    document.getElementById('finalScore').textContent = result.total_points;
                    document.getElementById('finalPercentage').textContent = result.percentage + '%';
                    
                    // Mostrar podio
                    showPodium(result.position, result.total_participants, result.leaderboard);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Error al finalizar el quiz',
                        confirmButtonColor: '#1CB0F6'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al finalizar el quiz',
                    confirmButtonColor: '#1CB0F6'
                });
            }
        }
        
        function showPodium(userPosition, totalParticipants, leaderboard) {
            const container = document.getElementById('podiumContainer');
            container.innerHTML = '';
            
            // Mostrar top 3
            const top3 = leaderboard.slice(0, 3);
            const medals = ['🥇', '🥈', '🥉'];
            const heights = [200, 150, 120];
            const colors = ['var(--duo-yellow)', 'var(--gray-300)', '#CD7F32'];
            
            top3.forEach((participant, index) => {
                const isUser = participant.name === participantName;
                const podiumItem = document.createElement('div');
                podiumItem.className = 'podium-item';
                podiumItem.style.cssText = `
                    text-align: center;
                    flex: 1;
                    max-width: 200px;
                `;
                
                podiumItem.innerHTML = `
                    <div style="font-size: 48px; margin-bottom: 8px;">${medals[index]}</div>
                    <div class="card-game" style="background: ${colors[index]}; border-color: ${colors[index]}; padding: 16px; height: ${heights[index]}px; display: flex; flex-direction: column; justify-content: space-between; ${isUser ? 'border: 3px solid var(--duo-blue); box-shadow: 0 0 20px rgba(28, 176, 246, 0.5);' : ''}">
                        <div>
                            <div style="font-size: 24px; font-weight: 900; color: white; margin-bottom: 8px;">${participant.name}</div>
                            <div style="font-size: 32px; font-weight: 900; color: white;">${participant.total_points}</div>
                            <div style="font-size: 14px; font-weight: 700; color: rgba(255,255,255,0.9);">puntos</div>
                        </div>
                    </div>
                `;
                
                container.appendChild(podiumItem);
            });
            
            // Mostrar posición del usuario
            let positionText = '';
            if (userPosition === 1) {
                positionText = `🏆 ¡Felicidades! Eres el #1 con ${leaderboard[0].total_points} puntos`;
            } else if (userPosition <= 3) {
                positionText = `🎉 ¡Excelente! Obtuviste el puesto #${userPosition} de ${totalParticipants} participantes`;
            } else {
                positionText = `👍 Obtuviste el puesto #${userPosition} de ${totalParticipants} participantes`;
            }
            
            document.getElementById('positionText').textContent = positionText;
        }
    </script>
</body>
</html>

