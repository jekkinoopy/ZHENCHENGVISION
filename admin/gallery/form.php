<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$categories = $pdo->query('SELECT * FROM gallery_categories ORDER BY sort_order ASC')->fetchAll();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$photo = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM gallery_photos WHERE id = ?');
    $stmt->execute([$id]);
    $photo = $stmt->fetch();
    if (!$photo) {
        flashSet('error', '找不到指定的作品。');
        redirect(adminUrl('gallery/list.php'));
    }
}

$presetSlug = isset($_GET['category']) ? $_GET['category'] : null;
$defaultCategoryId = $photo ? $photo['category_id'] : null;
if (!$defaultCategoryId && $presetSlug) {
    foreach ($categories as $cat) {
        if ($cat['slug'] === $presetSlug) {
            $defaultCategoryId = $cat['id'];
        }
    }
}
if (!$defaultCategoryId) {
    $defaultCategoryId = $categories[0]['id'];
}

$sortOrder = $photo ? (int)$photo['sort_order'] : 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $categoryId = (int)$_POST['category_id'];
    $sortOrder = (int)$_POST['sort_order'];

    $category = null;
    foreach ($categories as $cat) {
        if ((int)$cat['id'] === $categoryId) {
            $category = $cat;
        }
    }
    if (!$category) {
        $errors[] = '請選擇有效的分類。';
    }

    $fileName = $photo ? $photo['file_name'] : null;

    if ($category) {
        $targetDir = IMAGES_DIR . '/' . $category['folder'];
        list($uploadedName, $uploadError) = handleImageUpload('image', $targetDir);
        if ($uploadError) {
            $errors[] = $uploadError;
        } elseif ($uploadedName) {
            $fileName = $uploadedName;
        } elseif (!$photo) {
            $errors[] = '請選擇要上傳的圖片。';
        }
    }

    if (!$errors) {
        if ($photo) {
            $stmt = $pdo->prepare('UPDATE gallery_photos SET category_id = ?, file_name = ?, sort_order = ? WHERE id = ?');
            $stmt->execute([$categoryId, $fileName, $sortOrder, $photo['id']]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO gallery_photos (category_id, file_name, sort_order) VALUES (?, ?, ?)');
            $stmt->execute([$categoryId, $fileName, $sortOrder]);
        }
        exportManifests($pdo);
        flashSet('success', $photo ? '作品已更新，並已同步匯出。' : '作品已新增，並已同步匯出。');
        redirect(adminUrl('gallery/list.php?category=' . urlencode($category['slug'])));
    }

    $defaultCategoryId = $categoryId;
}

$pageTitle = $photo ? '編輯作品' : '新增作品';
require __DIR__ . '/../includes/layout_start.php';
?>
<h1><?php echo h($pageTitle); ?></h1>
<p class="admin-subtitle">上傳的圖片會儲存到對應分類的 images/ 資料夾，儲存後自動更新 gallery-manifest.json。</p>

<?php if ($errors) { ?>
<div class="admin-flash admin-flash--error"><?php echo h(implode(' ', $errors)); ?></div>
<?php } ?>

<div class="admin-card">
    <form class="admin-form" method="post" enctype="multipart/form-data" action="<?php echo adminUrl('gallery/form.php' . ($photo ? '?id=' . (int)$photo['id'] : '')); ?>">
        <?php echo csrfField(); ?>
        <?php if ($photo) { ?><input type="hidden" name="id" value="<?php echo (int)$photo['id']; ?>"><?php } ?>

        <div>
            <label for="category_id">分類</label>
            <select id="category_id" name="category_id" required>
                <?php foreach ($categories as $cat) { ?>
                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo (int)$cat['id'] === (int)$defaultCategoryId ? 'selected' : ''; ?>>
                        <?php echo h($cat['name_zh']); ?>（<?php echo h($cat['folder']); ?>）
                    </option>
                <?php } ?>
            </select>
        </div>

        <div>
            <label for="image">圖片檔案<?php echo $photo ? '（如不更換可留空）' : ''; ?></label>
            <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp" <?php echo $photo ? '' : 'required'; ?>>
            <?php if ($photo) { ?>
            <div class="field-hint">目前檔案：<?php echo h($photo['file_name']); ?></div>
            <img class="thumb" style="margin-top:8px;width:120px;height:120px;" src="/images/<?php
                foreach ($categories as $cat) { if ((int)$cat['id'] === (int)$photo['category_id']) echo h($cat['folder']); }
            ?>/<?php echo h($photo['file_name']); ?>" alt="">
            <?php } ?>
        </div>

        <div>
            <label for="sort_order">排序（數字越小越前面）</label>
            <input type="number" id="sort_order" name="sort_order" value="<?php echo (int)$sortOrder; ?>">
        </div>

        <div class="actions">
            <button type="submit" class="btn">儲存</button>
            <a class="btn btn-secondary" href="<?php echo adminUrl('gallery/list.php'); ?>">取消</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/layout_end.php'; ?>
