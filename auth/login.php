<?php
require_once '../config/config.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        try {
            $db = getDB();
            
            // Verificar si la tabla users existe
            $tableCheck = $db->query("SHOW TABLES LIKE 'users'");
            if (!$tableCheck->fetch()) {
                $error = 'La base de datos no está configurada. Por favor ejecuta el archivo "estructura" en MySQL primero. <a href="../install/check-database.php" class="text-blue-600 underline">Verificar base de datos</a>';
            } else {
                $stmt = $db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    // Actualizar último login
                    $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    
                    header('Location: ' . APP_URL . '/dashboard/index.php');
                    exit;
                } else {
                    $error = 'Usuario o contraseña incorrectos';
                }
            }
        } catch (PDOException $e) {
            $error = 'Error de base de datos: ' . $e->getMessage() . '. Por favor verifica que las tablas estén creadas. <a href="../install/check-database.php" class="text-blue-600 underline">Verificar base de datos</a>';
        }
    } else {
        $error = 'Por favor completa todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/gamified.css">
</head>
<body style="background: linear-gradient(135deg, #E0F4FF 0%, #FFF4CC 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px;">
    <div class="w-full max-w-md bounce-in">
        <div class="card-game" style="padding: 40px;">
            <!-- Botón para regresar -->
            <div class="mb-6">
                <a href="<?php echo APP_URL; ?>/index.php" class="btn-game" style="background: var(--gray-200); color: var(--gray-700); padding: 10px 20px; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                    ← Volver al Inicio
                </a>
            </div>
            
            <!-- Logo/Header -->
            <div class="text-center mb-8">
                <img src="<?php echo APP_URL; ?>/assets/avatar/7.png" alt="MeritumQ Mascot" style="width: 120px; height: auto; margin: 0 auto 16px; display: block; filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1));">
                <h1 style="font-size: 36px; font-weight: 900; color: var(--gray-900); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;"><?php echo APP_NAME; ?></h1>
                <p style="font-size: 16px; font-weight: 600; color: var(--gray-700);">¡Bienvenido de nuevo! 👋 Nuestra mascota está emocionada de verte de vuelta</p>
            </div>

            <!-- Formulario -->
            <form id="loginForm" method="POST" action="">
                <?php if ($error): ?>
                    <div id="errorAlert" style="background: #FFE8E8; border: 2px solid var(--duo-red); border-radius: 16px; padding: 16px; margin-bottom: 24px; color: #CC3333; font-weight: 600;">
                        <div class="flex items-center">
                            <span style="font-size: 24px; margin-right: 12px;">⚠️</span>
                            <span><?php echo $error; ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 24px;">
                    <label for="username" style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        👤 Usuario o Email
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required
                        class="input-game"
                        placeholder="usuario@email.com"
                    >
                </div>

                <div style="margin-bottom: 24px;">
                    <label for="password" style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        🔒 Contraseña
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="input-game"
                            placeholder="••••••••"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword()"
                            style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: var(--gray-500); font-size: 20px;"
                        >
                            <span id="eyeIcon">👁️</span>
                        </button>
                    </div>
                </div>

                <button 
                    type="submit"
                    class="btn-game btn-blue"
                    style="width: 100%; margin-top: 32px; font-size: 16px;"
                >
                    🚀 INICIAR SESIÓN
                </button>
            </form>

            <div class="mt-6 text-center">
                <p style="font-size: 14px; font-weight: 600; color: var(--gray-700);">
                    ¿No tienes cuenta? 
                    <a href="register.php" style="color: var(--duo-blue); font-weight: 700; text-decoration: none;">Regístrate aquí</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                eyeIcon.textContent = '👁️';
            }
        }

        // Auto-ocultar error después de 5 segundos
        const errorAlert = document.getElementById('errorAlert');
        if (errorAlert) {
            setTimeout(() => {
                errorAlert.style.transition = 'opacity 0.5s';
                errorAlert.style.opacity = '0';
                setTimeout(() => errorAlert.remove(), 500);
            }, 5000);
        }
    </script>
</body>
</html>

