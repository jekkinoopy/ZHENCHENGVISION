<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(adminUrl('news/list.php'));
}
csrfVerify();

$id = (int)$_POST['id'];
$stmt = $pdo->prepare('SELECT * FROM news WHERE id = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();

if ($item) {
    $pdo->prepare('DELETE FROM news WHERE id = ?')->execute([$id]);
    if ($item['cover_image']) {
        $filePath = IMAGES_DIR . '/news/' . $item['cover_image'];
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }
    exportManifests($pdo);
    flashSet('success', '消息已刪除。');
} else {
    flashSet('error', '找不到指定的消息。');
}

redirect(adminUrl('news/list.php'));
