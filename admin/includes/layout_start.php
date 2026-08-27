<?php
/**
 * 使用方式：先設定 $pageTitle，再 require 本檔案。
 * 需要在 require 之前已完成 requireLogin()。
 */
$user = currentUser();
$flash = flashGet();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo h(isset($pageTitle) ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME); ?></title>
<link rel="stylesheet" href="<?php echo adminUrl('assets/admin.css'); ?>">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand">視野成珍<span>後台管理</span></div>
        <nav class="admin-nav">
            <a href="<?php echo adminUrl('index.php'); ?>">儀表板</a>
            <a href="<?php echo adminUrl('gallery/list.php'); ?>">圖庫管理</a>
            <a href="<?php echo adminUrl('news/list.php'); ?>">最新消息</a>
            <?php if ($user && $user['role'] === 'admin') { ?>
            <a href="<?php echo adminUrl('accounts/list.php'); ?>">帳號管理</a>
            <?php } ?>
        </nav>
        <div class="admin-user-box">
            <div class="admin-user-name"><?php echo h($user['display_name']); ?></div>
            <div class="admin-user-role"><?php echo $user['role'] === 'admin' ? '管理員' : '編輯者'; ?></div>
            <form action="<?php echo adminUrl('logout.php'); ?>" method="post">
                <?php echo csrfField(); ?>
                <button type="submit" class="admin-link-btn">登出</button>
            </form>
        </div>
    </aside>
    <main class="admin-main">
        <?php if ($flash) { ?>
        <div class="admin-flash admin-flash--<?php echo h($flash['type']); ?>"><?php echo h($flash['message']); ?></div>
        <?php } ?>
