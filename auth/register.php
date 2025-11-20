<?php
require_once '../config/config.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = sanitize($_POST['full_name'] ?? '');
    
    // Validaciones
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Por favor completa todos los campos requeridos';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido';
    } else {
        try {
            $db = getDB();
            
            // Verificar si la tabla users existe
            $tableCheck = $db->query("SHOW TABLES LIKE 'users'");
            if (!$tableCheck->fetch()) {
                $error = 'La base de datos no está configurada. Por favor ejecuta el archivo "estructura" en MySQL primero. <a href="../install/check-database.php" class="text-blue-600 underline">Verificar base de datos</a>';
            } else {
                // Verificar si el usuario o email ya existen
                $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $checkStmt->execute([$username, $email]);
                
                if ($checkStmt->fetch()) {
                    $error = 'El usuario o email ya está registrado';
                } else {
                    // Crear nuevo usuario
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $role = 'member'; // Por defecto es miembro
                    
                    $insertStmt = $db->prepare("INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
                    
                    if ($insertStmt->execute([$username, $email, $password_hash, $full_name, $role])) {
                        $success = 'Registro exitoso. Puedes iniciar sesión ahora.';
                    } else {
                        $error = 'Error al registrar usuario. Intenta nuevamente.';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Error de base de datos: ' . $e->getMessage() . '. Por favor verifica que las tablas estén creadas. <a href="../install/check-database.php" class="text-blue-600 underline">Verificar base de datos</a>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/gamified.css">
</head>
<body style="background: linear-gradient(135deg, #E5F8E0 0%, #FFF4CC 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px;">
    <div class="w-full max-w-md bounce-in">
        <div class="card-game" style="padding: 40px;">
            <!-- Logo/Header -->
            <div class="text-center mb-8">
                <img src="<?php echo APP_URL; ?>/assets/avatar/8.png" alt="MeritumQ Mascot" style="width: 120px; height: auto; margin: 0 auto 16px; display: block; filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1));">
                <h1 style="font-size: 36px; font-weight: 900; color: var(--gray-900); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;">ÚNETE</h1>
                <p style="font-size: 16px; font-weight: 600; color: var(--gray-700);">¡Comienza tu aventura en <?php echo APP_NAME; ?>! 🚀 Nuestra mascota te está esperando</p>
            </div>

            <!-- Formulario -->
            <form id="registerForm" method="POST" action="">
                <?php if ($error): ?>
                    <div id="errorAlert" style="background: #FFE8E8; border: 2px solid var(--duo-red); border-radius: 16px; padding: 16px; margin-bottom: 24px; color: #CC3333; font-weight: 600;">
                        <div class="flex items-center">
                            <span style="font-size: 24px; margin-right: 12px;">⚠️</span>
                            <span><?php echo $error; ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div id="successAlert" style="background: var(--pastel-green); border: 2px solid var(--duo-green); border-radius: 16px; padding: 16px; margin-bottom: 24px; color: #46A302; font-weight: 600;">
                        <div class="flex items-center">
                            <span style="font-size: 24px; margin-right: 12px;">✅</span>
                            <span><?php echo htmlspecialchars($success); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 20px;">
                    <label for="full_name" style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        🙋 Nombre Completo
                    </label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name"
                        class="input-game"
                        placeholder="Juan Pérez"
                    >
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="username" style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        👤 Usuario *
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required
                        class="input-game"
                        placeholder="usuario123"
                    >
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="email" style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        📧 Email *
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        class="input-game"
                        placeholder="tu@email.com"
                    >
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="password" style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        🔒 Contraseña *
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            minlength="6"
                            class="input-game"
                            placeholder="Mínimo 6 caracteres"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword('password')"
                            style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: var(--gray-500); font-size: 20px;"
                        >
                            <span id="eyeIcon1">👁️</span>
                        </button>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="confirm_password" style="display: block; font-size: 14px; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                        🔐 Confirmar Contraseña *
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required
                            minlength="6"
                            class="input-game"
                            placeholder="Repite tu contraseña"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword('confirm_password')"
                            style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: var(--gray-500); font-size: 20px;"
                        >
                            <span id="eyeIcon2">👁️</span>
                        </button>
                    </div>
                </div>

                <button 
                    type="submit"
                    class="btn-game btn-green"
                    style="width: 100%; margin-top: 32px; font-size: 16px;"
                >
                    ✨ REGISTRARSE
                </button>
            </form>

            <div class="mt-6 text-center">
                <p style="font-size: 14px; font-weight: 600; color: var(--gray-700);">
                    ¿Ya tienes cuenta? 
                    <a href="login.php" style="color: var(--duo-blue); font-weight: 700; text-decoration: none;">Inicia sesión</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const eyeIcon = document.getElementById(fieldId === 'password' ? 'eyeIcon1' : 'eyeIcon2');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                eyeIcon.textContent = '👁️';
            }
        }

        // Validación de contraseñas coincidentes
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Las contraseñas no coinciden',
                    confirmButtonColor: '#3b82f6'
                });
            }
        });

        // Auto-ocultar alertas
        setTimeout(() => {
            const errorAlert = document.getElementById('errorAlert');
            const successAlert = document.getElementById('successAlert');
            if (errorAlert) {
                errorAlert.style.transition = 'opacity 0.5s';
                errorAlert.style.opacity = '0';
                setTimeout(() => errorAlert.remove(), 500);
            }
            if (successAlert) {
                successAlert.style.transition = 'opacity 0.5s';
                successAlert.style.opacity = '0';
                setTimeout(() => successAlert.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>

