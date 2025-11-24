<?php
// Configuración de Base de Datos
define('DB_HOST', '5.183.11.230');
define('DB_NAME', 'kartti');
define('DB_USER', 'root');
define('DB_PASS', 'Platino5.');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('JSON_CACHE_DIR', __DIR__ . '/cache/');

// Crear directorios si no existen
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
if (!file_exists(JSON_CACHE_DIR)) {
    mkdir(JSON_CACHE_DIR, 0755, true);
}

// Conexión a la base de datos
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            die("Error de conexión a la base de datos");
        }
    }
    
    return $conn;
}

// Función para generar códigos únicos
function generateUniqueCode($length = 8) {
    return strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, $length));
}

// Función para limpiar cache JSON
function clearQuizCache($quizId) {
    $cacheFile = JSON_CACHE_DIR . "quiz_{$quizId}.json";
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
    }
}

// Función para obtener quiz desde cache o BD
function getQuizFromCache($quizId) {
    $cacheFile = JSON_CACHE_DIR . "quiz_{$quizId}.json";
    
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        // Cache válido por 5 minutos
        if (time() - $data['cached_at'] < 300) {
            return $data['quiz'];
        }
    }
    
    return null;
}

// Función para guardar quiz en cache
function saveQuizToCache($quizId, $quizData) {
    $cacheFile = JSON_CACHE_DIR . "quiz_{$quizId}.json";
    file_put_contents($cacheFile, json_encode([
        'quiz' => $quizData,
        'cached_at' => time()
    ]));
}

// Headers para JSON
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Validar sesión (si implementas autenticación)
function requireAuth() {
    // Por ahora sin autenticación, pero puedes agregarla aquí
    return true;
}
?>


