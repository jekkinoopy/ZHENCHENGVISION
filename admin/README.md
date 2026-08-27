# 後台管理系統（PHP + MySQL）

依「網頁設計乙級技術士技能檢定」慣用做法建置的後台管理系統：Session 登入驗證、MySQL 資料庫、圖庫／消息／帳號的新增修改刪除（CRUD），並將資料匯出為前台讀取的 JSON 檔案。

前台網站本身維持純靜態（部署在 GitHub Pages），因此後台與前台採「離線編輯、匯出發布」的方式整合：後台在本機（或任一支援 PHP+MySQL 的環境）管理內容，儲存時自動把資料匯出成 `gallery-manifest.json`、`bird-manifest.json`、`news.json` 寫回專案根目錄，之後將這些檔案（與上傳的圖片）一併 commit、push，前台就會顯示最新內容。

## 環境需求

- PHP 7.4 以上（含 PDO、pdo_mysql、fileinfo 擴充，皆為 XAMPP 預設內建）
- MySQL / MariaDB
- 建議直接使用 XAMPP：本機已安裝於 `C:\xampp`

## 安裝步驟

1. 啟動 XAMPP 的 **Apache** 與 **MySQL**（或使用下方「快速測試」的內建伺服器方式，不需要 Apache）。
2. 匯入資料庫結構：
   - 用 phpMyAdmin 匯入 `admin/sql/schema.sql`，或
   - 終端機執行：`mysql -u root < admin/sql/schema.sql`
   - 這會建立 `zcv_admin` 資料庫、四張資料表，以及五個圖庫分類、一組預設管理員帳號。
3. 匯入現有圖庫資料（把目前已上線的圖庫作品同步進資料庫）：
   ```
   php admin/sql/seed_from_manifest.php
   ```
4. 確認 `admin/config/config.php` 的資料庫連線設定符合你的環境（XAMPP 預設 `root` 帳號、空密碼可直接使用，不需修改）。
5. 用瀏覽器開啟 `/admin/login.php` 登入。

### 預設管理員帳號

- 帳號：`admin`
- 密碼：`0516`

**請登入後立即到「帳號管理」變更密碼**，尤其是要上傳到公開主機時。

## 快速測試（不需要 Apache）

在專案根目錄執行：

```
php -S localhost:8000
```

接著開啟 `http://localhost:8000/admin/login.php`；前台網站可同時用 `http://localhost:8000/index.html` 檢視，兩者共用同一份 `images/`、`gallery-manifest.json`。

## 功能

- **登入 / 登出**：Session 驗證、密碼以 `password_hash` 加密儲存，表單皆有 CSRF Token。
- **圖庫管理**：五大分類（羽翼視界／文化影像／昆蟲生態／地景風光／植物生態）作品的新增、編輯、排序、刪除，上傳圖片會直接存進對應的 `images/gallery-*/` 資料夾。
- **最新消息**：標題、內容、封面圖片、草稿／發布狀態管理。
- **帳號管理**（僅管理員角色可用）：新增、編輯、刪除後台帳號，並保護「至少保留一位管理員」。
- **匯出**：新增／編輯／刪除任何內容時自動重新匯出 JSON；儀表板也提供「立即重新匯出」按鈕。

## 目錄結構

```
admin/
  config/      資料庫與網站設定（含 .htaccess 阻擋直接存取）
  includes/    共用的登入驗證、輔助函式、版面
  gallery/     圖庫 CRUD
  news/        最新消息 CRUD
  accounts/    帳號 CRUD（管理員限定）
  sql/         資料庫結構與匯入工具（含 .htaccess 阻擋直接存取）
  assets/      後台樣式
```

## 部署到正式主機時的注意事項

- 更新 `admin/config/config.php` 的 `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` 為正式主機的資料庫資訊。
- 確認 `admin/config/.htaccess`、`admin/sql/.htaccess` 生效（Apache），避免設定檔與 SQL 檔被直接下載；若主機為 Nginx，需另外在站台設定中封鎖這兩個路徑。
- 正式站台請務必啟用 HTTPS，並將 `session.cookie_secure` 等 session 安全設定打開。
