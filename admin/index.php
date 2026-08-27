<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$categories = $pdo->query('
    SELECT c.*, COUNT(p.id) AS photo_count
    FROM gallery_categories c
    LEFT JOIN gallery_photos p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.sort_order ASC
')->fetchAll();

$totalPhotos = array_sum(array_column($categories, 'photo_count'));
$newsPublished = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE status = 'published'")->fetchColumn();
$newsDraft = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE status = 'draft'")->fetchColumn();
$accountCount = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();

$manifestPath = PROJECT_ROOT . '/gallery-manifest.json';
$lastExport = file_exists($manifestPath) ? date('Y-m-d H:i:s', filemtime($manifestPath)) : '尚未匯出';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export') {
    csrfVerify();
    exportManifests($pdo);
    flashSet('success', '已重新匯出 gallery-manifest.json、bird-manifest.json、news.json 到專案根目錄。');
    redirect(adminUrl('index.php'));
}

$pageTitle = '儀表板';
require __DIR__ . '/includes/layout_start.php';
?>
<h1>儀表板</h1>
<p class="admin-subtitle">歡迎回來，<?php echo h($user['display_name']); ?>。這裡是網站內容總覽。</p>

<div class="admin-stat-grid">
    <div class="admin-stat-card"><div class="num"><?php echo $totalPhotos; ?></div><div class="label">圖庫作品總數</div></div>
    <div class="admin-stat-card"><div class="num"><?php echo $newsPublished; ?></div><div class="label">已發布消息</div></div>
    <div class="admin-stat-card"><div class="num"><?php echo $newsDraft; ?></div><div class="label">草稿消息</div></div>
    <div class="admin-stat-card"><div class="num"><?php echo $accountCount; ?></div><div class="label">後台帳號</div></div>
</div>

<div class="admin-card">
    <h2>各分類圖庫作品數</h2>
    <table>
        <thead><tr><th>分類</th><th>資料夾</th><th>作品數</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($categories as $cat) { ?>
            <tr>
                <td><?php echo h($cat['name_zh']); ?></td>
                <td><?php echo h($cat['folder']); ?></td>
                <td><?php echo (int)$cat['photo_count']; ?></td>
                <td><a href="<?php echo adminUrl('gallery/list.php?category=' . urlencode($cat['slug'])); ?>">管理 &rarr;</a></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<div class="admin-card">
    <h2>發布到正式網站</h2>
    <p class="admin-subtitle" style="margin-bottom:12px;">
        本站前台為靜態網站，圖庫與消息內容以 JSON 檔案讀取。新增圖庫作品或發布消息時系統會自動匯出，也可以按下方按鈕手動重新匯出。
        匯出後請將專案根目錄下的 <code>gallery-manifest.json</code>、<code>bird-manifest.json</code>、<code>news.json</code>（以及上傳的圖片）一併 commit 並 push，前台網站才會更新。
    </p>
    <p class="admin-subtitle">上次匯出時間：<?php echo h($lastExport); ?></p>
    <form method="post" action="<?php echo adminUrl('index.php'); ?>">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="export">
        <button type="submit" class="btn">立即重新匯出</button>
    </form>
</div>
<?php require __DIR__ . '/includes/layout_end.php'; ?>
