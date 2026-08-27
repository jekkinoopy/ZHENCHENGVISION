<?php
/**
 * 一次性匯入工具：讀取專案根目錄現有的 gallery-manifest.json，
 * 把目前已上線的圖庫作品匯入 gallery_photos 資料表，讓後台與現況同步。
 * 用法（於專案根目錄執行一次即可）：
 *   php admin/sql/seed_from_manifest.php
 */

require_once __DIR__ . '/../config/db.php';

$manifestPath = PROJECT_ROOT . '/gallery-manifest.json';
if (!file_exists($manifestPath)) {
    fwrite(STDERR, "找不到 gallery-manifest.json：$manifestPath\n");
    exit(1);
}

$manifestRaw = file_get_contents($manifestPath);
$manifestRaw = preg_replace('/^\xEF\xBB\xBF/', '', $manifestRaw); // 去除 UTF-8 BOM
$manifest = json_decode($manifestRaw, true);
if (!is_array($manifest)) {
    fwrite(STDERR, "gallery-manifest.json 格式錯誤\n");
    exit(1);
}

$categories = $pdo->query('SELECT * FROM gallery_categories')->fetchAll();
$categoryByKey = [];
foreach ($categories as $cat) {
    $categoryByKey[$cat['manifest_key']] = $cat;
}

$insertStmt = $pdo->prepare('INSERT IGNORE INTO gallery_photos (category_id, file_name, sort_order) VALUES (?, ?, ?)');

$total = 0;
foreach ($manifest as $manifestKey => $files) {
    if (!isset($categoryByKey[$manifestKey]) || !is_array($files)) {
        continue;
    }
    $category = $categoryByKey[$manifestKey];
    $order = 0;
    foreach ($files as $fileName) {
        $insertStmt->execute([$category['id'], $fileName, $order]);
        $order += 10;
        $total++;
    }
    echo "已匯入分類 {$category['name_zh']}（{$manifestKey}）：" . count($files) . " 筆\n";
}

echo "完成，共匯入 {$total} 筆圖庫作品資料。\n";
