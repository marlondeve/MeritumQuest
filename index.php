<?php
require_once 'config/config.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Plataforma de Quizzes y Talleres Interactivos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/gamified.css">
    <style>
        .hero-pattern {
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(28, 176, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(88, 204, 2, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(255, 200, 0, 0.1) 0%, transparent 50%);
        }
        
        .feature-card {
            background: white;
            border: 2px solid #E5E5E5;
            border-radius: 24px;
            padding: 32px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 3px 0 #E5E5E5;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 11px 0 #E5E5E5;
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        /* Asegurar que las variables CSS funcionen */
        :root {
            --duo-blue: #1CB0F6;
            --duo-blue-dark: #1391C4;
            --duo-blue-light: #4FC3F7;
            --duo-green: #58CC02;
            --duo-yellow: #FFC800;
            --duo-yellow-light: #FFD900;
            --duo-pink: #FF9EC4;
            --gray-50: #FAFAFA;
            --gray-200: #E5E5E5;
            --gray-300: #AFAFAF;
            --gray-400: #8B8B8B;
            --gray-700: #4B4B4B;
            --gray-900: #1F1F1F;
        }
    </style>
</head>
<body style="background: #FAFAFA;">
    <!-- Navbar -->
    <nav class="header-game sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-500 rounded-xl flex items-center justify-center shadow-lg">
                        <span style="font-size: 24px;">🎓</span>
                    </div>
                    <span style="font-size: 24px; font-weight: 900; color: var(--gray-900); letter-spacing: 1px;"><?php echo APP_NAME; ?></span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="auth/login.php" class="btn-game btn-blue" style="padding: 12px 24px; font-size: 14px;">
                        🚀 INICIAR SESIÓN
                    </a>
                    <a href="auth/register.php" class="btn-game btn-green" style="padding: 12px 24px; font-size: 14px;">
                        ✨ REGISTRARSE
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-pattern py-20" style="background-color: #FAFAFA;">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="slide-up">
                    <div style="margin-bottom: 24px;">
                        <span class="badge-game blue" style="font-size: 14px;">
                            🎯 PLATAFORMA EDUCATIVA
                        </span>
                    </div>
                    <h1 style="font-size: 56px; font-weight: 900; color: var(--gray-900); line-height: 1.1; margin-bottom: 24px;">
                        Crea Quizzes y<br>
                        Talleres <span style="color: var(--duo-blue);">Interactivos</span>
                    </h1>
                    <p style="font-size: 20px; font-weight: 600; color: var(--gray-700); margin-bottom: 32px; line-height: 1.6;">
                        La plataforma todo-en-uno para gestionar cuestionarios, organizar talleres y generar códigos QR. ¡Gamifica la educación! 🚀
                    </p>
                    <div class="flex flex-wrap gap-4 items-end">
                        <a href="auth/register.php" class="btn-game btn-blue" style="padding: 18px 32px; font-size: 16px;">
                            ✨ COMENZAR GRATIS
                        </a>
                        <div style="flex: 1; min-width: 280px;">
                            <label style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                                🔑 Ingresa el código del Quiz
                            </label>
                            <form id="joinQuizForm" onsubmit="joinQuiz(event)" style="display: flex; gap: 8px;">
                                <input 
                                    type="text" 
                                    id="quizCode" 
                                    name="code" 
                                    placeholder="Ej: QUIZ-ABC123" 
                                    required
                                    class="input-game" 
                                    style="flex: 1; font-family: monospace; font-weight: 700; text-transform: uppercase;"
                                    maxlength="20">
                                <button type="submit" class="btn-game btn-green" style="padding: 18px 24px; font-size: 16px; white-space: nowrap;">
                                    🚀 ENTRAR
                                </button>
                            </form>
                        </div>
                    </div>
                    <div style="margin-top: 32px; display: flex; align-items: center; gap: 24px; font-weight: 700; color: var(--gray-700);">
                        <div style="text-align: center;">
                            <div style="font-size: 32px; color: var(--duo-blue);">❓</div>
                            <div style="font-size: 14px; margin-top: 4px;">Quizzes</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 32px; color: var(--duo-green);">📚</div>
                            <div style="font-size: 14px; margin-top: 4px;">Talleres</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 32px; color: var(--duo-yellow);">🔳</div>
                            <div style="font-size: 14px; margin-top: 4px;">QR Codes</div>
                        </div>
                    </div>
                </div>
                
                <div class="bounce-in" style="animation-delay: 0.3s;">
                    <div style="position: relative; text-align: center;">
                        <img src="<?php echo APP_URL; ?>/assets/avatar/1.png" alt="MeritumQ Mascot" class="floating" style="max-width: 400px; width: 100%; height: auto; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1));">
                        <div class="emoji-sticker" style="position: absolute; top: 10%; left: -5%; font-size: 60px; animation-delay: 0.5s;">
                            ✨
                        </div>
                        <div class="emoji-sticker" style="position: absolute; top: 70%; right: -5%; font-size: 60px; animation-delay: 1s;">
                            🚀
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" style="padding: 80px 0; background: white;">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16 slide-up">
                <span style="font-size: 64px; margin-bottom: 16px; display: inline-block;">
                    <img src="<?php echo APP_URL; ?>/assets/avatar/15.png" alt="MeritumQ Mascot" class="static" style="max-width: 100px; width: 100%; height: auto; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1));">
                </span>
                <h2 style="font-size: 48px; font-weight: 900; color: var(--gray-900); margin-bottom: 16px;">
                    ¿Qué hace MeritumQ?
                </h2>
                <p style="font-size: 20px; font-weight: 600; color: var(--gray-700); max-width: 700px; margin: 0 auto;">
                    Una plataforma completa para crear experiencias educativas interactivas
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="feature-card bounce-in" style="animation-delay: 0.1s;">
                    <img src="<?php echo APP_URL; ?>/assets/avatar/5.png" alt="Quizzes" style="width: 120px; height: auto; margin: 0 auto 20px; display: block; filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1));">
                    <h3 style="font-size: 24px; font-weight: 900; color: var(--gray-900); margin-bottom: 12px;">
                        Quizzes Dinámicos
                    </h3>
                    <p style="font-size: 16px; font-weight: 600; color: var(--gray-700); line-height: 1.6;">
                        Crea cuestionarios con múltiples opciones, personaliza puntos y genera códigos únicos de acceso. ¡Haz que el aprendizaje sea divertido y gamificado!
                    </p>
                </div>

                <div class="feature-card bounce-in" style="animation-delay: 0.2s;">
                    <img src="<?php echo APP_URL; ?>/assets/avatar/17.png" alt="Talleres" style="width: 120px; height: auto; margin: 0 auto 20px; display: block; filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1));">
                    <h3 style="font-size: 24px; font-weight: 900; color: var(--gray-900); margin-bottom: 12px;">
                        Talleres Organizados
                    </h3>
                    <p style="font-size: 16px; font-weight: 600; color: var(--gray-700); line-height: 1.6;">
                        Gestiona talleres con fechas, límites de participantes y controla la disponibilidad. Organiza tus eventos educativos de manera profesional.
                    </p>
                </div>

                <div class="feature-card bounce-in" style="animation-delay: 0.3s;">
                    <img src="<?php echo APP_URL; ?>/assets/avatar/16.png" alt="QR Codes" style="width: 120px; height: auto; margin: 0 auto 20px; display: block; filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1));">
                    <h3 style="font-size: 24px; font-weight: 900; color: var(--gray-900); margin-bottom: 12px;">
                        Códigos QR
                    </h3>
                    <p style="font-size: 16px; font-weight: 600; color: var(--gray-700); line-height: 1.6;">
                        Genera códigos QR automáticamente para compartir tus quizzes y talleres fácilmente. Acceso rápido y sencillo para todos.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section style="padding: 80px 0; background: var(--gray-50);">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16 slide-up">
                <span style="font-size: 64px; margin-bottom: 16px; display: inline-block;">
                    <img src="<?php echo APP_URL; ?>/assets/avatar/9.png" alt="MeritumQ Mascot" class="static" style="max-width: 150px; width: 100%; height: auto; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1));">
                </span>
                <h2 style="font-size: 48px; font-weight: 900; color: var(--gray-900); margin-bottom: 16px;">
                    ¿Cómo Funciona?
                </h2>
                <p style="font-size: 20px; font-weight: 600; color: var(--gray-700);">
                    Empezar es súper fácil
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 max-w-6xl mx-auto">
                <div class="text-center slide-up" style="animation-delay: 0.1s;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--duo-blue) 0%, var(--duo-blue-light) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 4px 0 var(--duo-blue-dark);">
                        <span style="font-size: 32px;">1️⃣</span>
                    </div>
                    <h3 style="font-size: 20px; font-weight: 900; color: var(--gray-900); margin-bottom: 8px;">
                        Regístrate
                    </h3>
                    <p style="font-size: 14px; font-weight: 600; color: var(--gray-700);">
                        Crea tu cuenta gratis en segundos
                    </p>
                </div>

                <div class="text-center slide-up" style="animation-delay: 0.2s;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--duo-green) 0%, #89E219 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 4px 0 #46A302;">
                        <span style="font-size: 32px;">2️⃣</span>
                    </div>
                    <h3 style="font-size: 20px; font-weight: 900; color: var(--gray-900); margin-bottom: 8px;">
                        Crea Contenido
                    </h3>
                    <p style="font-size: 14px; font-weight: 600; color: var(--gray-700);">
                        Diseña quizzes y talleres personalizados
                    </p>
                </div>

                <div class="text-center slide-up" style="animation-delay: 0.3s;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--duo-yellow) 0%, var(--duo-yellow-light) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 4px 0 #D9A500;">
                        <span style="font-size: 32px;">3️⃣</span>
                    </div>
                    <h3 style="font-size: 20px; font-weight: 900; color: var(--gray-900); margin-bottom: 8px;">
                        Comparte
                    </h3>
                    <p style="font-size: 14px; font-weight: 600; color: var(--gray-700);">
                        Genera códigos QR para acceso fácil
                    </p>
                </div>

                <div class="text-center slide-up" style="animation-delay: 0.4s;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--duo-pink) 0%, #FFB3D9 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 4px 0 #E57AB8;">
                        <span style="font-size: 32px;">4️⃣</span>
                    </div>
                    <h3 style="font-size: 20px; font-weight: 900; color: var(--gray-900); margin-bottom: 8px;">
                        ¡Aprende!
                    </h3>
                    <p style="font-size: 14px; font-weight: 600; color: var(--gray-700);">
                        Gestiona y monitorea resultados
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits -->
    <section style="padding: 80px 0; background: white;">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="slide-up" style="text-align: center;">
                    <img src="<?php echo APP_URL; ?>/assets/avatar/5.png" alt="Características" style="max-width: 300px; width: 100%; height: auto; margin: 0 auto; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1));">
                </div>

                <div class="slide-up" style="animation-delay: 0.2s;">
                    <span style="font-size: 48px; margin-bottom: 16px; display: inline-block;">💡</span>
                    <h2 style="font-size: 42px; font-weight: 900; color: var(--gray-900); margin-bottom: 24px;">
                        Características Principales
                    </h2>
                    
                    <div class="space-y-6">
                        <div style="display: flex; gap: 16px; align-items: start;">
                            <div style="width: 48px; height: 48px; background: var(--pastel-blue); border: 2px solid var(--duo-blue); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span style="font-size: 24px;">⚡</span>
                            </div>
                            <div>
                                <h3 style="font-size: 20px; font-weight: 900; color: var(--gray-900); margin-bottom: 4px;">
                                    Creación Rápida
                                </h3>
                                <p style="font-size: 16px; font-weight: 600; color: var(--gray-700);">
                                    Crea quizzes y talleres en minutos con nuestra interfaz intuitiva
                                </p>
                            </div>
                        </div>

                        <div style="display: flex; gap: 16px; align-items: start;">
                            <div style="width: 48px; height: 48px; background: var(--pastel-green); border: 2px solid var(--duo-green); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span style="font-size: 24px;">🎨</span>
                            </div>
                            <div>
                                <h3 style="font-size: 20px; font-weight: 900; color: var(--gray-900); margin-bottom: 4px;">
                                    Totalmente Personalizable
                                </h3>
                                <p style="font-size: 16px; font-weight: 600; color: var(--gray-700);">
                                    Configura puntos, tiempo, opciones múltiples y más
                                </p>
                            </div>
                        </div>

                        <div style="display: flex; gap: 16px; align-items: start;">
                            <div style="width: 48px; height: 48px; background: var(--pastel-yellow); border: 2px solid var(--duo-yellow); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span style="font-size: 24px;">📊</span>
                            </div>
                            <div>
                                <h3 style="font-size: 20px; font-weight: 900; color: var(--gray-900); margin-bottom: 4px;">
                                    Seguimiento en Tiempo Real
                                </h3>
                                <p style="font-size: 16px; font-weight: 600; color: var(--gray-700);">
                                    Monitorea intentos, participantes y estadísticas
                                </p>
                            </div>
                        </div>

                        <div style="display: flex; gap: 16px; align-items: start;">
                            <div style="width: 48px; height: 48px; background: var(--pastel-pink); border: 2px solid var(--duo-pink); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span style="font-size: 24px;">🔒</span>
                            </div>
                            <div>
                                <h3 style="font-size: 20px; font-weight: 900; color: var(--gray-900); margin-bottom: 4px;">
                                    Seguro y Confiable
                                </h3>
                                <p style="font-size: 16px; font-weight: 600; color: var(--gray-700);">
                                    Sistema de roles: administradores y miembros
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Use Cases -->
    <section style="padding: 80px 0; background: var(--gray-50);">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16 slide-up">
                <span style="font-size: 64px; margin-bottom: 16px; display: inline-block;">🎯</span>
                <h2 style="font-size: 48px; font-weight: 900; color: var(--gray-900); margin-bottom: 16px;">
                    Ideal Para
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="card-game bounce-in" style="animation-delay: 0.1s;">
                    <span style="font-size: 56px; margin-bottom: 16px; display: inline-block;">👨‍🏫</span>
                    <h3 style="font-size: 24px; font-weight: 900; color: var(--gray-900); margin-bottom: 12px;">
                        Profesores & Educadores
                    </h3>
                    <p style="font-size: 16px; font-weight: 600; color: var(--gray-700);">
                        Crea evaluaciones interactivas y gestiona talleres educativos de manera eficiente
                    </p>
                </div>

                <div class="card-game bounce-in" style="animation-delay: 0.2s;">
                    <span style="font-size: 56px; margin-bottom: 16px; display: inline-block;">🏢</span>
                    <h3 style="font-size: 24px; font-weight: 900; color: var(--gray-900); margin-bottom: 12px;">
                        Empresas & Capacitación
                    </h3>
                    <p style="font-size: 16px; font-weight: 600; color: var(--gray-700);">
                        Organiza sesiones de formación y evalúa el conocimiento de tu equipo
                    </p>
                </div>

                <div class="card-game bounce-in" style="animation-delay: 0.3s;">
                    <span style="font-size: 56px; margin-bottom: 16px; display: inline-block;">🎓</span>
                    <h3 style="font-size: 24px; font-weight: 900; color: var(--gray-900); margin-bottom: 12px;">
                        Instituciones Educativas
                    </h3>
                    <p style="font-size: 16px; font-weight: 600; color: var(--gray-700);">
                        Plataforma completa para gestionar actividades académicas y eventos
                    </p>
                </div>

                <div class="card-game bounce-in" style="animation-delay: 0.4s;">
                    <span style="font-size: 56px; margin-bottom: 16px; display: inline-block;">🎉</span>
                    <h3 style="font-size: 24px; font-weight: 900; color: var(--gray-900); margin-bottom: 12px;">
                        Eventos & Comunidades
                    </h3>
                    <p style="font-size: 16px; font-weight: 600; color: var(--gray-700);">
                        Organiza trivia, competencias y actividades grupales con facilidad
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section style="padding: 100px 0; background: linear-gradient(135deg, var(--duo-blue) 0%, var(--duo-blue-light) 100%); color: white;">
        <div class="container mx-auto px-6 text-center">
            <div class="bounce-in">
                <img src="<?php echo APP_URL; ?>/assets/avatar/14.png" alt="¡Comienza ahora!" style="max-width: 200px; width: 100%; height: auto; margin: 0 auto 24px; display: block; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.2));">
                <h2 style="font-size: 48px; font-weight: 900; margin-bottom: 20px; text-transform: uppercase;">
                    ¿Listo para Comenzar?
                </h2>
                <p style="font-size: 22px; font-weight: 600; margin-bottom: 40px; opacity: 0.95;">
                    Únete a MeritumQ y transforma la forma en que enseñas y aprendes. ¡Nuestra mascota está lista para acompañarte en esta aventura educativa!
                </p>
                <div class="flex flex-wrap gap-4 justify-center">
                    <a href="auth/register.php" class="btn-game btn-green" style="padding: 20px 40px; font-size: 18px;">
                        ✨ CREAR CUENTA GRATIS
                    </a>
                    <a href="auth/login.php" class="btn-game" style="background: white; color: var(--duo-blue); padding: 20px 40px; font-size: 18px; box-shadow: 0 4px 0 rgba(255, 255, 255, 0.3);">
                        👋 YA TENGO CUENTA
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: var(--gray-900); color: white; padding: 40px 0;">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <span style="font-size: 32px;">🎓</span>
                        <span style="font-size: 24px; font-weight: 900; letter-spacing: 1px;"><?php echo APP_NAME; ?></span>
                    </div>
                    <p style="font-size: 14px; font-weight: 600; color: var(--gray-300); line-height: 1.6;">
                        Plataforma educativa gamificada para crear quizzes, organizar talleres y generar códigos QR
                    </p>
                </div>

                <div>
                    <h3 style="font-size: 16px; font-weight: 900; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 1px;">
                        🔗 Enlaces Rápidos
                    </h3>
                    <ul style="font-size: 14px; font-weight: 600; color: var(--gray-300); line-height: 2;">
                        <li><a href="auth/login.php" style="text-decoration: none; color: var(--gray-300); transition: color 0.2s;" onmouseover="this.style.color='var(--duo-blue)'" onmouseout="this.style.color='var(--gray-300)'">Iniciar Sesión</a></li>
                        <li><a href="auth/register.php" style="text-decoration: none; color: var(--gray-300); transition: color 0.2s;" onmouseover="this.style.color='var(--duo-blue)'" onmouseout="this.style.color='var(--gray-300)'">Registrarse</a></li>
                        <li><a href="#features" style="text-decoration: none; color: var(--gray-300); transition: color 0.2s;" onmouseover="this.style.color='var(--duo-blue)'" onmouseout="this.style.color='var(--gray-300)'">Características</a></li>
                    </ul>
                </div>

                <div>
                    <h3 style="font-size: 16px; font-weight: 900; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 1px;">
                        📧 Contacto
                    </h3>
                    <p style="font-size: 14px; font-weight: 600; color: var(--gray-300); line-height: 1.8;">
                        Sistema de gestión educativa<br>
                        gamificado y moderno
                    </p>
                </div>
            </div>

            <div style="border-top: 2px solid var(--gray-700); padding-top: 24px; text-align: center;">
                <p style="font-size: 14px; font-weight: 600; color: var(--gray-400);">
                    © <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos los derechos reservados. Hecho con 💙
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scroll para los enlaces
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Función para unirse a un quiz con código
        function joinQuiz(event) {
            event.preventDefault();
            const codeInput = document.getElementById('quizCode');
            const code = codeInput.value.trim().toUpperCase();
            
            if (code) {
                window.location.href = 'join.php?code=' + encodeURIComponent(code);
            }
        }
    </script>
</body>
</html>
