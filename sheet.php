<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/markdown.php';

$slug = trim((string)($_GET['stack'] ?? ''));
$stack = $slug ? q_row('SELECT * FROM stacks WHERE slug = ?', [$slug]) : null;

if (!$stack) {
    http_response_code(404);
    page_header(['title' => '未找到技术栈']);
    echo '<section class="empty-tip"><h2>404 · 未找到该技术栈</h2>'
        . '<p class="hint">没有找到 <code>' . e($slug) . '</code> 对应的速查清单。<a href="' . u('index.php') . '">返回首页</a></p></section>';
    page_footer();
    exit;
}

$stackId = (int)$stack['id'];
$lang = lang_for_stack((string)$stack['slug']);
$isAdmin = is_admin();

/* ---------- admin: 新增分类 ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_category' && $isAdmin) {
    csrf_check_post();
    $name = trim((string)($_POST['cat_name'] ?? ''));
    if ($name !== '' && mb_strlen($name) <= 100) {
        $next = (int)q_val('SELECT COALESCE(MAX(sort), 0) + 1 FROM categories WHERE stack_id = ?', [$stackId]);
        q('INSERT INTO categories (stack_id, name, sort) VALUES (?, ?, ?)', [$stackId, $name, $next]);
        $newId = (int)db()->lastInsertId();
        flash_set('success', '已新增分类「' . $name . '」');
    } else {
        flash_set('error', '分类名称不能为空且不超过 100 字');
    }
    redirect('sheet.php?stack=' . urlencode($slug) . '#cat-' . $newId);
}

/* ---------- 读取分类与条目 ---------- */
$cats = q_all('SELECT * FROM categories WHERE stack_id = ? ORDER BY sort, id', [$stackId]);
$byCat = [];
if ($cats) {
    $rows = q_all(
        'SELECT e.* FROM entries e
         JOIN categories c ON e.category_id = c.id
         WHERE c.stack_id = ?
         ORDER BY c.sort, c.id, e.sort, e.id',
        [$stackId]
    );
    foreach ($rows as $r) {
        $byCat[(int)$r['category_id']][] = $r;
    }
}

$entryCount = 0;
foreach ($cats as $c) {
    $entryCount += count($byCat[(int)$c['id']] ?? []);
}

/* ---------- 供前端编辑用的 JSON ---------- */
$jsEntries = [];
foreach ($cats as $c) {
    foreach (($byCat[(int)$c['id']] ?? []) as $r) {
        $jsEntries[] = [
            'id' => (int)$r['id'],
            'cat' => (int)$c['id'],
            'title' => (string)$r['title'],
            'code' => (string)($r['code'] ?? ''),
            'note' => (string)($r['note'] ?? ''),
            'link' => (string)($r['link'] ?? ''),
        ];
    }
}

page_header(['title' => (string)$stack['name'] . ' 速查']);
?>
<section class="sheet-head">
  <div class="crumbs"><a href="<?= u('index.php') ?>">首页</a> <span>/</span> <?= e((string)$stack['name']) ?></div>
  <h1 class="sheet-title"><span class="sheet-icon"><?= stack_icon((string)$stack['slug'], (string)($stack['icon'] ?? '')) ?></span><?= e((string)$stack['name']) ?> <small><?= $entryCount ?> 条速查</small></h1>
  <p class="sheet-desc"><?= e((string)$stack['description']) ?></p>
  <div class="sheet-toolbar">
    <input id="sheetFilter" type="search" placeholder="在此清单内过滤…">
    <?php if ($isAdmin): ?>
    <button class="btn btn-primary" id="addEntryTop" data-cat="<?= (int)($cats[0]['id'] ?? 0) ?>"><?= icon('plus') ?>新增条目</button>
    <?php endif; ?>
  </div>
</section>
<?= flash_render() ?>

