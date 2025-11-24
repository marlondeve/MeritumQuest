<?php
/**
 * Script de prueba de conexión a la base de datos
 * Ejecuta este archivo para verificar que la configuración es correcta
 */

require_once 'config.php';

echo "<h1>Prueba de Conexión - MeritumQuest</h1>";

try {
    $db = getDBConnection();
    echo "<p style='color: green;'>✅ Conexión a la base de datos exitosa</p>";
    
    // Verificar tablas
    $tables = ['quizzes', 'participants', 'quiz_modes', 'quiz_questions', 
               'quiz_question_options', 'quiz_sessions', 'quiz_attempts', 'quiz_attempt_answers'];
    
    echo "<h2>Verificación de Tablas:</h2>";
    echo "<ul>";
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<li style='color: green;'>✅ Tabla '$table' existe</li>";
            } else {
                echo "<li style='color: red;'>❌ Tabla '$table' NO existe - Ejecuta el script 'estructura' en MySQL</li>";
            }
        } catch (Exception $e) {
            echo "<li style='color: red;'>❌ Error al verificar '$table': " . $e->getMessage() . "</li>";
        }
    }
    
    echo "</ul>";
    
    // Verificar permisos de directorios
    echo "<h2>Verificación de Directorios:</h2>";
    echo "<ul>";
    
    $dirs = [
        'uploads' => UPLOAD_DIR,
        'cache' => JSON_CACHE_DIR
    ];
    
    foreach ($dirs as $name => $path) {
        if (is_dir($path) && is_writable($path)) {
            echo "<li style='color: green;'>✅ Directorio '$name' existe y es escribible</li>";
        } else {
            echo "<li style='color: orange;'>⚠️ Directorio '$name' necesita permisos de escritura</li>";
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
                echo "<li style='color: blue;'>📁 Intentando crear directorio '$name'...</li>";
            }
        }
    }
    
    echo "</ul>";
    
    echo "<h2 style='color: green;'>✅ Sistema listo para usar</h2>";
    echo "<p><a href='admin/'>Ir al Panel de Administración</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error de conexión: " . $e->getMessage() . "</p>";
    echo "<p>Verifica la configuración en <code>config.php</code></p>";
}
?>


