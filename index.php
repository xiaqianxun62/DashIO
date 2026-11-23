<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';

$stacks = q_all(
    'SELECT s.*,
        (SELECT COUNT(*) FROM categories c WHERE c.stack_id = s.id) AS cat_count,
        (SELECT COUNT(*) FROM entries e
            JOIN categories c ON e.category_id = c.id
            WHERE c.stack_id = s.id) AS entry_count
     FROM stacks s
     ORDER BY s.sort, s.id'
);

$groups = [];
foreach ($stacks as $st) {
    $groups[(string)$st['grp']][] = $st;
}
$stats = stats_counts();

page_header();
?>
<section class="hero">
  <h1 class="hero-title">技术栈速查清单</h1>
  <p class="hero-sub">为个人与团队整理的开发速查手册 · 覆盖 <?= (int)$stats['stacks'] ?> 个技术栈 / <?= (int)$stats['entries'] ?> 条速查</p>
  <form class="hero-search" action="<?= u('search.php') ?>" method="get" autocomplete="off">
    <input class="js-suggest-input" type="search" name="q" placeholder="输入关键词，如：foreach / flex / JOIN / async / LINQ …" autocomplete="off">
    <button type="submit">搜索</button>
    <div class="suggest-box js-suggest-box" hidden></div>
  </form>
  <div class="hero-stats">
    <span><?= icon('book-open') ?><?= (int)$stats['stacks'] ?> 个技术栈</span>
    <span><?= icon('folder-open') ?><?= (int)$stats['cats'] ?> 个分类</span>
    <span><?= icon('zap') ?><?= (int)$stats['entries'] ?> 条速查</span>
    <?php if (is_admin()): ?><span><?= icon('pencil') ?>管理员已登录，可直接编辑条目</span><?php endif; ?>
  </div>
</section>

<?php if ($stats['stacks'] === 0): ?>
<section class="empty-tip">
  <h2><?= icon('inbox') ?>知识库还是空的</h2>
  <p class="hint">请检查 <code>data/seed</code> 目录下的种子文件是否存在，或重新运行 <a href="<?= u('install.php') ?>">安装向导</a>。</p>
</section>
<?php endif; ?>

<?php foreach ($groups as $gname => $list): ?>
<section class="stack-group">
  <h2 class="group-title"><span class="group-bar"></span><?= e($gname) ?></h2>
  <div class="stack-grid">
    <?php foreach ($list as $st): ?>
    <a class="stack-card" href="<?= u('sheet.php') ?>?stack=<?= urlencode((string)$st['slug']) ?>">
      <div class="stack-icon"><?= stack_icon((string)$st['slug'], (string)($st['icon'] ?? '')) ?></div>
      <div class="stack-body">
        <div class="stack-name"><?= e((string)$st['name']) ?></div>
        <div class="stack-desc"><?= e((string)$st['description']) ?></div>
        <div class="stack-meta"><?= (int)$st['entry_count'] ?> 条速查 · <?= (int)$st['cat_count'] ?> 个分类</div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>

<?php
page_footer();
