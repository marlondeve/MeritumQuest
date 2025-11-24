<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analíticas - MeritumQuest</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8" x-data="analyticsApp()" x-cloak>
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Analíticas del Quiz</h1>
                    <p class="text-gray-600 mt-2" x-text="'Quiz ID: ' + quizId"></p>
                </div>
                <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg">
                    Volver
                </a>
            </div>
        </div>

        <!-- Estadísticas generales -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-gray-600 text-sm font-bold mb-2">Total Intentos</h3>
                <p class="text-3xl font-bold text-blue-600" x-text="stats.total_attempts || 0"></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-gray-600 text-sm font-bold mb-2">Total Participantes</h3>
                <p class="text-3xl font-bold text-green-600" x-text="stats.total_participants || 0"></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-gray-600 text-sm font-bold mb-2">Promedio</h3>
                <p class="text-3xl font-bold text-purple-600" x-text="(stats.average_percentage || 0).toFixed(1) + '%'"></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-gray-600 text-sm font-bold mb-2">Puntos Máximos</h3>
                <p class="text-3xl font-bold text-yellow-600" x-text="stats.max_points_achieved || 0"></p>
            </div>
        </div>

        <!-- Estadísticas por pregunta -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Estadísticas por Pregunta</h2>
            <div class="space-y-6">
                <template x-for="(question, index) in questionStats" :key="question.question_id">
                    <div class="border rounded-lg p-4">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <h3 class="font-bold text-lg mb-2" x-text="'Pregunta ' + (index + 1)"></h3>
                                <p class="text-gray-700 mb-2" x-text="question.question_text"></p>
                                <div class="flex gap-4 text-sm">
                                    <span class="text-green-600 font-bold">
                                        Correctas: <span x-text="question.correct_answers"></span>
                                    </span>
                                    <span class="text-red-600 font-bold">
                                        Incorrectas: <span x-text="question.incorrect_answers"></span>
                                    </span>
                                    <span class="text-blue-600 font-bold">
                                        Tasa de éxito: <span x-text="(question.success_rate || 0).toFixed(1) + '%'"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Gráfica de opciones -->
                        <div class="mt-4">
                            <canvas :id="'chart-' + question.question_id" width="400" height="100"></canvas>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Botón exportar -->
        <div class="mb-6">
            <button @click="exportToCSV()" 
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow-md">
                Exportar a CSV
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="analytics.js"></script>
</body>
</html>


