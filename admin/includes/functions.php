<?php

function h($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    header('Location: ' . $path);
    exit;
}

function adminUrl($path = '')
{
    return '/admin/' . ltrim($path, '/');
}

function flashSet($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flashGet()
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

function csrfVerify()
{
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        die('表單驗證失敗，請返回上一頁重新整理後再試一次。');
    }
}

/**
 * 將 gallery_categories / gallery_photos / news 目前資料，重新匯出成前台讀取的
 * gallery-manifest.json、bird-manifest.json、news.json，寫回專案根目錄。
 */
function exportManifests(PDO $pdo)
{
    $jsonFlags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    $categories = $pdo->query('SELECT * FROM gallery_categories ORDER BY sort_order ASC')->fetchAll();

    $manifest = [];
    $birdList = [];

    $photoStmt = $pdo->prepare('SELECT file_name FROM gallery_photos WHERE category_id = ? ORDER BY sort_order ASC, id ASC');
    foreach ($categories as $cat) {
        $photoStmt->execute([$cat['id']]);
        $files = array_column($photoStmt->fetchAll(), 'file_name');
        $manifest[$cat['manifest_key']] = $files;
        if ($cat['folder'] === 'gallery-birds') {
            $birdList = $files;
        }
    }

    file_put_contents(PROJECT_ROOT . '/gallery-manifest.json', json_encode($manifest, $jsonFlags) . "\n");
    file_put_contents(PROJECT_ROOT . '/bird-manifest.json', json_encode($birdList, $jsonFlags) . "\n");

    $newsItems = $pdo->query(
        "SELECT id, title, content, cover_image, published_at FROM news WHERE status = 'published' ORDER BY published_at DESC, id DESC"
    )->fetchAll();
    file_put_contents(PROJECT_ROOT . '/news.json', json_encode($newsItems, $jsonFlags) . "\n");
}

function sanitizeFileName($name)
{
    $name = basename($name);
    $name = preg_replace('/[\/\\\\\x00-\x1F]/u', '', $name);
    return trim($name);
}

/**
 * 處理圖片上傳，成功回傳最終檔名；失敗回傳 [false, 錯誤訊息]
 * @return array{0: string|false, 1: string}
 */
function handleImageUpload($fileField, $targetDir)
{
    if (empty($_FILES[$fileField]) || $_FILES[$fileField]['error'] === UPLOAD_ERR_NO_FILE) {
        return [false, ''];
    }

    $file = $_FILES[$fileField];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [false, '檔案上傳失敗（錯誤代碼 ' . $file['error'] . '）。'];
    }

    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return [false, '檔案大小超過上限（' . (int)(UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB）。'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED_EXT, true)) {
        return [false, '不支援的圖片格式，僅接受：' . implode(', ', UPLOAD_ALLOWED_EXT)];
    }

    if (!@getimagesize($file['tmp_name'])) {
        return [false, '檔案內容不是有效的圖片。'];
    }

    $safeName = sanitizeFileName($file['name']);
    if ($safeName === '' || $safeName === '.' . $ext) {
        $safeName = 'photo.' . $ext;
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $destination = $targetDir . '/' . $safeName;
    $base = pathinfo($safeName, PATHINFO_FILENAME);
    $i = 1;
    while (file_exists($destination)) {
        $safeName = $base . '-' . $i . '.' . $ext;
        $destination = $targetDir . '/' . $safeName;
        $i++;
    }

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return [false, '無法將檔案儲存到伺服器，請確認資料夾可寫入。'];
    }

    return [$safeName, ''];
}
