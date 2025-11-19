<?php
/**
 * Script de instalación automática de la base de datos
 * Ejecuta el archivo estructura.sql automáticamente
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$success = false;
$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        $db = getDB();
        
        // Leer el archivo estructura
        $sql_file = __DIR__ . '/../estructura';
        
        if (!file_exists($sql_file)) {
            throw new Exception('El archivo "estructura" no existe');
        }
        
        $sql_content = file_get_contents($sql_file);
        
        // Remover comentarios y líneas vacías
        $sql_content = preg_replace('/--.*$/m', '', $sql_content);
        $sql_content = preg_replace('/^\s*$/m', '', $sql_content);
        
        // Dividir en sentencias individuales
        $statements = array_filter(
            array_map('trim', explode(';', $sql_content)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^USE\s+/i', $stmt);
            }
        );
        
        // Ejecutar cada sentencia
        $db->beginTransaction();
        
        try {
            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    $db->exec($statement);
                }
            }
            
            $db->commit();
            $success = true;
            $messages[] = 'Base de datos instalada correctamente';
            
        } catch (PDOException $e) {
            $db->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        $errors[] = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación de Base de Datos - MeritumQ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fas fa-database mr-2 text-blue-600"></i>
                Instalación de Base de Datos
            </h1>
            
            <?php if ($success): ?>
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">
                    <p class="text-green-800">
                        <i class="fas fa-check-circle mr-2"></i>
                        <strong>¡Instalación exitosa!</strong>
                    </p>
                    <ul class="mt-2 space-y-1">
                        <?php foreach ($messages as $msg): ?>
                            <li class="text-green-700"><?php echo htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="p-4 bg-blue-50 border-l-4 border-blue-500 rounded mb-6">
                    <p class="text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        La base de datos está lista. Puedes:
                    </p>
                    <div class="mt-3 space-x-4">
                        <a href="check-database.php" class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-check mr-2"></i>Verificar Instalación
                        </a>
                        <a href="../auth/register.php" class="inline-block bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-user-plus mr-2"></i>Crear Primer Usuario
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                        <p class="text-red-800 font-semibold mb-2">
                            <i class="fas fa-exclamation-circle mr-2"></i>Errores:
                        </p>
                        <ul class="list-disc list-inside space-y-1">
                            <?php foreach ($errors as $error): ?>
                                <li class="text-red-700"><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded">
                    <h3 class="text-yellow-800 font-semibold mb-2">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Advertencia
                    </h3>
                    <p class="text-yellow-800 mb-2">
                        Este script creará todas las tablas necesarias en la base de datos <strong>meritumquest</strong>.
                    </p>
                    <p class="text-yellow-800">
                        <strong>Nota:</strong> Si las tablas ya existen, serán eliminadas y recreadas. Todos los datos existentes se perderán.
                    </p>
                </div>
                
                <form method="POST" class="space-y-4">
                    <div class="flex items-center space-x-4">
                        <input type="checkbox" id="confirm" name="confirm" required
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="confirm" class="text-gray-700">
                            Confirmo que entiendo que esto eliminará las tablas existentes si existen
                        </label>
                    </div>
                    
                    <div>
                        <button type="submit" name="install" value="1"
                            class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-semibold">
                            <i class="fas fa-database mr-2"></i>Instalar Base de Datos
                        </button>
                        <a href="check-database.php" class="ml-4 text-gray-600 hover:text-gray-800">
                            Cancelar y verificar estado
                        </a>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                <h3 class="font-semibold text-gray-800 mb-2">Información de conexión:</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li><strong>Host:</strong> <?php echo DB_HOST; ?></li>
                    <li><strong>Base de datos:</strong> <?php echo DB_NAME; ?></li>
                    <li><strong>Usuario:</strong> <?php echo DB_USER; ?></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
