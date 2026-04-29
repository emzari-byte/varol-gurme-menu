<?php
// HTTP
define('HTTP_SERVER', 'https://example.com/menu/admin/');
define('HTTP_CATALOG', 'https://example.com/menu/');

// HTTPS
define('HTTPS_SERVER', 'https://example.com/menu/admin/');
define('HTTPS_CATALOG', 'https://example.com/menu/');

// DIR
define('DIR_APPLICATION', '/home/USERNAME/public_html/menu/admin/');
define('DIR_SYSTEM', '/home/USERNAME/public_html/menu/system/');
define('DIR_IMAGE', '/home/USERNAME/public_html/menu/image/');
define('DIR_STORAGE', '/home/USERNAME/public_html/menu/storage/');
define('DIR_CATALOG', '/home/USERNAME/public_html/menu/catalog/');

define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'DB_USER');
define('DB_PASSWORD', 'DB_PASSWORD');
define('DB_DATABASE', 'DB_NAME');
define('DB_PORT', '3306');
define('DB_PREFIX', 'menu_');

// OpenCart API
define('OPENCART_SERVER', 'https://www.opencart.com/');
