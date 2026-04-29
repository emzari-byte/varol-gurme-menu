<?php
// HTTP
define('HTTP_SERVER', 'https://example.com/deneme/menu/admin/');
define('HTTP_CATALOG', 'https://example.com/deneme/menu/');

// HTTPS
define('HTTPS_SERVER', 'https://example.com/deneme/menu/admin/');
define('HTTPS_CATALOG', 'https://example.com/deneme/menu/');

// DIR
define('DIR_APPLICATION', '/home/USERNAME/public_html/deneme/menu/admin/');
define('DIR_SYSTEM', '/home/USERNAME/public_html/deneme/menu/system/');
define('DIR_IMAGE', '/home/USERNAME/public_html/deneme/menu/image/');
define('DIR_STORAGE', '/home/USERNAME/public_html/deneme/menu/storage/');
define('DIR_CATALOG', '/home/USERNAME/public_html/deneme/menu/catalog/');

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
