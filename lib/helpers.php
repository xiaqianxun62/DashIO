<?php
declare(strict_types=1);

/* 统一启动会话（所有引用本库的页面均需要）
 * 上传超限时 PHP 会先打一条 Warning（display_errors 开着时），输出已发出去会导致
 * session_start 失败，这里加 headers_sent 判断并抑制提示，避免整页权限判断被带偏 */
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/music.php';

/** HTML 转义 */
function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** 站点相对 URL（支持部署在子目录） */
function u(string $path = ''): string
{
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . u($path));
    exit;
}

/* ---------- Flash 消息 ---------- */

function flash_set(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['t' => $type, 'm' => $msg];
}

function flash_take(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- 记录 Flash 输出 ---------- */

function flash_render(): string
{
    $out = '';
    foreach (flash_take() as $item) {
        $out .= '<div class="alert alert-' . e($item['t']) . '">' . e($item['m']) . '</div>';
    }
    return $out;
}

/* ---------- 认证 ---------- */

function current_user(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return null;
    }
    $uid = $_SESSION['uid'] ?? null;
    if (!$uid) {
        return null;
    }
    static $cached = false;
    if ($cached === false) {
        $cached = q_row('SELECT id, username, display_name, role, created_at FROM users WHERE id = ?', [$uid]);
        if (!$cached) {
            unset($_SESSION['uid']);
            return null;
        }
    }
    return $cached;
}

function is_admin(): bool
{
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

function require_admin(): void
{
    if (!is_admin()) {
        flash_set('error', '需要管理员权限，请先登录管理员账号');
        redirect('login.php');
    }
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check_post(): void
{
    $tok = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)$tok)) {
        http_response_code(403);
        exit('CSRF 校验失败');
    }
}

/* ---------- 设置 ---------- */

function setting_get(string $k, string $default = ''): string
{
    $v = q_val('SELECT v FROM settings WHERE k = ?', [$k]);
    return $v === null ? $default : (string)$v;
}

