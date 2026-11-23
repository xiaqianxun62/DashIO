<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/markdown.php';

$q = trim((string)($_GET['q'] ?? ''));
$results = [];
$total = 0;

if ($q !== '') {
    $kw = '%' . str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $q) . '%';
    $rows = q_all(
        'SELECT e.id, e.title, e.code, e.note, e.link,
                c.id AS cat_id, c.name AS cat_name,
                s.slug AS stack_slug, s.name AS stack_name, s.icon AS stack_icon, s.color AS stack_color
         FROM entries e
         JOIN categories c ON e.category_id = c.id
         JOIN stacks s ON c.stack_id = s.id
         WHERE e.title LIKE ? OR e.code LIKE ? OR e.note LIKE ?
         ORDER BY s.sort, c.sort, e.sort, e.id
         LIMIT 120',
        [$kw, $kw, $kw]
    );
    $results = $rows;
    $total = count($rows);
}

/** 截取命中片段（多字节安全） */
function snippet(string $text, string $kw, int $len = 130): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    $pos = mb_strpos($text, $kw);
    if ($pos === false) {
        return mb_substr($text, 0, $len);
    }
    $start = max(0, $pos - (int)($len / 3));
    $out = mb_substr($text, $start, $len);
    return ($start > 0 ? '…' : '') . $out . '…';
}

page_header(['title' => $q !== '' ? '搜索：' . $q : '搜索']);
?>
<section class="search-head">
  <h1><?= icon('search') ?>搜索结果<?= $q !== '' ? '：' . e($q) : '' ?></h1>
  <form class="search-big-form" action="<?= u('search.php') ?>" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="输入关键词：标题 / 代码 / 说明 全文匹配" autofocus>
    <button class="btn btn-primary" type="submit">搜索</button>
  </form>
  <?php if ($q !== ''): ?>
  <p class="search-meta">共 <b><?= $total ?></b> 条命中<?= $total >= 120 ? '（仅显示前 120 条，请用更精确的关键词）' : '' ?></p>
  <?php endif; ?>
</section>
<?= flash_render() ?>

<?php if ($q === ''): ?>
<section class="empty-tip">
  <p>输入关键词试试：foreach · flex · JOIN · 事务 · async · LINQ · 绑定 …</p>
</section>
<?php elseif ($total === 0): ?>
<section class="empty-tip">
  <h2>没有找到与「<?= e($q) ?>」相关的条目</h2>
  <p class="hint">可以换个说法再试，或 <a href="<?= u('index.php') ?>">浏览全部技术栈</a>。</p>
</section>
<?php else: ?>
<div class="search-list">
  <?php foreach ($results as $r): ?>
  <a class="search-item" href="<?= u('sheet.php') ?>?stack=<?= urlencode((string)$r['stack_slug']) ?>#cat-<?= (int)$r['cat_id'] ?>">
    <div class="search-item-head">
      <span class="search-icon"><?= stack_icon((string)$r['stack_slug'], (string)$r['stack_icon']) ?></span>
      <span class="search-title"><?= e((string)$r['title']) ?></span>
      <span class="search-loc"><?= e((string)$r['stack_name']) ?> / <?= e((string)$r['cat_name']) ?></span>
    </div>
    <?php if (trim((string)($r['code'] ?? '')) !== ''): ?>
    <pre class="code-block"><code><?= e(snippet(md_strip_marker((string)$r['code']), $q)) ?></code></pre>
    <?php endif; ?>
    <?php if (trim((string)($r['note'] ?? '')) !== ''): ?>
    <p class="entry-note"><?= e(snippet((string)$r['note'], $q, 90)) ?></p>
    <?php endif; ?>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
page_footer();
