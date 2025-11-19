<?php
/**
 * Script de verificación de base de datos
 * Verifica si las tablas necesarias existen
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Base de Datos - MeritumQ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fas fa-database mr-2 text-blue-600"></i>
                Verificación de Base de Datos
            </h1>
            
            <?php
            try {
                $db = getDB();
                
                // Lista de tablas requeridas
                $required_tables = [
                    'users',
                    'quizzes',
                    'workshops',
                    'qr_codes',
                    'participants',
                    'quiz_modes',
                    'quiz_questions',
                    'quiz_question_options',
                    'quiz_sessions',
                    'quiz_attempts',
                    'quiz_attempt_answers'
                ];
                
                // Verificar conexión
                echo '<div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">';
                echo '<p class="text-green-800"><i class="fas fa-check-circle mr-2"></i><strong>Conexión exitosa</strong> a la base de datos</p>';
                echo '</div>';
                
                // Verificar tablas
                $missing_tables = [];
                $existing_tables = [];
                
                foreach ($required_tables as $table) {
                    $stmt = $db->prepare("SHOW TABLES LIKE ?");
                    $stmt->execute([$table]);
                    
                    if ($stmt->fetch()) {
                        $existing_tables[] = $table;
                    } else {
                        $missing_tables[] = $table;
                    }
                }
                
                // Mostrar resultados
                if (empty($missing_tables)) {
                    echo '<div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">';
                    echo '<p class="text-green-800"><i class="fas fa-check-circle mr-2"></i><strong>Todas las tablas existen</strong></p>';
                    echo '</div>';
                    
                    echo '<div class="mb-6">';
                    echo '<h2 class="text-lg font-semibold text-gray-800 mb-3">Tablas encontradas:</h2>';
                    echo '<ul class="list-disc list-inside space-y-1">';
                    foreach ($existing_tables as $table) {
                        echo '<li class="text-gray-700"><i class="fas fa-table mr-2 text-green-600"></i>' . htmlspecialchars($table) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                    
                    echo '<div class="p-4 bg-blue-50 border-l-4 border-blue-500 rounded">';
                    echo '<p class="text-blue-800"><i class="fas fa-info-circle mr-2"></i>La base de datos está lista. Puedes <a href="../auth/register.php" class="text-blue-600 hover:text-blue-800 font-semibold underline">registrarte aquí</a> o <a href="../auth/login.php" class="text-blue-600 hover:text-blue-800 font-semibold underline">iniciar sesión</a>.</p>';
                    echo '</div>';
                    
                } else {
                    echo '<div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded">';
                    echo '<p class="text-red-800"><i class="fas fa-exclamation-triangle mr-2"></i><strong>Faltan tablas en la base de datos</strong></p>';
                    echo '</div>';
                    
                    echo '<div class="mb-6">';
                    echo '<h2 class="text-lg font-semibold text-gray-800 mb-3">Tablas faltantes:</h2>';
                    echo '<ul class="list-disc list-inside space-y-1 mb-4">';
                    foreach ($missing_tables as $table) {
                        echo '<li class="text-red-700"><i class="fas fa-times-circle mr-2"></i>' . htmlspecialchars($table) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                    
                    if (!empty($existing_tables)) {
                        echo '<div class="mb-6">';
                        echo '<h2 class="text-lg font-semibold text-gray-800 mb-3">Tablas existentes:</h2>';
                        echo '<ul class="list-disc list-inside space-y-1">';
                        foreach ($existing_tables as $table) {
                            echo '<li class="text-gray-700"><i class="fas fa-table mr-2 text-green-600"></i>' . htmlspecialchars($table) . '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    }
                    
                    echo '<div class="p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded">';
                    echo '<h3 class="text-yellow-800 font-semibold mb-2"><i class="fas fa-info-circle mr-2"></i>Instrucciones:</h3>';
                    echo '<ol class="list-decimal list-inside space-y-2 text-yellow-800">';
                    echo '<li>Abre tu cliente MySQL (phpMyAdmin, MySQL Workbench, etc.)</li>';
                    echo '<li>Selecciona la base de datos <strong>meritumquest</strong></li>';
                    echo '<li>Ejecuta el contenido del archivo <strong>estructura</strong> que está en la raíz del proyecto</li>';
                    echo '<li>Vuelve a ejecutar esta verificación</li>';
                    echo '</ol>';
                    echo '</div>';
                }
                
                // Verificar si hay usuarios
                if (empty($missing_tables)) {
                    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
                    $user_count = $stmt->fetch()['total'];
                    
                    if ($user_count == 0) {
                        echo '<div class="mt-6 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">';
                        echo '<p class="text-blue-800"><i class="fas fa-user-plus mr-2"></i>No hay usuarios registrados. <a href="../auth/register.php" class="text-blue-600 hover:text-blue-800 font-semibold underline">Crea el primer usuario aquí</a>.</p>';
                        echo '</div>';
                    } else {
                        echo '<div class="mt-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">';
                        echo '<p class="text-green-800"><i class="fas fa-users mr-2"></i>Hay <strong>' . $user_count . '</strong> usuario(s) registrado(s). <a href="../auth/login.php" class="text-green-600 hover:text-green-800 font-semibold underline">Inicia sesión aquí</a>.</p>';
                        echo '</div>';
                    }
                }
                
            } catch (PDOException $e) {
                echo '<div class="p-4 bg-red-50 border-l-4 border-red-500 rounded">';
                echo '<p class="text-red-800"><i class="fas fa-exclamation-circle mr-2"></i><strong>Error de conexión:</strong></p>';
                echo '<p class="text-red-700 mt-2">' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
                
                echo '<div class="mt-6 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded">';
                echo '<h3 class="text-yellow-800 font-semibold mb-2">Verifica:</h3>';
                echo '<ul class="list-disc list-inside space-y-1 text-yellow-800">';
                echo '<li>Que el servidor MySQL esté corriendo</li>';
                echo '<li>Que las credenciales en <code>config/database.php</code> sean correctas</li>';
                echo '<li>Que la base de datos <strong>meritumquest</strong> exista</li>';
                echo '<li>Que el servidor permita conexiones remotas (si aplica)</li>';
                echo '</ul>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</body>
</html>
