<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participar en Quiz - MeritumQuest</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        [x-cloak] { display: none !important; }
        body {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            min-height: 100vh;
        }
        .option-btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid #e5e7eb;
        }
        .option-btn:hover:not(:disabled) {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }
        .option-btn.selected {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%) !important;
            color: white !important;
            border-color: #1e40af !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }
        .option-btn.correct {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
            border-color: #059669 !important;
            animation: scaleIn 0.3s ease-out;
        }
        .option-btn.incorrect {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: white !important;
            border-color: #dc2626 !important;
            animation: shake 0.5s ease-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-6 md:py-8" x-data="studentApp()" x-cloak>
        <!-- Pantalla de entrada -->
        <div x-show="screen === 'entry'" class="max-w-md mx-auto animate-fade-in">
            <div class="modern-card p-6 md:p-8">
                <div class="text-center mb-6">
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">MeritumQuest</h1>
                    <div class="w-16 h-1 bg-blue-600 mx-auto"></div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-3 text-sm md:text-base">Código del Quiz</label>
                    <input type="text" x-model="sessionCode" 
                           @keyup.enter="joinSession()"
                           placeholder="Ingresa el código"
                           class="w-full px-4 py-4 md:py-5 modern-input rounded-lg text-center text-xl md:text-2xl font-bold tracking-widest uppercase">
                </div>
                
                <button @click="joinSession()" 
                        class="w-full btn-primary ripple text-white font-bold py-4 md:py-5 px-6 rounded-lg text-base md:text-lg">
                    <span class="relative z-10">Entrar</span>
                </button>
            </div>
        </div>

        <!-- Pantalla de espera -->
        <div x-show="screen === 'waiting'" class="max-w-md mx-auto animate-fade-in">
            <div class="modern-card p-8 md:p-12 text-center">
                <div class="spinner mx-auto mb-6"></div>
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3">Esperando...</h2>
                <p class="text-gray-600 mb-4">Esperando a que el presentador inicie el quiz</p>
                <div class="inline-block badge badge-blue" x-text="'Código: ' + sessionCode"></div>
            </div>
        </div>

        <!-- Pantalla de nombre -->
        <div x-show="screen === 'name'" class="max-w-md mx-auto animate-scale-in">
            <div class="modern-card p-6 md:p-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6 text-center">Ingresa tu nombre</h2>
                <input type="text" x-model="participantName" 
                       @keyup.enter="startQuiz()"
                       placeholder="Tu nombre o nickname"
                       class="w-full px-4 py-4 modern-input rounded-lg mb-6 text-center text-lg">
                <button @click="startQuiz()" 
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-lg transition-all hover:scale-105 shadow-lg">
                    Comenzar
                </button>
            </div>
        </div>

        <!-- Pantalla del quiz -->
        <div x-show="screen === 'quiz'" class="max-w-4xl mx-auto animate-fade-in">
            <div class="modern-card p-4 md:p-8">
                <!-- Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 pb-4 border-b-2 border-gray-200">
                    <div class="mb-4 md:mb-0">
                        <h2 class="text-xl md:text-2xl font-bold text-gray-900" x-text="sessionData.quiz_title"></h2>
                        <p class="text-sm text-gray-600 mt-1" x-text="'Pregunta ' + (currentQuestionIndex + 1) + ' de ' + questions.length"></p>
                    </div>
                    <div class="text-right w-full md:w-auto">
                        <div x-show="currentQuestion.time_limit_sec" 
                             class="text-3xl md:text-4xl font-bold inline-block px-4 py-2 rounded-lg"
                             :class="timeLeft <= 10 ? 'bg-red-100 text-red-600 animate-pulse-slow' : 'bg-blue-100 text-blue-600'"
                             x-text="formatTime(timeLeft)"></div>
                    </div>
                </div>

                <!-- Pregunta -->
                <div class="mb-6">
                    <h3 class="text-2xl font-bold mb-4" x-text="currentQuestion.text"></h3>
                    
                    <!-- Multimedia -->
                    <div class="mb-4">
                        <img x-show="currentQuestion.image_url" 
                             :src="currentQuestion.image_url" 
                             class="max-w-full rounded-lg mb-4">
                        <video x-show="currentQuestion.video_url" 
                               :src="currentQuestion.video_url" 
                               controls
                               class="max-w-full rounded-lg mb-4"></video>
                        <audio x-show="currentQuestion.audio_url" 
                               :src="currentQuestion.audio_url" 
                               controls
                               class="w-full mb-4"></audio>
                    </div>
                </div>

                <!-- Opciones -->
                <div class="space-y-3 md:space-y-4 mb-6">
                    <template x-for="(option, index) in currentQuestion.options" :key="option.id">
                        <button 
                            @click="selectOption(option.id)"
                            :disabled="answered || (modeConfig && modeConfig.feedback_immediate && answered)"
                            :class="getOptionClass(option.id)"
                            class="option-btn w-full text-left p-4 md:p-5 rounded-lg font-semibold text-base md:text-lg bg-white"
                            x-text="String.fromCharCode(65 + index) + '. ' + option.text">
                        </button>
                    </template>
                </div>

                <!-- Botones de navegación -->
                <div class="flex flex-col md:flex-row justify-between gap-3 md:gap-4">
                    <button @click="previousQuestion()" 
                            :disabled="currentQuestionIndex === 0"
                            class="bg-gray-500 hover:bg-gray-600 disabled:bg-gray-300 text-white font-bold py-3 px-6 rounded-lg transition-all hover:scale-105 disabled:opacity-50">
                        ← Anterior
                    </button>
                    <button @click="nextQuestion()" 
                            :disabled="currentQuestionIndex >= questions.length - 1"
                            class="btn-primary text-white font-bold py-3 px-6 rounded-lg transition-all hover:scale-105 disabled:opacity-50">
                        Siguiente →
                    </button>
                    <button x-show="currentQuestionIndex === questions.length - 1" 
                            @click="finishQuiz()"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition-all hover:scale-105 shadow-lg">
                        ✓ Finalizar Quiz
                    </button>
                </div>
            </div>
        </div>

        <!-- Pantalla de resultados -->
        <div x-show="screen === 'results'" class="max-w-4xl mx-auto animate-fade-in">
            <div class="modern-card p-6 md:p-8">
                <h2 class="text-3xl md:text-4xl font-bold text-center mb-8 text-gray-900">Resultados</h2>
                
                <div class="text-center mb-8 p-8 rounded-2xl"
                     :class="results.percentage >= 70 ? 'bg-green-50' : results.percentage >= 50 ? 'bg-yellow-50' : 'bg-red-50'">
                    <div class="text-6xl md:text-7xl font-bold mb-4 animate-scale-in" 
                         :class="results.percentage >= 70 ? 'text-green-600' : results.percentage >= 50 ? 'text-yellow-600' : 'text-red-600'"
                         x-text="results.percentage.toFixed(1) + '%'"></div>
                    <p class="text-xl md:text-2xl text-gray-700 font-semibold mb-2" 
                       x-text="results.correct_answers + ' de ' + results.total_questions + ' correctas'"></p>
                    <p class="text-lg text-gray-600" 
                       x-text="results.total_points + ' puntos de ' + results.max_points"></p>
                </div>

                <!-- Ranking -->
                <div x-show="modeConfig && modeConfig.show_ranking" class="mb-6">
                    <h3 class="text-2xl font-bold mb-4">Ranking</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="px-4 py-2">Posición</th>
                                    <th class="px-4 py-2">Nombre</th>
                                    <th class="px-4 py-2">Puntos</th>
                                    <th class="px-4 py-2">Porcentaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(entry, index) in ranking" :key="entry.id">
                                    <tr :class="entry.participant_name === participantName ? 'bg-blue-100 font-bold' : ''">
                                        <td class="px-4 py-2" x-text="entry.position"></td>
                                        <td class="px-4 py-2" x-text="entry.participant_name"></td>
                                        <td class="px-4 py-2" x-text="entry.total_points"></td>
                                        <td class="px-4 py-2" x-text="entry.percentage.toFixed(1) + '%'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="text-center">
                    <button @click="reset()" 
                            class="btn-primary ripple text-white font-bold py-3 px-8 rounded-lg">
                        <span class="relative z-10">Volver al inicio</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="student.js"></script>
</body>
</html>