<?php if (!empty($cats)): ?>
<nav class="cat-nav" id="catNav">
  <?php foreach ($cats as $c): ?>
  <a href="#cat-<?= (int)$c['id'] ?>"><?= e((string)$c['name']) ?> <em><?= count($byCat[(int)$c['id']] ?? []) ?></em></a>
  <?php endforeach; ?>
</nav>
<?php endif; ?>

<?php if (empty($cats)): ?>
<section class="empty-tip">
  <h2>该栈还没有分类与内容</h2>
  <p class="hint">管理员登录后可在下方添加分类与条目。</p>
</section>
<?php endif; ?>

<?php foreach ($cats as $c): $catId = (int)$c['id']; ?>
<section class="cat-block" id="cat-<?= $catId ?>">
  <div class="cat-head">
    <h2 class="cat-title"><?= e((string)$c['name']) ?> <em><?= count($byCat[$catId] ?? []) ?> 条</em></h2>
    <div class="cat-tools">
      <?php if ($isAdmin): ?>
      <button class="btn btn-ghost btn-sm js-add-entry" data-cat="<?= $catId ?>"><?= icon('plus') ?>新增条目</button>
      <button class="btn btn-ghost btn-sm js-del-cat" data-cat="<?= $catId ?>" data-name="<?= e((string)$c['name']) ?>"><?= icon('trash-2') ?>删除分类</button>
      <?php endif; ?>
    </div>
  </div>
  <?php if (empty($byCat[$catId])): ?>
  <p class="hint">暂无条目。</p>
  <?php else: ?>
  <div class="entry-grid">
    <?php foreach ($byCat[$catId] as $r): ?>
    <article class="entry">
      <div class="entry-head">
        <h3 class="entry-title"><?= e((string)$r['title']) ?></h3>
        <div class="entry-tools">
          <?php if (!empty($r['link'])): ?>
          <a class="icon-btn" href="<?= e((string)$r['link']) ?>" target="_blank" rel="noopener" title="参考链接"><?= icon('link-2') ?></a>
          <?php endif; ?>
          <?php if (trim((string)($r['code'] ?? '')) !== ''): ?>
          <button class="icon-btn copy-btn" title="复制代码" type="button"><?= icon('copy') ?></button>
          <?php endif; ?>
          <?php if ($isAdmin): ?>
          <button class="icon-btn js-edit-entry" title="编辑" type="button" data-id="<?= (int)$r['id'] ?>"><?= icon('pencil') ?></button>
          <?php endif; ?>
        </div>
      </div>
      <?php $codeRaw = (string)($r['code'] ?? ''); ?>
      <?php if (trim($codeRaw) !== ''): ?>
        <?php if (is_md_content($codeRaw)): ?>
      <div class="md-body"><?= md_render(md_strip_marker($codeRaw)) ?></div>
        <?php else: ?>
      <pre class="code-block"><code data-lang="<?= e($lang) ?>"><?= e($codeRaw) ?></code></pre>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (trim((string)($r['note'] ?? '')) !== ''): ?>
      <p class="entry-note"><?= e((string)$r['note']) ?></p>
      <?php endif; ?>
      <?php if ($isAdmin): ?>
      <div class="entry-meta">更新于 <?= e((string)$r['updated_at']) ?></div>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<?php endforeach; ?>

<?php if ($isAdmin): ?>
<section class="add-cat-box">
  <h3><?= icon('plus') ?>为 <?= e((string)$stack['name']) ?> 新增分类</h3>
  <form method="post" class="add-cat-form">
    <input type="hidden" name="action" value="add_category">
    <?= csrf_field() ?>
    <input type="text" name="cat_name" maxlength="100" placeholder="分类名称，如：进阶技巧" required>
    <button class="btn btn-primary" type="submit">添加分类</button>
  </form>
</section>
<?php endif; ?>

<script>
window.SHEET = {
  stack: <?= json_encode((string)$stack['slug']) ?>,
  lang: <?= json_encode($lang) ?>,
  isAdmin: <?= $isAdmin ? 'true' : 'false' ?>,
  entries: <?= json_encode($jsEntries, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
};
</script>
<?php
page_footer();