function setting_set(string $k, string $v): void
{
    if (db_driver() === 'sqlite') {
        q('INSERT INTO settings (k, v) VALUES (?, ?)
           ON CONFLICT(k) DO UPDATE SET v = excluded.v', [$k, $v]);
    } else {
        q('INSERT INTO settings (k, v) VALUES (?, ?)
           ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
    }
}

/** 站点名称 */
function site_name(): string
{
    return setting_get('site_name', 'TechQuickRef 技术速查');
}

/* ---------- 技术栈对应高亮语言 ---------- */

function lang_for_stack(string $slug): string
{
    $map = [
        'php'        => 'php',
        'html'       => 'html',
        'css'        => 'css',
        'javascript' => 'js',
        'mysql'      => 'sql',
        'java'       => 'java',
        'csharp'     => 'csharp',
        'dotnet'     => 'csharp',
        'wpf'        => 'xml',
        'winform'    => 'csharp',
    ];
    return $map[$slug] ?? 'plain';
}

/* ---------- 统计 ---------- */

function stats_counts(): array
{
    return [
        'stacks'  => (int)q_val('SELECT COUNT(*) FROM stacks'),
        'cats'    => (int)q_val('SELECT COUNT(*) FROM categories'),
        'entries' => (int)q_val('SELECT COUNT(*) FROM entries'),
        'users'   => (int)q_val('SELECT COUNT(*) FROM users'),
    ];
}

/* ---------- 页面框架 ---------- */

function page_header(array $opts = []): void
{
    $title = $opts['title'] ?? '';
    $user = current_user();
    $q = trim($_GET['q'] ?? '');
    $site = site_name();
    ?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title ? e($title) . ' · ' . e($site) : e($site) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= u('assets/favicon.svg') ?>">
<link rel="stylesheet" href="<?= u('assets/css/style.css') ?>?v=<?= (int)@filemtime(__DIR__ . '/../assets/css/style.css') ?>">
<script>try{if(localStorage.getItem('tqr-theme')==='dark'){document.documentElement.setAttribute('data-theme','dark');}}catch(e){}</script>
</head>
<body>
<header class="topbar">
  <div class="topbar-inner">
    <a class="brand" href="<?= u('index.php') ?>">
      <span class="brand-logo"><?= icon('zap', 'icon-lg') ?></span>
      <span class="brand-name"><?= e($site) ?></span>
    </a>
    <form class="topbar-search" action="<?= u('search.php') ?>" method="get" autocomplete="off">
      <input class="js-suggest-input" type="search" name="q" value="<?= e($q) ?>" placeholder="搜索知识条目，如：flex / 事务 / async…" autocomplete="off">
      <button type="submit" title="搜索"><?= icon('search') ?></button>
      <div class="suggest-box js-suggest-box" hidden></div>
    </form>
    <nav class="topbar-actions">
      <button class="icon-btn" id="themeToggle" type="button" title="切换深色 / 浅色" aria-label="切换深色 / 浅色">
        <span class="theme-moon"><?= icon('moon') ?></span>
        <span class="theme-sun" hidden><?= icon('sun') ?></span>
      </button>
      <?php if ($user): ?>
        <?php if ($user['role'] === 'admin'): ?>
          <a class="topbar-link" href="<?= u('admin.php') ?>">管理</a>
        <?php endif; ?>
        <a class="topbar-link" href="<?= u('login.php') ?>?logout=1" title="退出 <?= e($user['username']) ?>">退出(<?= e($user['username']) ?>)</a>
      <?php else: ?>
        <a class="topbar-link" href="<?= u('login.php') ?>">登录</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
<?php
}

function page_footer(string $extraJs = '', bool $withPlayer = true): void
{
    $csrf = csrf_token();
    $isAdmin = is_admin() ? 'true' : 'false';

    /* 全站迷你播放器：没有曲目就不渲染，省一次音频请求 */
    $tracks = $withPlayer ? music_tracks() : [];
    $trackJson = '[]';
    if ($tracks !== []) {
        $brief = [];
        foreach ($tracks as $t) {
            $brief[] = ['i' => (int)$t['id'], 't' => (string)$t['title'], 'a' => (string)$t['artist'], 's' => (string)$t['src']];
        }
        $trackJson = (string)json_encode($brief, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
    ?>
</main>
<?php if ($tracks !== []): ?>
<div class="player-space" aria-hidden="true"></div>
<div class="mini-player" id="tqrPlayer" data-mode="bar">
  <div class="player-main">
    <button class="icon-btn player-btn" id="plPrev" type="button" title="上一首" aria-label="上一首"><?= icon('skip-back') ?></button>
    <button class="icon-btn player-btn player-play" id="plToggle" type="button" title="播放" aria-label="播放">
      <span class="pl-icon-play"><?= icon('play') ?></span>
      <span class="pl-icon-pause" hidden><?= icon('pause') ?></span>
    </button>
    <button class="icon-btn player-btn" id="plNext" type="button" title="下一首" aria-label="下一首"><?= icon('skip-forward') ?></button>
  </div>
  <div class="player-info">
    <div class="player-title" id="plTitle">未在播放</div>
    <div class="player-artist" id="plArtist">&nbsp;</div>
  </div>
  <div class="player-seek">
    <span class="player-time" id="plCur">0:00</span>
    <input type="range" id="plSeek" min="0" max="1000" value="0" step="1" aria-label="播放进度">
    <span class="player-time" id="plDur">0:00</span>
  </div>
  <div class="player-right">
    <button class="icon-btn" id="plMute" type="button" title="静音" aria-label="静音">
      <span class="pl-icon-vol"><?= icon('volume-2') ?></span>
      <span class="pl-icon-mute" hidden><?= icon('volume-x') ?></span>
    </button>
    <input type="range" id="plVol" min="0" max="100" value="80" step="1" aria-label="音量">
    <a class="icon-btn" href="<?= u('music.php') ?>" title="歌单" aria-label="歌单"><?= icon('list-music') ?></a>
  </div>
</div>
<?php endif; ?>
<footer class="footer">
  <div>TechQuickRef · 团队技术速查清单 · PHP + MySQL 驱动</div>
  <div class="footer-links">
    <a href="<?= u('index.php') ?>">首页</a>
    <a href="<?= u('music.php') ?>">音乐</a>
    <?php if (is_admin()): ?><a href="<?= u('admin.php') ?>">管理后台</a><?php endif; ?>
  </div>
</footer>
<div class="modal-backdrop" id="modalBackdrop" hidden>
  <div class="modal">
    <div class="modal-head">
      <span id="modalTitle">编辑条目</span>
      <button class="icon-btn" id="modalClose" type="button" title="关闭"><?= icon('x') ?></button>
    </div>
    <form id="entryForm">
      <input type="hidden" name="id" id="f_id" value="">
      <input type="hidden" name="category_id" id="f_cat" value="">
      <label class="field"><span>标题 *</span><input type="text" name="title" id="f_title" required maxlength="200"></label>
      <label class="field"><span>内容（代码 或 Markdown）</span><textarea name="code" id="f_code" rows="10" spellcheck="false" placeholder="代码片段直接粘贴即可；写 Markdown 时支持 # 标题、- 列表、**加粗**、| 表格 |、```代码块```；首行写 [md] 可强制按 Markdown 渲染，写 [code] 则强制按代码渲染"></textarea></label>
      <label class="field"><span>备注说明</span><textarea name="note" id="f_note" rows="3" placeholder="一句话说明，如注意事项 / 适用场景"></textarea></label>
      <label class="field"><span>参考链接（可选）</span><input type="url" name="link" id="f_link" placeholder="https://…"></label>
      <div class="modal-foot">
        <button type="button" class="btn btn-danger js-delete" id="delBtn" hidden>删除</button>
        <span class="flex-spacer"></span>
        <button type="button" class="btn btn-ghost" id="modalCancel">取消</button>
        <button type="submit" class="btn btn-primary">保存</button>
      </div>
    </form>
  </div>
</div>
<div class="toast" id="toast" hidden></div>
<script>window.CSRF = <?= json_encode($csrf) ?>;</script>
<script src="<?= u('assets/js/highlight.js') ?>?v=<?= (int)@filemtime(__DIR__ . '/../assets/js/highlight.js') ?>"></script>
<script src="<?= u('assets/js/app.js') ?>?v=<?= (int)@filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
<?php if ($tracks !== []): ?>
<script type="application/json" id="tqrTracks"><?= $trackJson ?></script>
<script src="<?= u('assets/js/player.js') ?>?v=<?= (int)@filemtime(__DIR__ . '/../assets/js/player.js') ?>"></script>
<?php endif; ?>
<?= $extraJs ?>
</body>
</html>
<?php
}
