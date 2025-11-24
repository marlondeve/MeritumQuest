<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantalla Presentador - MeritumQuest</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        [x-cloak] { display: none !important; }
        body {
            background: linear-gradient(135deg, #000000 0%, #1e40af 50%, #2563eb 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-6 md:py-8" x-data="presenterApp()" x-cloak>
        <!-- Header -->
        <div class="modern-card p-6 md:p-8 mb-6 gradient-blue text-white animate-fade-in">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-2xl md:text-4xl font-bold mb-2" x-text="sessionData?.quiz_title || 'MeritumQuest'"></h1>
                    <div class="badge badge-white" x-text="'Código: ' + sessionCode"></div>
                </div>
                <div class="text-right bg-white/20 rounded-lg p-4 backdrop-blur-sm">
                    <div class="text-sm text-white/80 mb-1">Participantes conectados</div>
                    <div class="text-4xl md:text-5xl font-bold animate-pulse-slow" x-text="connectedParticipants"></div>
                </div>
            </div>
        </div>

        <!-- Pantalla de espera -->
        <div x-show="currentState === 'waiting'" class="modern-card p-12 md:p-16 text-center animate-fade-in">
            <div class="text-7xl md:text-8xl mb-6 animate-bounce">⏳</div>
            <h2 class="text-3xl md:text-5xl font-bold mb-4 text-gray-900">Esperando participantes</h2>
            <p class="text-lg md:text-xl text-gray-600 mb-8">Los estudiantes pueden unirse usando el código:</p>
            <div class="inline-block badge badge-blue text-2xl md:text-3xl px-6 py-3 mb-8" x-text="sessionCode"></div>
            <button @click="startQuiz()" 
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-8 md:px-12 rounded-lg text-lg md:text-xl transition-all hover:scale-105 shadow-xl">
                ▶ Iniciar Quiz
            </button>
        </div>

        <!-- Pantalla de pregunta activa -->
        <div x-show="currentState === 'question'" class="modern-card p-6 md:p-8 animate-fade-in">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 pb-4 border-b-2 border-gray-200">
                <div class="mb-4 md:mb-0">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900" x-text="'Pregunta ' + (currentQuestionIndex + 1) + ' de ' + questions.length"></h2>
                </div>
                <div class="flex flex-col md:flex-row gap-4 md:gap-6">
                    <div class="text-center md:text-right bg-green-50 rounded-lg p-3 md:p-4">
                        <div class="text-xs md:text-sm text-gray-600 mb-1">Han respondido</div>
                        <div class="text-3xl md:text-4xl font-bold text-green-600" x-text="answeredCount + ' / ' + connectedParticipants"></div>
                    </div>
                    <div x-show="currentQuestion.time_limit_sec" 
                         class="text-center md:text-right px-4 py-3 rounded-lg"
                         :class="timeLeft <= 10 ? 'bg-red-100 text-red-600 animate-pulse-slow' : 'bg-blue-100 text-blue-600'">
                        <div class="text-xs md:text-sm text-gray-600 mb-1">Tiempo</div>
                        <div class="text-2xl md:text-3xl font-bold" x-text="formatTime(timeLeft)"></div>
                    </div>
                </div>
            </div>

            <div class="mb-8">
                <h3 class="text-2xl md:text-4xl font-bold mb-6 text-gray-900" x-text="currentQuestion.text"></h3>
                
                <!-- Multimedia -->
                <div class="mb-6">
                    <img x-show="currentQuestion.image_url" 
                         :src="currentQuestion.image_url" 
                         class="max-w-full rounded-xl mb-4 shadow-lg">
                    <video x-show="currentQuestion.video_url" 
                           :src="currentQuestion.video_url" 
                           controls
                           class="max-w-full rounded-xl mb-4 shadow-lg"></video>
                    <audio x-show="currentQuestion.audio_url" 
                           :src="currentQuestion.audio_url" 
                           controls
                           class="w-full mb-4"></audio>
                </div>

                <!-- Opciones -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <template x-for="(option, index) in currentQuestion.options" :key="option.id">
                        <div class="modern-card p-5 md:p-6 text-lg md:text-xl font-semibold text-gray-900 card-hover"
                             x-text="String.fromCharCode(65 + index) + '. ' + option.text">
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex flex-col md:flex-row justify-center gap-4">
                <button @click="closeQuestion()" 
                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-lg text-lg transition-all hover:scale-105 shadow-lg">
                    ⏹ Cerrar Pregunta
                </button>
                <button @click="nextQuestion()" 
                        class="btn-primary text-white font-bold py-3 px-8 rounded-lg text-lg transition-all hover:scale-105">
                    Siguiente Pregunta →
                </button>
            </div>
        </div>

        <!-- Pantalla de resultados de pregunta -->
        <div x-show="currentState === 'results'" class="modern-card p-6 md:p-8 animate-fade-in">
            <h2 class="text-3xl md:text-4xl font-bold mb-6 text-gray-900">Resultados de la Pregunta</h2>
            
            <div class="mb-8">
                <h3 class="text-xl md:text-2xl font-bold mb-4 text-gray-800" x-text="currentQuestion.text"></h3>
                <div class="bg-gradient-to-r from-green-50 to-green-100 border-2 border-green-500 rounded-xl p-5 md:p-6 mb-6 animate-scale-in">
                    <p class="text-lg md:text-xl font-semibold text-green-800">
                        ✓ Respuesta correcta: <span class="text-green-600" x-text="getCorrectAnswer()"></span>
                    </p>
                </div>
            </div>

            <!-- Gráfica de resultados -->
            <div class="mb-8">
                <canvas id="resultsChart" width="400" height="200"></canvas>
            </div>

            <!-- Top 5 -->
            <div x-show="topParticipants.length > 0" class="mb-8">
                <h3 class="text-2xl md:text-3xl font-bold mb-6 text-gray-900">🏆 Top 5</h3>
                <div class="space-y-3">
                    <template x-for="(participant, index) in topParticipants" :key="index">
                        <div class="modern-card p-4 md:p-5 flex justify-between items-center card-hover animate-fade-in"
                             :style="`animation-delay: ${index * 0.1}s`">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl md:text-4xl font-bold" 
                                      :class="index === 0 ? 'text-yellow-500' : index === 1 ? 'text-gray-400' : index === 2 ? 'text-orange-600' : 'text-gray-600'"
                                      x-text="index + 1"></span>
                                <span class="text-lg md:text-xl font-semibold text-gray-900" x-text="participant.name"></span>
                            </div>
                            <span class="text-lg md:text-xl font-bold text-blue-600" x-text="participant.points + ' pts'"></span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex justify-center">
                <button @click="nextQuestion()" 
                        class="btn-primary text-white font-bold py-3 px-8 md:px-12 rounded-lg text-lg transition-all hover:scale-105">
                    Siguiente Pregunta →
                </button>
            </div>
        </div>

        <!-- Pantalla de ranking final -->
        <div x-show="currentState === 'ranking'" class="modern-card p-6 md:p-8 animate-fade-in">
            <h2 class="text-4xl md:text-5xl font-bold text-center mb-8 text-gray-900">🏆 Ranking Final</h2>
            
            <div class="overflow-x-auto mb-8">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                            <th class="px-4 md:px-6 py-3 text-left text-sm md:text-base">Posición</th>
                            <th class="px-4 md:px-6 py-3 text-left text-sm md:text-base">Nombre</th>
                            <th class="px-4 md:px-6 py-3 text-right text-sm md:text-base">Puntos</th>
                            <th class="px-4 md:px-6 py-3 text-right text-sm md:text-base">Porcentaje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(entry, index) in ranking" :key="entry.id">
                            <tr class="animate-fade-in border-b border-gray-200 hover:bg-gray-50 transition-colors"
                                :style="`animation-delay: ${index * 0.05}s`"
                                :class="index < 3 ? 'bg-gradient-to-r from-yellow-50 to-yellow-100 font-bold' : ''">
                                <td class="px-4 md:px-6 py-4">
                                    <span class="text-2xl md:text-3xl" 
                                          :class="index === 0 ? 'text-yellow-500' : index === 1 ? 'text-gray-400' : index === 2 ? 'text-orange-600' : 'text-gray-600'"
                                          x-text="entry.position"></span>
                                </td>
                                <td class="px-4 md:px-6 py-4 text-base md:text-lg text-gray-900" x-text="entry.participant_name"></td>
                                <td class="px-4 md:px-6 py-4 text-right text-base md:text-lg font-bold text-blue-600" x-text="entry.total_points"></td>
                                <td class="px-4 md:px-6 py-4 text-right text-base md:text-lg" x-text="entry.percentage.toFixed(1) + '%'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="text-center">
                <button @click="endSession()" 
                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 md:px-12 rounded-lg text-lg transition-all hover:scale-105 shadow-xl">
                    ⏹ Finalizar Sesión
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="presenter.js"></script>
</body>
</html>

