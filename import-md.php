<?php
/**
 * 导入 Markdown（仅管理员）
 *
 * 两种导入方式：
 *   1) 文件：扫描 data/md/*.md
 *   2) 粘贴：把 Markdown 文本直接贴进来，不用上传文件
 *
 * 文档约定：
 *   # 技术栈名称            ← 可省略，省略时用文件名 / 「导入笔记」
 *   ## 分类名称
 *   ### 条目标题
 *   正文（Markdown 原样保存，页面按 Markdown 渲染）
 *
 * 已有条目按「分类 + 标题」判重，重名跳过，所以可以反复导入做增量补充。
 * 导入完成后建议删除本文件。
 */
declare(strict_types=1);

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/markdown.php';
require_once __DIR__ . '/lib/importer.php';

if (!is_admin()) {
    flash_set('error', '需要管理员权限，请先登录管理员账号');
    redirect('login.php');
}

$dir = __DIR__ . '/data/md';
$files = glob($dir . '/*.md') ?: [];
sort($files);

$error = '';
$fileReport = null;
$pasteResult = null;
$pastePreview = null;
$old = ['md_text' => '', 'md_name' => '', 'md_slug' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $old = [
        'md_text' => (string)($_POST['md_text'] ?? ''),
        'md_name' => (string)($_POST['md_name'] ?? ''),
        'md_slug' => (string)($_POST['md_slug'] ?? ''),
    ];
    try {
        csrf_check_post();

        if ($action === 'import') {
            $fileReport = [];
            foreach ($files as $f) {
                $slug = md_slug_from((string)pathinfo($f, PATHINFO_FILENAME));
                if ($slug === '') {
                    continue;
                }
                $doc = md_parse_doc((string)file_get_contents($f));
                $stackName = $doc['name'] !== '' ? $doc['name'] : (string)pathinfo($f, PATHINFO_FILENAME);
                $r = md_import_doc($doc, $slug, $stackName);
                $fileReport[] = [
                    'file' => basename($f),
                    'stack' => $stackName,
                    'slug' => $slug,
                    'added' => $r['added'],
                    'skipped' => $r['skipped'],
                    'new' => $r['stack_new'],
                ];
            }
        } elseif ($action === 'paste' || $action === 'preview_paste') {
            $raw = trim((string)($_POST['md_text'] ?? ''));
            if ($raw === '') {
                throw new RuntimeException('粘贴内容为空');
            }

            $doc = md_parse_doc($raw);
            $name = trim((string)($_POST['md_name'] ?? ''));
            if ($name === '' && $doc['name'] !== '') {
                $name = $doc['name'];
            }
            if ($name === '') {
                $name = '导入笔记 ' . date('Y-m-d');
            }
            $slug = md_slug_from($name, trim((string)($_POST['md_slug'] ?? '')));

            $count = 0;
            $sample = [];
            foreach ($doc['cats'] as $c) {
                foreach ($c['entries'] as $e) {
                    if (trim((string)$e['title']) === '' || trim((string)$e['body']) === '') {
                        continue;
                    }
                    $count++;
                    if (count($sample) < 6) {
                        $sample[] = $c['name'] . ' / ' . $e['title'];
                    }
                }
            }

            if ($action === 'preview_paste') {
                $pastePreview = [
                    'name' => $name,
                    'slug' => $slug,
                    'cats' => count($doc['cats']),
                    'entries' => $count,
                    'sample' => $sample,
                    'exists' => (bool)q_val('SELECT id FROM stacks WHERE slug = ?', [$slug]),
                ];
            } else {
                if ($count === 0) {
                    throw new RuntimeException('没解析出任何条目，检查一下是不是有 ## 分类 和 ### 条目标题');
                }
                $r = md_import_doc($doc, $slug, $name);
                $pasteResult = [
                    'name' => $name,
                    'slug' => $slug,
                    'cats' => count($doc['cats']),
                    'parsed' => $count,
                    'added' => $r['added'],
                    'skipped' => $r['skipped'],
                    'cats_added' => $r['cats'],
                    'stack_new' => $r['stack_new'],
                ];
                $old = ['md_text' => '', 'md_name' => '', 'md_slug' => ''];
            }
        }
    } catch (Throwable $ex) {
        $error = $ex->getMessage();
    }
}

/* 文件预览：不写库，只统计 */
$preview = [];
foreach ($files as $f) {
    $doc = md_parse_doc((string)file_get_contents($f));
    $count = 0;
    foreach ($doc['cats'] as $c) {
        foreach ($c['entries'] as $e) {
            if (trim((string)$e['title']) !== '' && trim((string)$e['body']) !== '') {
                $count++;
            }
        }
    }
    $preview[] = [
        'file' => basename($f),
        'name' => $doc['name'] !== '' ? $doc['name'] : pathinfo($f, PATHINFO_FILENAME),
        'cats' => count($doc['cats']),
        'entries' => $count,
    ];
}

page_header(['title' => '导入 Markdown']);
?>
<section class="hero" style="padding:10px 0 4px">
  <p class="hint" style="margin-bottom:6px"><?= icon('arrow-left') ?><a href="<?= u('admin.php') ?>">返回管理后台</a> <span class="crumb-sep">/</span> <a href="<?= u('index.php') ?>">回到首页</a></p>
  <h1 class="hero-title">导入 Markdown</h1>
  <p class="hero-sub">支持粘贴 Markdown 文本，或导入 <code>data/md/</code> 下的 .md 文件；同名条目跳过，可反复执行。</p>
</section>

