<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$categories = $pdo->query('SELECT * FROM gallery_categories ORDER BY sort_order ASC')->fetchAll();
if (!$categories) {
    die('尚未設定任何圖庫分類，請確認 admin/sql/schema.sql 是否已匯入。');
}

$activeSlug = isset($_GET['category']) ? $_GET['category'] : $categories[0]['slug'];
$activeCategory = null;
foreach ($categories as $cat) {
    if ($cat['slug'] === $activeSlug) {
        $activeCategory = $cat;
        break;
    }
}
if (!$activeCategory) {
    $activeCategory = $categories[0];
    $activeSlug = $activeCategory['slug'];
}

$stmt = $pdo->prepare('SELECT * FROM gallery_photos WHERE category_id = ? ORDER BY sort_order ASC, id ASC');
$stmt->execute([$activeCategory['id']]);
$photos = $stmt->fetchAll();

$pageTitle = '圖庫管理';
require __DIR__ . '/../includes/layout_start.php';
?>
<h1>圖庫管理</h1>
<p class="admin-subtitle">管理五大主題分類的作品，新增或調整順序後會自動同步到前台的 gallery-manifest.json。</p>

<div class="admin-tabs">
    <?php foreach ($categories as $cat) { ?>
        <a class="admin-tab <?php echo $cat['slug'] === $activeSlug ? 'active' : ''; ?>"
           href="<?php echo adminUrl('gallery/list.php?category=' . urlencode($cat['slug'])); ?>">
            <?php echo h($cat['name_zh']); ?>
        </a>
    <?php } ?>
</div>

<div class="admin-toolbar">
    <h2 style="margin:0;"><?php echo h($activeCategory['name_zh']); ?>（<?php echo h($activeCategory['folder']); ?>）</h2>
    <a class="btn" href="<?php echo adminUrl('gallery/form.php?category=' . urlencode($activeSlug)); ?>">+ 新增作品</a>
</div>

<div class="admin-card" style="padding:0;">
    <?php if (!$photos) { ?>
        <p class="empty-state">此分類尚無作品，請點選右上「新增作品」上傳圖片。</p>
    <?php } else { ?>
    <table>
        <thead><tr><th>預覽</th><th>檔名</th><th>排序</th><th style="width:220px;">操作</th></tr></thead>
        <tbody>
        <?php foreach ($photos as $i => $photo) { ?>
            <tr>
                <td><img class="thumb" src="/images/<?php echo h($activeCategory['folder']); ?>/<?php echo h($photo['file_name']); ?>" alt=""></td>
                <td><?php echo h($photo['file_name']); ?></td>
                <td><?php echo (int)$photo['sort_order']; ?></td>
                <td>
                    <form class="inline-form" method="post" action="<?php echo adminUrl('gallery/move.php'); ?>">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="id" value="<?php echo (int)$photo['id']; ?>">
                        <input type="hidden" name="category" value="<?php echo h($activeSlug); ?>">
                        <input type="hidden" name="direction" value="up">
                        <button type="submit" class="btn btn-secondary btn-small" <?php echo $i === 0 ? 'disabled' : ''; ?>>&uarr;</button>
                    </form>
                    <form class="inline-form" method="post" action="<?php echo adminUrl('gallery/move.php'); ?>">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="id" value="<?php echo (int)$photo['id']; ?>">
                        <input type="hidden" name="category" value="<?php echo h($activeSlug); ?>">
                        <input type="hidden" name="direction" value="down">
                        <button type="submit" class="btn btn-secondary btn-small" <?php echo $i === count($photos) - 1 ? 'disabled' : ''; ?>>&darr;</button>
                    </form>
                    <a class="btn btn-secondary btn-small" href="<?php echo adminUrl('gallery/form.php?id=' . (int)$photo['id']); ?>">編輯</a>
                    <form class="inline-form" method="post" action="<?php echo adminUrl('gallery/delete.php'); ?>" onsubmit="return confirm('確定要刪除這張作品嗎？圖檔也會一併移除。');">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="id" value="<?php echo (int)$photo['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-small">刪除</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <?php } ?>
</div>
<?php require __DIR__ . '/../includes/layout_end.php'; ?>
