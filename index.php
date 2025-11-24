<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MeritumQuest - Sistema de Quizzes Interactivos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 50%, #000000 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(37, 99, 235, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(30, 64, 175, 0.3) 0%, transparent 50%);
            animation: pulse 15s ease-in-out infinite;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .floating {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .card-entrance {
            animation: fadeIn 0.6s ease-out backwards;
        }
        
        .card-entrance:nth-child(1) { animation-delay: 0.1s; }
        .card-entrance:nth-child(2) { animation-delay: 0.2s; }
        .card-entrance:nth-child(3) { animation-delay: 0.3s; }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8 md:py-16 hero-content">
        <div class="max-w-5xl mx-auto text-center">
            <div class="floating mb-8">
                <h1 class="text-5xl md:text-7xl font-bold text-white mb-4 animate-fade-in" style="text-shadow: 0 4px 6px rgba(0,0,0,0.3);">
                    MeritumQuest
                </h1>
                <p class="text-xl md:text-3xl text-white/90 mb-2 animate-slide-in-left">Sistema de Quizzes Interactivos</p>
                <div class="w-24 h-1 bg-white mx-auto mt-4 animate-scale-in"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8 mt-12 md:mt-16">
                <a href="admin/" class="modern-card p-6 md:p-8 card-entrance card-hover group">
                    <div class="text-5xl md:text-6xl mb-4 group-hover:scale-110 transition-transform duration-300">👨‍💼</div>
                    <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-3">Panel Admin</h2>
                    <p class="text-gray-600 text-sm md:text-base">Gestiona tus quizzes y analiza resultados</p>
                    <div class="mt-4 text-blue-600 font-semibold group-hover:translate-x-2 transition-transform duration-300">
                        Acceder →
                    </div>
                </a>
                
                <a href="student/" class="modern-card p-6 md:p-8 card-entrance card-hover group">
                    <div class="text-5xl md:text-6xl mb-4 group-hover:scale-110 transition-transform duration-300">👥</div>
                    <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-3">Estudiante</h2>
                    <p class="text-gray-600 text-sm md:text-base">Participa en quizzes con código o QR</p>
                    <div class="mt-4 text-blue-600 font-semibold group-hover:translate-x-2 transition-transform duration-300">
                        Acceder →
                    </div>
                </a>
                
                <a href="presenter/" class="modern-card p-6 md:p-8 card-entrance card-hover group">
                    <div class="text-5xl md:text-6xl mb-4 group-hover:scale-110 transition-transform duration-300">📺</div>
                    <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-3">Presentador</h2>
                    <p class="text-gray-600 text-sm md:text-base">Pantalla de proyección para eventos</p>
                    <div class="mt-4 text-blue-600 font-semibold group-hover:translate-x-2 transition-transform duration-300">
                        Acceder →
                    </div>
                </a>
            </div>
            
            <div class="mt-12 md:mt-16 text-white animate-fade-in" style="animation-delay: 0.4s;">
                <p class="text-lg md:text-xl mb-2">Sistema completo para crear y gestionar quizzes interactivos</p>
                <p class="text-sm md:text-base text-white/80">Soporta eventos en vivo y modo taller autónomo</p>
            </div>
        </div>
    </div>
</body>
</html>

