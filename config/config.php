<?php
/**
 * Configuración General de la Aplicación
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la aplicación
define('APP_NAME', 'MeritumQ');
define('APP_URL', 'http://localhost/MeritumQ');
define('BASE_PATH', __DIR__ . '/..');

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Incluir conexión a base de datos
require_once __DIR__ . '/database.php';

/**
 * Verificar si el usuario está autenticado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Obtener información del usuario actual (con caché en sesión)
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    // Usar caché de sesión si existe y es válido
    if (isset($_SESSION['user_cache']) && isset($_SESSION['user_cache_time'])) {
        // Cache válido por 5 minutos
        if (time() - $_SESSION['user_cache_time'] < 300) {
            return $_SESSION['user_cache'];
        }
    }
    
    // Si no hay caché o expiró, consultar BD
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, email, full_name, role, is_active FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Guardar en caché
    if ($user) {
        $_SESSION['user_cache'] = $user;
        $_SESSION['user_cache_time'] = time();
    }
    
    return $user;
}

/**
 * Verificar si el usuario es administrador
 */
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

/**
 * Requerir autenticación
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Requerir rol de administrador
 */
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/dashboard/index.php');
        exit;
    }
}

/**
 * Generar código único verificando en base de datos
 */
function generateUniqueCode($prefix = '', $type = 'quiz') {
    $db = getDB();
    $max_attempts = 10;
    $attempt = 0;
    
    do {
        // Generar código: prefijo + timestamp + random
        $code = $prefix . strtoupper(substr(uniqid(), -6) . substr(md5(time() . rand()), 0, 4));
        $code = str_replace(['-', '_'], '', $code); // Limpiar caracteres especiales
        
        // Verificar si existe en quizzes
        $stmt = $db->prepare("SELECT id FROM quizzes WHERE code = ?");
        $stmt->execute([$code]);
        $exists_quiz = $stmt->fetch();
        
        // Verificar si existe en workshops
        $stmt = $db->prepare("SELECT id FROM workshops WHERE code = ?");
        $stmt->execute([$code]);
        $exists_workshop = $stmt->fetch();
        
        // Verificar si existe en qr_codes
        $stmt = $db->prepare("SELECT id FROM qr_codes WHERE code = ?");
        $stmt->execute([$code]);
        $exists_qr = $stmt->fetch();
        
        $attempt++;
        
        // Si no existe en ninguna tabla, retornar el código
        if (!$exists_quiz && !$exists_workshop && !$exists_qr) {
            return $code;
        }
        
    } while ($attempt < $max_attempts);
    
    // Si después de varios intentos no se encuentra uno único, usar timestamp
    return $prefix . strtoupper(substr(md5(time() . rand() . uniqid()), 0, 10));
}

/**
 * Sanitizar entrada
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

