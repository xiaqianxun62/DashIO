<?php
/**
 * 部署自检页：上传到站点根目录后访问 /php-check.php
 * 用途：确认 1) PHP 是否被解析 2) 版本/扩展 3) config.php 是否正确 4) 数据库能否连上
 * 排查完成后请删除本文件。
 */
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$root = __DIR__;
$rows = [];

$rows[] = ['PHP 是否被解析', '是（能看到本页说明 .php 已正常执行）', 'ok'];
$rows[] = ['PHP 版本', PHP_VERSION . ' / ' . PHP_SAPI, version_compare(PHP_VERSION, '7.4.0', '>=') ? 'ok' : 'bad'];
$rows[] = ['PDO', extension_loaded('PDO') ? '已启用' : '未启用', extension_loaded('PDO') ? 'ok' : 'bad'];
$rows[] = ['pdo_mysql', extension_loaded('pdo_mysql') ? '已启用' : '未启用', extension_loaded('pdo_mysql') ? 'ok' : 'bad'];
$rows[] = ['pdo_sqlite', extension_loaded('pdo_sqlite') ? '已启用' : '未启用', extension_loaded('pdo_sqlite') ? 'ok' : 'bad'];
$rows[] = ['mbstring', extension_loaded('mbstring') ? '已启用' : '未启用', extension_loaded('mbstring') ? 'ok' : 'bad'];
$rows[] = ['站点根目录', $root, 'ok'];
$rows[] = ['根目录可写（config.php 需要）', is_writable($root) ? '可写' : '不可写', is_writable($root) ? 'ok' : 'bad'];
$rows[] = ['data 目录可写（SQLite 需要）', is_writable($root . '/data') ? '可写' : '不可写（SQLite 不可用，请用 MySQL）', is_writable($root . '/data') ? 'ok' : 'warn'];

$confFile = $root . '/config.php';
$conf = null;
if (!is_file($confFile)) {
    $rows[] = ['config.php', '不存在，会跳转 install.php', 'warn'];
} else {
    $conf = @include $confFile;
    if (!is_array($conf)) {
        $rows[] = ['config.php', '内容不是数组，可能已损坏', 'bad'];
        $conf = null;
    } else {
        $driver = ($conf['driver'] ?? 'mysql') === 'sqlite' ? 'sqlite' : 'mysql';
        if ($driver === 'sqlite') {
            $rows[] = ['数据库配置', 'driver=sqlite', 'ok'];
        } else {
            $rows[] = ['数据库配置', sprintf(
                'host=%s port=%s dbname=%s user=%s',
                $conf['host'] ?? '?',
                $conf['port'] ?? '?',
                $conf['dbname'] ?? '?',
                $conf['user'] ?? '?'
            ), 'ok'];
        }
        // 实际连接测试
        try {
            if ($driver === 'sqlite') {
                $path = $conf['sqlite_path'] ?? ($root . '/data/quickref.sqlite');
                new PDO('sqlite:' . $path);
                $rows[] = ['数据库连接', 'SQLite 连接成功', 'ok'];
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $conf['host'] ?? '127.0.0.1',
                    $conf['port'] ?? '3306',
                    $conf['dbname'] ?? ''
                );
                $pdo = new PDO($dsn, $conf['user'] ?? '', $conf['pass'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
                $rows[] = ['数据库连接', '连接成功，现有表：' . (count($tables) ? implode(', ', $tables) : '（空，需要执行安装）'), 'ok'];
            }
        } catch (Throwable $ex) {
            $rows[] = ['数据库连接', '失败：' . $ex->getMessage(), 'bad'];
        }
    }
}

function badge(string $s): string
{
    $map = [
        'ok' => ['circle-check', '#067647', '#ecfdf3'],
        'warn' => ['triangle-alert', '#b54708', '#fffaeb'],
        'bad' => ['circle-x', '#b42318', '#fef3f2'],
    ];
    [$name, $fg, $bg] = $map[$s] ?? $map['warn'];
    $paths = [
        'circle-check' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'circle-x' => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>',
        'triangle-alert' => '<path d="M12 3 2.6 19.9a1 1 0 0 0 .9 1.6h17a1 1 0 0 0 .9-1.6Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
    ];
    $svg = '<svg class="badge-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="' . $fg
        . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths[$name] . '</svg>';
    return '<span class="badge" style="color:' . $fg . ';background:' . $bg . '">' . $svg . '</span>';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>部署自检 · TechQuickRef</title>
<style>
  :root { --bg:#ffffff; --bg-sub:#f7f8fa; --fg:#171a20; --soft:#5c6470; --line:#e5e7ec; --radius:8px; }
  * { box-sizing:border-box; }
  body { margin:0; font-family:"Segoe UI","Microsoft YaHei",-apple-system,sans-serif; background:var(--bg-sub); color:var(--fg); font-size:14px; line-height:1.6; }
  .wrap { max-width:840px; margin:36px auto; padding:0 20px; }
  .card { background:var(--bg); border:1px solid var(--line); border-radius:var(--radius); padding:24px; }
  h1 { display:flex; align-items:center; gap:8px; font-size:20px; margin:0 0 4px; }
  h1 svg { flex:none; }
  .sub { color:var(--soft); font-size:13px; margin:0 0 18px; }
  table { width:100%; border-collapse:collapse; }
  td { padding:9px 8px; border-bottom:1px solid var(--line); font-size:14px; vertical-align:top; }
  td.k { width:220px; color:var(--soft); }
  .badge { display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; border-radius:6px; margin-right:8px; vertical-align:middle; }
  .badge-icon { display:block; }
  .foot { margin-top:14px; padding-top:12px; border-top:1px solid var(--line); color:var(--soft); font-size:13px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#2563eb" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8Z"/></svg>
      部署自检
    </h1>
    <p class="sub">用于判断 index.php 打不开到底是「PHP 没解析」还是「代码执行报错」</p>
    <table>
      <?php foreach ($rows as [$k, $v, $st]): ?>
      <tr><td class="k"><?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= badge($st) ?> <?= htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') ?></td></tr>
      <?php endforeach; ?>
    </table>
    <div class="foot">
      自检完成后请删除 php-check.php（本文件会暴露数据库主机名等信息）。
    </div>
  </div>
</div>
</body>
</html>
