<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'prakriti_care');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// JWT Secret Key (change this in production!)
define('JWT_SECRET', 'your-secret-key-change-this-in-production');

// API Base URL
define('API_BASE_URL', 'http://localhost/Prakriti Care/api');

// CORS settings
define('ALLOWED_ORIGINS', ['http://localhost', 'http://127.0.0.1']);

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Error reporting (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Timezone
date_default_timezone_set('Asia/Kolkata');
?>