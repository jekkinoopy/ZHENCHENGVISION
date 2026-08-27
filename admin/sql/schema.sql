-- 視野成珍 後台管理系統 資料庫結構
-- 使用方式（XAMPP）：開啟 phpMyAdmin 或執行
--   mysql -u root < admin/sql/schema.sql
-- 即可建立資料庫、資料表與預設管理員帳號。

CREATE DATABASE IF NOT EXISTS zcv_admin DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE zcv_admin;

CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(50) NOT NULL,
    role ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gallery_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(30) NOT NULL UNIQUE,
    folder VARCHAR(50) NOT NULL UNIQUE,
    manifest_key VARCHAR(50) NOT NULL,
    name_zh VARCHAR(50) NOT NULL,
    name_en VARCHAR(50) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gallery_photos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gallery_photos_category FOREIGN KEY (category_id) REFERENCES gallery_categories(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_category_file (category_id, file_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS news (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    content TEXT NOT NULL,
    cover_image VARCHAR(255) NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_news_created_by FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 五大圖庫分類，對應現有 images/ 資料夾與 gallery-manifest.json 的 key
INSERT IGNORE INTO gallery_categories (slug, folder, manifest_key, name_zh, name_en, sort_order) VALUES
('birds', 'gallery-birds', 'gallery-birds', '羽翼視界', 'Birds', 1),
('culture', 'gallery-culture', 'gallery-culture', '文化影像', 'Culture & Life', 2),
('insects', 'gallery-insects', 'gallery-insects', '昆蟲生態', 'Insects', 3),
('landscapes', 'gallery-landscapes', 'gallery-landscapes', '地景風光', 'Landscapes', 4),
('flora', 'gallery-flora', 'gallery-flora', '植物生態', 'Flora', 5);

-- 預設管理員帳號：帳號 admin ／ 密碼 0516
INSERT IGNORE INTO admin_users (username, password_hash, display_name, role) VALUES
('admin', '$2y$10$Yf8Vkl85A9BPO1b709DXWOHfDL6TGkiLeJW19AnFP/4ABdeIKTyD6', '系統管理員', 'admin');
