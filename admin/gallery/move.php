<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(adminUrl('gallery/list.php'));
}
csrfVerify();

$id = (int)$_POST['id'];
$direction = $_POST['direction'] === 'up' ? 'up' : 'down';
$category = isset($_POST['category']) ? $_POST['category'] : '';

$stmt = $pdo->prepare('SELECT * FROM gallery_photos WHERE id = ?');
$stmt->execute([$id]);
$current = $stmt->fetch();

if ($current) {
    if ($direction === 'up') {
        $neighborStmt = $pdo->prepare('SELECT * FROM gallery_photos WHERE category_id = ? AND (sort_order < ? OR (sort_order = ? AND id < ?)) ORDER BY sort_order DESC, id DESC LIMIT 1');
        $neighborStmt->execute([$current['category_id'], $current['sort_order'], $current['sort_order'], $current['id']]);
    } else {
        $neighborStmt = $pdo->prepare('SELECT * FROM gallery_photos WHERE category_id = ? AND (sort_order > ? OR (sort_order = ? AND id > ?)) ORDER BY sort_order ASC, id ASC LIMIT 1');
        $neighborStmt->execute([$current['category_id'], $current['sort_order'], $current['sort_order'], $current['id']]);
    }
    $neighbor = $neighborStmt->fetch();

    if ($neighbor) {
        $pdo->prepare('UPDATE gallery_photos SET sort_order = ? WHERE id = ?')->execute([$neighbor['sort_order'], $current['id']]);
        $pdo->prepare('UPDATE gallery_photos SET sort_order = ? WHERE id = ?')->execute([$current['sort_order'], $neighbor['id']]);
        exportManifests($pdo);
    }
}

redirect(adminUrl('gallery/list.php?category=' . urlencode($category)));
