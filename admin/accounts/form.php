<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireRole('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$account = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ?');
    $stmt->execute([$id]);
    $account = $stmt->fetch();
    if (!$account) {
        flashSet('error', '找不到指定的帳號。');
        redirect(adminUrl('accounts/list.php'));
    }
}

$username = $account ? $account['username'] : '';
$displayName = $account ? $account['display_name'] : '';
$role = $account ? $account['role'] : 'editor';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $username = trim($_POST['username']);
    $displayName = trim($_POST['display_name']);
    $role = $_POST['role'] === 'admin' ? 'admin' : 'editor';
    $password = (string)$_POST['password'];

    if ($username === '' || !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        $errors[] = '帳號需為 3-50 字元的英數字、底線、句點或連字號。';
    }
    if ($displayName === '') {
        $errors[] = '請輸入顯示姓名。';
    }
    if (!$account && $password === '') {
        $errors[] = '新增帳號時請設定密碼。';
    }
    if ($password !== '' && strlen($password) < 4) {
        $errors[] = '密碼長度至少需要 4 碼。';
    }

    if ($account && $account['role'] === 'admin' && $role !== 'admin') {
        $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM admin_users WHERE role = 'admin'")->fetchColumn();
        if ($adminCount <= 1) {
            $errors[] = '系統至少需保留一位管理員，無法將唯一的管理員降級。';
        }
    }

    if (!$errors) {
        $dupStmt = $pdo->prepare('SELECT id FROM admin_users WHERE username = ? AND id != ?');
        $dupStmt->execute([$username, $account ? $account['id'] : 0]);
        if ($dupStmt->fetch()) {
            $errors[] = '此帳號已被使用，請更換。';
        }
    }

    if (!$errors) {
        if ($account) {
            if ($password !== '') {
                $stmt = $pdo->prepare('UPDATE admin_users SET username = ?, display_name = ?, role = ?, password_hash = ? WHERE id = ?');
                $stmt->execute([$username, $displayName, $role, password_hash($password, PASSWORD_DEFAULT), $account['id']]);
            } else {
                $stmt = $pdo->prepare('UPDATE admin_users SET username = ?, display_name = ?, role = ? WHERE id = ?');
                $stmt->execute([$username, $displayName, $role, $account['id']]);
            }
            flashSet('success', '帳號已更新。');
        } else {
            $stmt = $pdo->prepare('INSERT INTO admin_users (username, display_name, role, password_hash) VALUES (?, ?, ?, ?)');
            $stmt->execute([$username, $displayName, $role, password_hash($password, PASSWORD_DEFAULT)]);
            flashSet('success', '帳號已新增。');
        }
        redirect(adminUrl('accounts/list.php'));
    }
}

$pageTitle = $account ? '編輯帳號' : '新增帳號';
require __DIR__ . '/../includes/layout_start.php';
?>
<h1><?php echo h($pageTitle); ?></h1>

<?php if ($errors) { ?>
<div class="admin-flash admin-flash--error"><?php echo h(implode(' ', $errors)); ?></div>
<?php } ?>

<div class="admin-card">
    <form class="admin-form" method="post" action="<?php echo adminUrl('accounts/form.php' . ($account ? '?id=' . (int)$account['id'] : '')); ?>">
        <?php echo csrfField(); ?>
        <?php if ($account) { ?><input type="hidden" name="id" value="<?php echo (int)$account['id']; ?>"><?php } ?>

        <div>
            <label for="username">帳號</label>
            <input type="text" id="username" name="username" value="<?php echo h($username); ?>" required>
        </div>

        <div>
            <label for="display_name">顯示姓名</label>
            <input type="text" id="display_name" name="display_name" value="<?php echo h($displayName); ?>" required>
        </div>

        <div>
            <label for="password">密碼<?php echo $account ? '（留空表示不變更）' : ''; ?></label>
            <input type="password" id="password" name="password">
        </div>

        <div>
            <label for="role">角色</label>
            <select id="role" name="role">
                <option value="editor" <?php echo $role === 'editor' ? 'selected' : ''; ?>>編輯者（圖庫／消息）</option>
                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>管理員（含帳號管理）</option>
            </select>
        </div>

        <div class="actions">
            <button type="submit" class="btn">儲存</button>
            <a class="btn btn-secondary" href="<?php echo adminUrl('accounts/list.php'); ?>">取消</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/layout_end.php'; ?>
