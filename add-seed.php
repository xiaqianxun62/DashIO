<?php
/**
 * 增量导入种子（追加模式）：
 * 只新增数据库里还不存在的技术栈，不改 config.php、不覆盖已有数据、不重置管理员账号。
 * 用法：以管理员登录后访问 /add-seed.php，点一下按钮即可。
 * 导入完成后建议删除本文件。
 */
declare(strict_types=1);

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/seed_loader.php';

if (!is_admin()) {
    flash_set('error', '需要管理员权限，请先登录管理员账号');
    redirect('login.php');
}

$result = null;
$error = '';
$seeds = seed_load_all();
$pending = [];

foreach ($seeds as $s) {
    $slug = (string)($s['slug'] ?? '');
    if ($slug === '') {
        continue;
    }
    $exists = (bool)q_val('SELECT id FROM stacks WHERE slug = ?', [$slug]);
    $pending[] = [
        'slug' => $slug,
        'name' => (string)($s['name'] ?? $slug),
        'count' => array_sum(array_map(function ($c) {
            return count($c['entries'] ?? []);
        }, $s['categories'] ?? [])),
        'exists' => $exists,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check_post();
        $result = seed_import('append');
    } catch (Throwable $ex) {
        $error = $ex->getMessage();
    }
}

page_header(['title' => '导入种子数据']);
?>
<section class="hero" style="padding:10px 0 4px">
  <p class="hint" style="margin-bottom:6px"><?= icon('arrow-left') ?><a href="<?= u('admin.php') ?>">返回管理后台</a> <span class="crumb-sep">/</span> <a href="<?= u('index.php') ?>">回到首页</a></p>
  <h1 class="hero-title">导入种子数据</h1>
  <p class="hero-sub">只补数据库里还没有的东西：新栈整栈插入，已有栈只补齐缺失的分类与条目，不覆盖任何现有内容。</p>
</section>

<?php if ($error !== ''): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($result !== null): ?>
  <div class="alert alert-success">
    导入完成：新增 <?= (int)$result['stacks'] ?> 个技术栈 /
    <?= (int)$result['cats'] ?> 个分类 /
    <?= (int)$result['entries'] ?> 条速查；已存在的技术栈 <?= (int)$result['skipped'] ?> 个（按分类名 + 标题去重，跳过重复条目 <?= (int)($result['entries_skipped'] ?? 0) ?> 条）。
  </div>
  <?php if ((int)$result['cats'] + (int)$result['entries'] + (int)$result['stacks'] > 0): ?>
  <div style="display:flex;flex-wrap:wrap;gap:10px;margin:4px 0 18px">
    <a class="btn btn-primary btn-sm" href="<?= u('index.php') ?>">去首页看清单</a>
    <a class="btn btn-ghost btn-sm" href="<?= u('admin.php') ?>">回后台</a>
  </div>
  <?php endif; ?>
<?php endif; ?>

<section class="stack-group">
  <h2 class="group-title"><span class="group-bar"></span>种子文件清单</h2>
  <table class="chk" style="width:100%;border-collapse:collapse">
    <?php foreach ($pending as $p): ?>
      <tr>
        <td style="padding:8px 6px;border-bottom:1px solid var(--line)"><?= e($p['name']) ?></td>
        <td style="padding:8px 6px;border-bottom:1px solid var(--line);color:var(--fg-soft)"><?= e($p['slug']) ?></td>
        <td style="padding:8px 6px;border-bottom:1px solid var(--line);color:var(--fg-soft)"><?= (int)$p['count'] ?> 条</td>
        <td style="padding:8px 6px;border-bottom:1px solid var(--line);text-align:right">
          <?php if ($p['exists']): ?>
            <span style="color:#b7791f">已存在 · 合并补齐</span>
          <?php else: ?>
            <span style="color:#1e8e4e">待导入</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <form method="post" style="margin-top:18px">
    <?= csrf_field() ?>
    <button class="btn btn-primary" type="submit">开始增量导入</button>
  </form>

  <p class="hint" style="margin-top:14px;color:var(--fg-soft);font-size:13px">
    导入完成后请删除 <code>add-seed.php</code>。若想让某个技术栈整体重建，请先删掉该技术栈再导入。
  </p>
</section>
<?php
page_footer();
