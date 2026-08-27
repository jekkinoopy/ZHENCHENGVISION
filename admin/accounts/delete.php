<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(adminUrl('accounts/list.php'));
}
csrfVerify();

$id = (int)$_POST['id'];

if ($id === (int)$user['id']) {
    flashSet('error', '無法刪除自己目前登入的帳號。');
    redirect(adminUrl('accounts/list.php'));
}

$stmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ?');
$stmt->execute([$id]);
$target = $stmt->fetch();

if (!$target) {
    flashSet('error', '找不到指定的帳號。');
    redirect(adminUrl('accounts/list.php'));
}

if ($target['role'] === 'admin') {
    $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM admin_users WHERE role = 'admin'")->fetchColumn();
    if ($adminCount <= 1) {
        flashSet('error', '系統至少需保留一位管理員，無法刪除唯一的管理員帳號。');
        redirect(adminUrl('accounts/list.php'));
    }
}

$pdo->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$id]);
flashSet('success', '帳號已刪除。');
redirect(adminUrl('accounts/list.php'));
