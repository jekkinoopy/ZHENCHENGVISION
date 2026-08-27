<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (currentUser()) {
    redirect(adminUrl('index.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $password = (string)(isset($_POST['password']) ? $_POST['password'] : '');

    if ($username === '' || $password === '') {
        $error = '請輸入帳號與密碼。';
    } elseif (attemptLogin($pdo, $username, $password)) {
        redirect(adminUrl('index.php'));
    } else {
        $error = '帳號或密碼錯誤，請再試一次。';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>管理員登入 | <?php echo h(SITE_NAME); ?></title>
<link rel="stylesheet" href="<?php echo adminUrl('assets/admin.css'); ?>">
</head>
<body>
<div class="login-shell">
    <div class="login-box">
        <h1>視野成珍</h1>
        <p class="admin-subtitle">後台管理系統登入</p>
        <?php if ($error) { ?>
        <div class="admin-flash admin-flash--error"><?php echo h($error); ?></div>
        <?php } ?>
        <form class="admin-form" method="post" action="<?php echo adminUrl('login.php'); ?>">
            <?php echo csrfField(); ?>
            <div>
                <label for="username">帳號</label>
                <input type="text" id="username" name="username" autofocus required>
            </div>
            <div>
                <label for="password">密碼</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="actions">
                <button type="submit" class="btn" style="width:100%;">登入</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
