<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(adminUrl('gallery/list.php'));
}
csrfVerify();

$id = (int)$_POST['id'];
$stmt = $pdo->prepare('SELECT p.*, c.folder, c.slug FROM gallery_photos p JOIN gallery_categories c ON c.id = p.category_id WHERE p.id = ?');
$stmt->execute([$id]);
$photo = $stmt->fetch();

if ($photo) {
    $pdo->prepare('DELETE FROM gallery_photos WHERE id = ?')->execute([$id]);

    $filePath = IMAGES_DIR . '/' . $photo['folder'] . '/' . $photo['file_name'];
    if (is_file($filePath)) {
        @unlink($filePath);
    }

    exportManifests($pdo);
    flashSet('success', '作品已刪除，並已同步匯出。');
    redirect(adminUrl('gallery/list.php?category=' . urlencode($photo['slug'])));
}

flashSet('error', '找不到指定的作品。');
redirect(adminUrl('gallery/list.php'));
