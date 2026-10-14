<?php
define('DB_NAME', 'taskflow');
define('DB_USER', 'taskflow');
define('DB_PASSWORD', 'taskflow-local-dev');
define('DB_HOST', 'localhost:/workspace/.runtime/mysql.sock');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
define('AUTH_KEY', 'taskflow-local-auth-3dc44883');
define('SECURE_AUTH_KEY', 'taskflow-local-secure-78a2bbcd');
define('LOGGED_IN_KEY', 'taskflow-local-login-a4eb182f');
define('NONCE_KEY', 'taskflow-local-nonce-c332f52a');
define('AUTH_SALT', 'taskflow-local-salt-a4ab7e10');
define('SECURE_AUTH_SALT', 'taskflow-local-secure-salt-b171ba9e');
define('LOGGED_IN_SALT', 'taskflow-local-login-salt-b49adc1e');
define('NONCE_SALT', 'taskflow-local-nonce-salt-782ddc82');
define('TASKFLOW_DEMO', true);
// Individual assets pass through the same-origin URL filters in the demo plugin.
define('CONCATENATE_SCRIPTS', false);
if (isset($_SERVER['HTTP_HOST'])) {
    $scheme = (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'http') === 'https') ? 'https' : 'http';
    define('WP_HOME', $scheme . '://' . $_SERVER['HTTP_HOST']);
    define('WP_SITEURL', WP_HOME);
    if ($scheme === 'https') $_SERVER['HTTPS'] = 'on';
}
define('WP_DEBUG', false);
$table_prefix = 'wp_';
if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
require_once ABSPATH . 'wp-settings.php';
