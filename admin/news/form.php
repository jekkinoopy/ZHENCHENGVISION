<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$item = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) {
        flashSet('error', '找不到指定的消息。');
        redirect(adminUrl('news/list.php'));
    }
}

$title = $item ? $item['title'] : '';
$content = $item ? $item['content'] : '';
$status = $item ? $item['status'] : 'draft';
$coverImage = $item ? $item['cover_image'] : null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $status = $_POST['status'] === 'published' ? 'published' : 'draft';

    if ($title === '') {
        $errors[] = '請輸入標題。';
    }
    if ($content === '') {
        $errors[] = '請輸入內容。';
    }

    $targetDir = IMAGES_DIR . '/news';
    list($uploadedName, $uploadError) = handleImageUpload('cover_image', $targetDir);
    if ($uploadError) {
        $errors[] = $uploadError;
    } elseif ($uploadedName) {
        $coverImage = $uploadedName;
    }

    if (!$errors) {
        $publishedAt = $item && $item['published_at'] ? $item['published_at'] : null;
        if ($status === 'published' && !$publishedAt) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        if ($item) {
            $stmt = $pdo->prepare('UPDATE news SET title = ?, content = ?, cover_image = ?, status = ?, published_at = ? WHERE id = ?');
            $stmt->execute([$title, $content, $coverImage, $status, $publishedAt, $item['id']]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO news (title, content, cover_image, status, published_at, created_by) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$title, $content, $coverImage, $status, $publishedAt, $user['id']]);
        }
        exportManifests($pdo);
        flashSet('success', $item ? '消息已更新。' : '消息已新增。');
        redirect(adminUrl('news/list.php'));
    }
}

$pageTitle = $item ? '編輯消息' : '新增消息';
require __DIR__ . '/../includes/layout_start.php';
?>
<h1><?php echo h($pageTitle); ?></h1>
<p class="admin-subtitle">狀態設為「已發布」時，會同步寫入前台的 news.json。</p>

<?php if ($errors) { ?>
<div class="admin-flash admin-flash--error"><?php echo h(implode(' ', $errors)); ?></div>
<?php } ?>

<div class="admin-card">
    <form class="admin-form" method="post" enctype="multipart/form-data" action="<?php echo adminUrl('news/form.php' . ($item ? '?id=' . (int)$item['id'] : '')); ?>">
        <?php echo csrfField(); ?>
        <?php if ($item) { ?><input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>"><?php } ?>

        <div>
            <label for="title">標題</label>
            <input type="text" id="title" name="title" value="<?php echo h($title); ?>" required>
        </div>

        <div>
            <label for="content">內容</label>
            <textarea id="content" name="content" required><?php echo h($content); ?></textarea>
        </div>

        <div>
            <label for="cover_image">封面圖片（選填）</label>
            <input type="file" id="cover_image" name="cover_image" accept=".jpg,.jpeg,.png,.webp">
            <?php if ($coverImage) { ?>
            <div class="field-hint">目前圖片：<?php echo h($coverImage); ?></div>
            <img class="thumb" style="margin-top:8px;width:120px;height:120px;" src="/images/news/<?php echo h($coverImage); ?>" alt="">
            <?php } ?>
        </div>

        <div>
            <label for="status">狀態</label>
            <select id="status" name="status">
                <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>草稿</option>
                <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>已發布</option>
            </select>
        </div>

        <div class="actions">
            <button type="submit" class="btn">儲存</button>
            <a class="btn btn-secondary" href="<?php echo adminUrl('news/list.php'); ?>">取消</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/layout_end.php'; ?>
