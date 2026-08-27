<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireRole('admin');

$accounts = $pdo->query('SELECT * FROM admin_users ORDER BY id ASC')->fetchAll();

$pageTitle = '帳號管理';
require __DIR__ . '/../includes/layout_start.php';
?>
<h1>帳號管理</h1>
<p class="admin-subtitle">管理可登入後台的管理者／編輯者帳號。</p>

<div class="admin-toolbar">
    <span></span>
    <a class="btn" href="<?php echo adminUrl('accounts/form.php'); ?>">+ 新增帳號</a>
</div>

<div class="admin-card" style="padding:0;">
    <table>
        <thead><tr><th>帳號</th><th>姓名</th><th>角色</th><th>建立時間</th><th style="width:160px;">操作</th></tr></thead>
        <tbody>
        <?php foreach ($accounts as $acc) { ?>
            <tr>
                <td><?php echo h($acc['username']); ?></td>
                <td><?php echo h($acc['display_name']); ?></td>
                <td>
                    <?php if ($acc['role'] === 'admin') { ?>
                        <span class="badge badge-admin">管理員</span>
                    <?php } else { ?>
                        <span class="badge badge-editor">編輯者</span>
                    <?php } ?>
                </td>
                <td><?php echo h($acc['created_at']); ?></td>
                <td>
                    <a class="btn btn-secondary btn-small" href="<?php echo adminUrl('accounts/form.php?id=' . (int)$acc['id']); ?>">編輯</a>
                    <?php if ((int)$acc['id'] !== (int)$user['id']) { ?>
                    <form class="inline-form" method="post" action="<?php echo adminUrl('accounts/delete.php'); ?>" onsubmit="return confirm('確定要刪除這個帳號嗎？');">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="id" value="<?php echo (int)$acc['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-small">刪除</button>
                    </form>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/layout_end.php'; ?>
