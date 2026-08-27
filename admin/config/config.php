<?php
/**
 * 後台管理系統設定檔
 * 本機以 XAMPP 開發時，MySQL 預設帳號 root、密碼空白，可直接使用下方預設值。
 * 若部署到正式主機，請修改 DB_HOST / DB_NAME / DB_USER / DB_PASS。
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'zcv_admin');
define('DB_USER', 'root');
define('DB_PASS', '');

// 專案根目錄（前台靜態網站所在位置，也是 manifest JSON 匯出的目的地）
define('PROJECT_ROOT', dirname(__DIR__, 2));
define('IMAGES_DIR', PROJECT_ROOT . '/images');

define('SITE_NAME', '視野成珍 後台管理系統');

// 允許上傳的圖片副檔名與單檔大小上限（bytes）
define('UPLOAD_ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'webp']);
define('UPLOAD_MAX_SIZE', 8 * 1024 * 1024);
