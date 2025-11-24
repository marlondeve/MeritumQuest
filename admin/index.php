<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrador - MeritumQuest</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        [x-cloak] { display: none !important; }
        body {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-6 md:py-8" x-data="adminApp()" x-cloak>
        <!-- Header -->
        <div class="modern-card p-6 md:p-8 mb-6 animate-fade-in gradient-blue text-white">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Panel Administrador</h1>
            <p class="text-white/90 text-sm md:text-base">Gestiona tus quizzes y analiza resultados</p>
        </div>

        <!-- Botón crear nuevo quiz -->
        <div class="mb-6 animate-slide-in-right">
            <button @click="showCreateQuizModal = true" 
                    class="btn-primary ripple text-white font-bold py-3 px-6 md:px-8 rounded-lg shadow-lg text-sm md:text-base w-full md:w-auto">
                <span class="relative z-10">+ Crear Nuevo Quiz</span>
            </button>
        </div>

        <!-- Lista de quizzes -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
            <template x-for="(quiz, index) in quizzes" :key="quiz.id">
                <div class="modern-card p-5 md:p-6 card-hover animate-fade-in" 
                     :style="`animation-delay: ${index * 0.1}s`">
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2" x-text="quiz.title"></h3>
                    <p class="text-gray-600 text-xs md:text-sm mb-4 line-clamp-2" x-text="quiz.description || 'Sin descripción'"></p>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="badge badge-blue text-xs" x-text="quiz.code"></span>
                        <span class="badge badge-white text-xs" x-text="quiz.points_per_question + ' pts'"></span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2">
                        <button @click="editQuiz(quiz)" 
                                class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-2 md:px-3 rounded-lg text-xs font-semibold transition-all hover:scale-105">
                            Editar
                        </button>
                        <button @click="manageQuestions(quiz)" 
                                class="bg-green-500 hover:bg-green-600 text-white py-2 px-2 md:px-3 rounded-lg text-xs font-semibold transition-all hover:scale-105">
                            Preguntas
                        </button>
                        <button @click="viewAnalytics(quiz)" 
                                class="bg-purple-500 hover:bg-purple-600 text-white py-2 px-2 md:px-3 rounded-lg text-xs font-semibold transition-all hover:scale-105">
                            Analíticas
                        </button>
                        <button @click="configureModes(quiz)" 
                                class="bg-indigo-500 hover:bg-indigo-600 text-white py-2 px-2 md:px-3 rounded-lg text-xs font-semibold transition-all hover:scale-105">
                            Modos
                        </button>
                        <button @click="startSession(quiz)" 
                                class="col-span-2 md:col-span-1 lg:col-span-1 btn-primary text-white py-2 px-2 md:px-3 rounded-lg text-xs font-semibold transition-all hover:scale-105">
                            Iniciar
                        </button>
                    </div>
                </div>
            </template>
        </div>
        
        <div x-show="quizzes.length === 0" class="text-center py-12 animate-fade-in">
            <div class="text-6xl mb-4">📝</div>
            <p class="text-gray-600 text-lg">No hay quizzes creados aún</p>
            <p class="text-gray-500 text-sm mt-2">Crea tu primer quiz para comenzar</p>
        </div>

        <!-- Modal Crear/Editar Quiz -->
        <div x-show="showCreateQuizModal" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
             @click.self="showCreateQuizModal = false">
            <div class="modern-card p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto animate-scale-in">
                <h2 class="text-2xl font-bold mb-4" x-text="editingQuiz ? 'Editar Quiz' : 'Crear Nuevo Quiz'"></h2>
                
                <form @submit.prevent="saveQuiz()">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Título *</label>
                        <input type="text" x-model="quizForm.title" required
                               class="w-full px-4 py-2 modern-input rounded-lg">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Descripción</label>
                        <textarea x-model="quizForm.description" rows="3"
                                  class="w-full px-4 py-2 modern-input rounded-lg"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">Puntos por pregunta</label>
                            <input type="number" x-model="quizForm.points_per_question" min="1" required
                                   class="w-full px-4 py-2 modern-input rounded-lg">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">Usar tiempo por pregunta</label>
                            <select x-model="quizForm.use_time_per_question"
                                    class="w-full px-4 py-2 modern-input rounded-lg">
                                <option :value="0">No</option>
                                <option :value="1">Sí</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" 
                                class="flex-1 btn-primary ripple text-white font-bold py-2 px-4 rounded-lg">
                            <span class="relative z-10">Guardar</span>
                        </button>
                        <button type="button" @click="showCreateQuizModal = false"
                                class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition-all hover:scale-105">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Gestionar Preguntas -->
        <div x-show="showQuestionsModal" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
             @click.self="showQuestionsModal = false">
            <div class="modern-card p-6 max-w-4xl w-full max-h-[90vh] overflow-y-auto animate-scale-in">
                <h2 class="text-2xl font-bold mb-4">Gestionar Preguntas</h2>
                <p class="text-gray-600 mb-4" x-text="'Quiz: ' + currentQuiz.title"></p>
                
                <button @click="showQuestionForm = true; editingQuestion = null"
                        class="mb-4 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition-all hover:scale-105 shadow-lg">
                    + Agregar Pregunta
                </button>
                
                <div class="space-y-4">
                    <template x-for="(question, index) in questions" :key="question.id">
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="font-bold" x-text="'Pregunta ' + (index + 1)"></h3>
                                    <p x-text="question.text"></p>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="editQuestion(question)"
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">
                                        Editar
                                    </button>
                                    <button @click="deleteQuestion(question.id)"
                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                            <div class="text-sm text-gray-600">
                                <span x-text="question.options.length + ' opciones'"></span>
                                <span x-if="question.time_limit_sec" x-text="' | Tiempo: ' + question.time_limit_sec + 's'"></span>
                            </div>
                        </div>
                    </template>
                </div>
                
                <button @click="showQuestionsModal = false"
                        class="mt-4 w-full bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg">
                    Cerrar
                </button>
            </div>
        </div>

        <!-- Modal Configurar Modos -->
        <div x-show="showModesModal" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
             @click.self="showModesModal = false">
            <div class="modern-card p-6 max-w-3xl w-full max-h-[90vh] overflow-y-auto animate-scale-in">
                <h2 class="text-2xl font-bold mb-4">Configurar Modos del Quiz</h2>
                <p class="text-gray-600 mb-4" x-text="'Quiz: ' + currentQuiz.title"></p>
                
                <div class="space-y-6">
                    <!-- Modo Live -->
                    <div class="border rounded-lg p-4">
                        <h3 class="text-xl font-bold mb-4">Modo Evento en Vivo</h3>
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" x-model="modesForm.live.enabled" class="mr-2">
                                <span>Habilitar modo evento en vivo</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Modo Workshop -->
                    <div class="border rounded-lg p-4">
                        <h3 class="text-xl font-bold mb-4">Modo Taller</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="modesForm.workshop.enabled" class="mr-2">
                                    <span>Habilitar modo taller</span>
                                </label>
                            </div>
                            
                            <div x-show="modesForm.workshop.enabled" class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Disponible desde</label>
                                    <input type="datetime-local" x-model="modesForm.workshop.available_from"
                                           class="w-full px-4 py-2 border rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Disponible hasta</label>
                                    <input type="datetime-local" x-model="modesForm.workshop.available_to"
                                           class="w-full px-4 py-2 border rounded-lg">
                                </div>
                            </div>
                            
                            <div x-show="modesForm.workshop.enabled">
                                <label class="block text-gray-700 font-bold mb-2">Límite de intentos</label>
                                <input type="number" x-model="modesForm.workshop.max_attempts" min="1"
                                       class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            
                            <div x-show="modesForm.workshop.enabled" class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="modesForm.workshop.show_correction_at_end" class="mr-2">
                                    <span>Mostrar correcciones al final</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="modesForm.workshop.show_explanations" class="mr-2">
                                    <span>Mostrar explicaciones</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="modesForm.workshop.show_ranking" class="mr-2">
                                    <span>Mostrar ranking</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="modesForm.workshop.public_ranking" class="mr-2">
                                    <span>Ranking público</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="modesForm.workshop.feedback_immediate" class="mr-2">
                                    <span>Feedback inmediato</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button @click="saveModes()" 
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
                        Guardar
                    </button>
                    <button @click="showModesModal = false"
                            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Formulario Pregunta -->
        <div x-show="showQuestionForm" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
             @click.self="showQuestionForm = false">
            <div class="modern-card p-6 max-w-3xl w-full max-h-[90vh] overflow-y-auto animate-scale-in">
                <h2 class="text-2xl font-bold mb-4" x-text="editingQuestion ? 'Editar Pregunta' : 'Nueva Pregunta'"></h2>
                
                <form @submit.prevent="saveQuestion()">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Texto de la pregunta *</label>
                        <textarea x-model="questionForm.text" rows="3" required
                                  class="w-full px-4 py-2 modern-input rounded-lg"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">URL Imagen</label>
                            <input type="url" x-model="questionForm.image_url"
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">URL Video</label>
                            <input type="url" x-model="questionForm.video_url"
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">URL Audio</label>
                            <input type="url" x-model="questionForm.audio_url"
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">Tiempo límite (segundos)</label>
                            <input type="number" x-model="questionForm.time_limit_sec" min="0"
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">Múltiples respuestas</label>
                            <select x-model="questionForm.allow_multiple_answers"
                                    class="w-full px-4 py-2 border rounded-lg">
                                <option :value="0">No</option>
                                <option :value="1">Sí</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Explicación (opcional)</label>
                        <textarea x-model="questionForm.explanation" rows="2"
                                  class="w-full px-4 py-2 border rounded-lg"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Opciones de respuesta *</label>
                        <div class="space-y-2">
                            <template x-for="(option, index) in questionForm.options" :key="index">
                                <div class="flex gap-2 items-center">
                                    <input type="text" x-model="option.text" required
                                           :placeholder="'Opción ' + (index + 1)"
                                           class="flex-1 px-4 py-2 border rounded-lg">
                                    <label class="flex items-center">
                                        <input type="checkbox" x-model="option.is_correct"
                                               class="mr-2">
                                        <span>Correcta</span>
                                    </label>
                                    <button type="button" @click="questionForm.options.splice(index, 1)"
                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded">
                                        Eliminar
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="questionForm.options.push({text: '', is_correct: false})"
                                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                                + Agregar Opción
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" 
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
                            Guardar
                        </button>
                        <button type="button" @click="showQuestionForm = false"
                                class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="admin.js"></script>
</body>
</html>