<?php if ($error !== ''): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if (is_array($fileReport)): ?>
  <div class="alert alert-success">
    文件导入完成：
    <?php foreach ($fileReport as $r): ?>
      <div>· <?= e($r['file']) ?> → <a href="<?= u('sheet.php?stack=' . urlencode($r['slug'])) ?>"><?= e($r['stack']) ?></a>（<?= e($r['slug']) ?>）：新增 <?= (int)$r['added'] ?> 条，跳过 <?= (int)$r['skipped'] ?> 条<?= $r['new'] ? '（新建技术栈）' : '' ?></div>
    <?php endforeach; ?>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:10px;margin:4px 0 18px">
    <a class="btn btn-ghost btn-sm" href="<?= u('index.php') ?>">回首页</a>
    <a class="btn btn-ghost btn-sm" href="<?= u('admin.php') ?>">回后台</a>
  </div>
<?php endif; ?>

<?php if (is_array($pasteResult)): ?>
  <div class="alert alert-success">
    粘贴导入完成：<?= e($pasteResult['name']) ?>（<?= e($pasteResult['slug']) ?>）
    —— 解析 <?= (int)$pasteResult['cats'] ?> 个分类 / <?= (int)$pasteResult['parsed'] ?> 条，
    实际新增 <?= (int)$pasteResult['added'] ?> 条，跳过重复 <?= (int)$pasteResult['skipped'] ?> 条<?= $pasteResult['stack_new'] ? '（新建技术栈）' : '' ?>。
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:10px;margin:4px 0 18px">
    <a class="btn btn-primary btn-sm" href="<?= u('sheet.php?stack=' . urlencode($pasteResult['slug'])) ?>">打开《<?= e($pasteResult['name']) ?>》</a>
    <a class="btn btn-ghost btn-sm" href="<?= u('index.php') ?>">回首页</a>
    <a class="btn btn-ghost btn-sm" href="<?= u('admin.php') ?>">回后台</a>
  </div>
<?php endif; ?>

<?php if (is_array($pastePreview)): ?>
  <div class="alert alert-info">
    解析预览：<?= e($pastePreview['name']) ?>（<?= e($pastePreview['slug']) ?>）
    —— <?= (int)$pastePreview['cats'] ?> 个分类 / <?= (int)$pastePreview['entries'] ?> 条<?= $pastePreview['exists'] ? '；该 slug 已存在，会合并进已有技术栈' : '' ?>
    <?php if ($pastePreview['sample'] !== []): ?>
      <div style="margin-top:6px;font-size:13px;color:var(--fg-soft)"><?= e(implode('　·　', $pastePreview['sample'])) ?><?= $pastePreview['entries'] > count($pastePreview['sample']) ? ' ……' : '' ?></div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<section class="stack-group">
  <h2 class="group-title"><span class="group-bar"></span>粘贴导入（推荐）</h2>

  <form method="post">
    <?= csrf_field() ?>
    <label class="field"><span>Markdown 文本</span>
      <textarea name="md_text" rows="14" spellcheck="false" placeholder="# 技术栈名称&#10;&#10;## 分类名称&#10;&#10;### 条目标题&#10;正文，支持 - 列表、**加粗**、| 表格 |、```代码块```"><?= e($old['md_text']) ?></textarea>
    </label>
    <div class="grid2">
      <label class="field"><span>技术栈名称（可选，默认取 # 标题）</span>
        <input type="text" name="md_name" maxlength="100" value="<?= e($old['md_name']) ?>" placeholder="例如：TCP 与 UDP">
      </label>
      <label class="field"><span>slug（可选，英文，留空自动生成）</span>
        <input type="text" name="md_slug" maxlength="50" value="<?= e($old['md_slug']) ?>" placeholder="例如：tcp-udp">
      </label>
    </div>
    <div style="display:flex;gap:10px;margin-top:14px">
      <button class="btn btn-primary" type="submit" name="action" value="paste">直接导入</button>
      <button class="btn" type="submit" name="action" value="preview_paste">只看解析结果</button>
    </div>
  </form>
</section>

<section class="stack-group">
  <h2 class="group-title"><span class="group-bar"></span>文件导入</h2>

  <?php if ($preview === []): ?>
    <p class="hint">目录 <code>data/md/</code> 里还没有 .md 文件。用上面的粘贴导入就不用管文件了。</p>
  <?php else: ?>
    <table style="width:100%;border-collapse:collapse">
      <?php foreach ($preview as $p): ?>
      <tr>
        <td style="padding:8px 6px;border-bottom:1px solid var(--line)"><?= e($p['file']) ?></td>
        <td style="padding:8px 6px;border-bottom:1px solid var(--line);color:var(--fg-soft)"><?= e($p['name']) ?></td>
        <td style="padding:8px 6px;border-bottom:1px solid var(--line);color:var(--fg-soft)"><?= (int)$p['cats'] ?> 个分类</td>
        <td style="padding:8px 6px;border-bottom:1px solid var(--line);color:var(--fg-soft)"><?= (int)$p['entries'] ?> 条</td>
      </tr>
      <?php endforeach; ?>
    </table>

    <form method="post" style="margin-top:18px">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="import">
      <button class="btn btn-primary" type="submit">导入这些文件</button>
    </form>
  <?php endif; ?>

  <div class="hint" style="margin-top:16px">
    格式约定：<code># 技术栈名称</code>（可省略）→ <code>## 分类名称</code> → <code>### 条目标题</code>，正文照原样保存并按 Markdown 渲染。<br>
    代码围栏 ``` 内部的 <code>###</code> 不会被当成标题。
  </div>
</section>
<?php
page_footer();
