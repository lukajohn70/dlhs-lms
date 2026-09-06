<?php
/**
 * Performance Optimization Configuration
 * Central configuration for all performance-related settings
 */

require_once __DIR__ . '/env.php';

// Define optimization constants
define('ENABLE_QUERY_CACHE', true);
define('ENABLE_SESSION_DB', dlhs_env_bool('DLHS_ENABLE_SESSION_DB', true));
define('ENABLE_GZIP_COMPRESSION', true);
define('ENABLE_PERFORMANCE_MONITORING', false); // Set to true in development
define('CACHE_EXPIRY_TIME', 300); // 5 minutes default

// PHP Performance Settings
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '30');
ini_set('max_input_time', '60');

// Enable output buffering with compression
if (ENABLE_GZIP_COMPRESSION && extension_loaded('zlib') && !ob_get_level()) {
    ob_start('ob_gzhandler');
}

// Error reporting configuration
if (dlhs_env_first(array('DLHS_APP_ENV', 'APP_ENV'), 'local') === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Session optimization
if (ENABLE_SESSION_DB) {
    // Database sessions will be initialized in session_handler.php
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);
} else {
    // File-based session optimization
    ini_set('session.save_path', __DIR__ . '/../sessions');
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 1000);
}

// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);

// Opcache settings (if available)
// Note: These settings should be in php.ini for best effect
// We just check if opcache is enabled here
if (function_exists('opcache_get_status')) {
    $opcacheStatus = @opcache_get_status(false);
    // Opcache is already enabled via php.ini - that's good!
    // Runtime changes to opcache settings are not possible
    // To configure opcache, edit php.ini with these recommended settings:
    // opcache.enable=1
    // opcache.memory_consumption=128
    // opcache.interned_strings_buffer=8
    // opcache.max_accelerated_files=10000
    // opcache.revalidate_freq=60
}

// Database connection pooling settings
define('DB_MAX_CONNECTIONS', 100);
define('DB_CONNECTION_TIMEOUT', 30);
define('DB_USE_PERSISTENT', true);

// Cache settings
define('CACHE_DRIVER', 'database'); // Options: database, file, memcached, redis
define('CACHE_PREFIX', 'dlhs_');

// Exam timer optimization
define('EXAM_TIMER_SYNC_INTERVAL', 30); // Sync every 30 seconds instead of 1
define('EXAM_TIMER_CLIENT_SIDE', true); // Use client-side countdown

// File upload optimization
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_UPLOAD_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip']);

// Query optimization
define('ENABLE_PREPARED_STATEMENTS', true);
define('ENABLE_QUERY_LOGGING', false); // Enable in development only

// Asset optimization
define('ENABLE_ASSET_MINIFICATION', false); // Set to true with build process
define('ENABLE_CDN', false); // Set to true when using CDN
define('CDN_URL', ''); // CDN base URL

// Performance monitoring
define('LOG_SLOW_QUERIES', true);
define('SLOW_QUERY_THRESHOLD', 1.0); // 1 second
define('LOG_SLOW_PAGES', true);
define('SLOW_PAGE_THRESHOLD', 2.0); // 2 seconds

// Rate limiting (for API endpoints)
define('ENABLE_RATE_LIMITING', true);
define('RATE_LIMIT_REQUESTS', 60); // Requests per minute
define('RATE_LIMIT_WINDOW', 60); // Time window in seconds

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Cache control headers for static assets
function setStaticAssetHeaders() {
    $extension = pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION);
    $cacheableExtensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'ico', 'woff', 'woff2', 'ttf', 'svg'];
    
    if (in_array($extension, $cacheableExtensions)) {
        header('Cache-Control: public, max-age=31536000'); // 1 year
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    }
}

/**
 * Initialize performance optimizations
 */
function initializeOptimizations() {
    // Create necessary directories
    $directories = [
        __DIR__ . '/../logs',
        __DIR__ . '/../sessions',
        __DIR__ . '/../cache'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
    
    // Set up error logging
    if (!file_exists(__DIR__ . '/../logs/error.log')) {
        @touch(__DIR__ . '/../logs/error.log');
    }
}

// Auto-initialize
initializeOptimizations();

/**
 * Get optimization status
 */
function getOptimizationStatus() {
    return [
        'query_cache' => ENABLE_QUERY_CACHE,
        'session_db' => ENABLE_SESSION_DB,
        'gzip_compression' => ENABLE_GZIP_COMPRESSION && extension_loaded('zlib'),
        'opcache' => function_exists('opcache_get_status') && opcache_get_status() !== false,
        'performance_monitoring' => ENABLE_PERFORMANCE_MONITORING,
        'persistent_connections' => DB_USE_PERSISTENT,
        'timer_optimization' => EXAM_TIMER_CLIENT_SIDE,
        'rate_limiting' => ENABLE_RATE_LIMITING
    ];
}

/**
 * Display optimization info (for admin panel)
 */
function displayOptimizationInfo() {
    $status = getOptimizationStatus();
    
    echo "<div style='background:#f0f0f0;padding:15px;margin:10px;border-radius:5px;'>";
    echo "<h3>🚀 Performance Optimizations Status</h3>";
    echo "<ul style='list-style:none;padding:0;'>";
    
    foreach ($status as $feature => $enabled) {
        $icon = $enabled ? '✅' : '❌';
        $label = ucwords(str_replace('_', ' ', $feature));
        echo "<li>$icon <strong>$label:</strong> " . ($enabled ? 'Enabled' : 'Disabled') . "</li>";
    }
    
    echo "</ul>";
    echo "</div>";
}

?>
