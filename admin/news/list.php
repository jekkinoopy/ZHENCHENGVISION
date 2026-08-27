<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$news = $pdo->query('SELECT * FROM news ORDER BY COALESCE(published_at, created_at) DESC, id DESC')->fetchAll();

$pageTitle = '最新消息';
require __DIR__ . '/../includes/layout_start.php';
?>
<h1>最新消息</h1>
<p class="admin-subtitle">管理公告與最新消息，發布後會自動同步到前台的 news.json。</p>

<div class="admin-toolbar">
    <span></span>
    <a class="btn" href="<?php echo adminUrl('news/form.php'); ?>">+ 新增消息</a>
</div>

<div class="admin-card" style="padding:0;">
    <?php if (!$news) { ?>
        <p class="empty-state">目前尚無任何消息，請點選右上「新增消息」建立第一則公告。</p>
    <?php } else { ?>
    <table>
        <thead><tr><th>標題</th><th>狀態</th><th>發布時間</th><th style="width:160px;">操作</th></tr></thead>
        <tbody>
        <?php foreach ($news as $item) { ?>
            <tr>
                <td><?php echo h($item['title']); ?></td>
                <td>
                    <?php if ($item['status'] === 'published') { ?>
                        <span class="badge badge-published">已發布</span>
                    <?php } else { ?>
                        <span class="badge badge-draft">草稿</span>
                    <?php } ?>
                </td>
                <td><?php echo h($item['published_at'] ?: '—'); ?></td>
                <td>
                    <a class="btn btn-secondary btn-small" href="<?php echo adminUrl('news/form.php?id=' . (int)$item['id']); ?>">編輯</a>
                    <form class="inline-form" method="post" action="<?php echo adminUrl('news/delete.php'); ?>" onsubmit="return confirm('確定要刪除這則消息嗎？');">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-small">刪除</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <?php } ?>
</div>
<?php require __DIR__ . '/../includes/layout_end.php'; ?>
