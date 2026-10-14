<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file('/workspace/wordpress' . $path)) return false;
if (str_starts_with($path, '/wp-admin')) {
    if (is_dir('/workspace/wordpress' . $path)) {
        require '/workspace/wordpress' . rtrim($path, '/') . '/index.php';
        return;
    }
}
require '/workspace/wordpress/index.php';
