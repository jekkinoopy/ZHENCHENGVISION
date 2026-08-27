<?php

function currentUser()
{
    return isset($_SESSION['admin_user']) ? $_SESSION['admin_user'] : null;
}

function requireLogin()
{
    if (!currentUser()) {
        redirect(adminUrl('login.php'));
    }
}

function requireRole($role)
{
    requireLogin();
    $user = currentUser();
    if ($user['role'] !== $role) {
        http_response_code(403);
        die('權限不足，此功能僅限管理員（admin）操作。');
    }
}

function attemptLogin(PDO $pdo, $username, $password)
{
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin_user'] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'display_name' => $user['display_name'],
        'role' => $user['role'],
    ];
    return true;
}

function logoutUser()
{
    $_SESSION = [];
    session_destroy();
}
