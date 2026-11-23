<?php

declare(strict_types=1);

session_start();

error_reporting(E_ALL);

ini_set('display_errors', '1');



$ROOT = __DIR__;

$configFile = $ROOT . '/config.php';



function h($s): string

{

    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

}

/** 出错重试时保留用户上次填写的值 */
function post_val(string $k, string $default = ''): string

{

    $v = $_POST[$k] ?? null;

    if ($v === null || $v === '') {

        return h($default);

    }

    return h((string)$v);

}



function install_base(): string

{

    $script = $_SERVER['SCRIPT_NAME'] ?? '/install.php';

    return rtrim(str_replace('\\', '/', dirname($script)), '/');

}



function already_installed(string $configFile): bool

{

    if (!is_file($configFile)) {

        return false;

    }

    $conf = require $configFile;

    if (!is_array($conf)) {

        return false;

    }

    try {

        if (($conf['driver'] ?? 'mysql') === 'sqlite') {

            $path = $conf['sqlite_path'] ?? (dirname($configFile) . '/data/quickref.sqlite');

            $pdo = new PDO('sqlite:' . $path);

        } else {

            $dsn = sprintf(

                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',

                $conf['host'] ?? '127.0.0.1',

                $conf['port'] ?? 3306,

                $conf['dbname'] ?? ''

            );

            $pdo = new PDO($dsn, $conf['user'] ?? 'root', $conf['pass'] ?? '',

                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        }

        return true;

    } catch (Exception $ex) {

        return false;

    }

}



$installed = already_installed($configFile);



/* ---------- Environment check ---------- */

$checks = [

    'PHP 版本 ≥ 7.4' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'ok' : 'bad',

    'PDO 扩展' => extension_loaded('PDO') ? 'ok' : 'bad',

    'pdo_mysql 扩展（MySQL/MariaDB 必需）' => extension_loaded('pdo_mysql') ? 'ok' : 'bad',

    'pdo_sqlite 扩展（SQLite 试用需要）' => extension_loaded('pdo_sqlite') ? 'ok' : 'bad',

    '项目目录可写（需生成 config.php）' => is_writable($ROOT) ? 'ok' : 'bad',

];



$error = '';

$done = [];

$installedNow = false;






/* ---------- 写入 config.php ---------- */

function write_config(array $conf, string $configFile): void

{

    $tpl = "<?php\n// TechQuickRef 配置（安装器生成）\nreturn [\n";

    foreach ($conf as $k => $v) {

        $tpl .= sprintf("    '%s' => %s,\n", $k, var_export((string)$v, true));

    }

    $tpl .= "];\n";

    if (@file_put_contents($configFile, $tpl) === false) {

        throw new RuntimeException('无法写入 config.php，请检查目录权限');

    }

}



/* ---------- 安装处理 ---------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'install') {

    $driver = ($_POST['driver'] ?? 'mysql') === 'sqlite' ? 'sqlite' : 'mysql';

    $siteName = trim((string)($_POST['site_name'] ?? '')) ?: 'TechQuickRef 技术速查';

    $adminUser = trim((string)($_POST['admin_user'] ?? '')) ?: 'admin';

    $adminPass = (string)($_POST['admin_pass'] ?? '');

    $adminPass2 = (string)($_POST['admin_pass2'] ?? '');

    $seedMode = ($_POST['seed_mode'] ?? 'fresh') === 'append' ? 'append' : 'fresh';



    try {

        if (!preg_match('/^[a-zA-Z0-9_]{2,50}$/', $adminUser)) {

            throw new RuntimeException('管理员用户名仅限字母数字下划线（2-50 位）');

        }

        if (strlen($adminPass) < 6) {

            throw new RuntimeException('管理员密码至少 6 位');

        }

        if ($adminPass !== $adminPass2) {

            throw new RuntimeException('两次输入的管理员密码不一致');

        }



        if ($driver === 'sqlite') {

            $path = $ROOT . '/data/quickref.sqlite';

            if (!is_dir(dirname($path))) {

                @mkdir(dirname($path), 0775, true);

            }

            $pdo = new PDO('sqlite:' . $path);

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $conf = ['driver' => 'sqlite', 'sqlite_path' => $path];

        } else {

            $host = trim((string)($_POST['db_host'] ?? 'sql310.infinityfree.com')) ?: 'sql310.infinityfree.com';

            $port = (int)($_POST['db_port'] ?? 3306) ?: 3306;

            $dbname = trim((string)($_POST['db_name'] ?? 'if0_43421353_techquickref')) ?: 'if0_43421353_techquickref';

            $user = trim((string)($_POST['db_user'] ?? 'if0_43421353'));

            $pass = (string)($_POST['db_pass'] ?? 'Xia520265147');

            if (!preg_match('/^[A-Za-z0-9_]+$/', $dbname)) {

                throw new RuntimeException('数据库名仅限字母数字下划线');

            }

            if ($dbname === 'mysql' || $dbname === 'information_schema') {

                throw new RuntimeException('不能使用系统保留数据库名');

            }

            $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass,

                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            // 共享虚拟主机（如 InfinityFree）一般没有建库权限，库需在控制面板里先创建

            try {

                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            } catch (PDOException $exCreate) {

                // 无权限建库时忽略，直接使用控制面板中已存在的库

            }

            $pdo->exec("USE `{$dbname}`");

            $conf = [

                'driver' => 'mysql',

                'host' => $host,

                'port' => $port,

                'dbname' => $dbname,

                'user' => $user,

                'pass' => $pass,

            ];

        }



        // 先写配置，后续 db_schema_sql / helpers 依赖它

        write_config($conf, $configFile);

        require_once $ROOT . '/lib/db.php';

        require_once $ROOT . '/lib/helpers.php';

        require_once $ROOT . '/lib/seed_loader.php';



        // 建表（统一改用 db() 单例连接，与种子导入、后续操作保持同一连接）
        $pdo = db();
        foreach (db_schema_sql() as $sql) {
            $pdo->exec($sql);
        }



        // 导入种子
        $stats = seed_import($seedMode);

        $done[] = '导入种子：' . $stats['stacks'] . ' 个技术栈 / ' . $stats['cats'] . ' 个分类 / ' . $stats['entries'] . ' 条速查';



        // 管理员账号

        q('REPLACE INTO users (username, password_hash, display_name, role) VALUES (?, ?, ?, "admin")',

            [$adminUser, password_hash($adminPass, PASSWORD_DEFAULT), '管理员']);

        $done[] = '创建管理员账号：' . $adminUser;



        // 站点设置

        setting_set('site_name', $siteName);

        setting_set('allow_register', '1');

        setting_set('installed_at', date('Y-m-d H:i:s'));

        $done[] = '写入站点设置';



        $installedNow = true;

        $error = '';

    } catch (Exception $ex) {

        $error = $ex->getMessage();

        // 安装失败时移除半成品 config.php，保证可重试

        if (!$installedNow && is_file($configFile)) {

            @unlink($configFile);

        }

    }

}



/* ---------- 危险重置（需输入 RESET 确认） ---------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {

    if (trim((string)($_POST['confirm'] ?? '')) === 'RESET' && $installed) {

        @unlink($configFile);

        $installed = false;

        $done[] = '已移除 config.php，重新进入安装流程（数据库数据保留，重装时选择「全新导入」即可覆盖）';

    } else {

        $error = '重置需精确输入 RESET 并在已安装状态下进行';

    }

}






/* ---------- 页面渲染 ---------- */

$B = install_base();

?><!DOCTYPE html>

<html lang="zh-CN">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>安装向导 · TechQuickRef</title>

<style>

  :root { --pri:#2563eb; --pri-hover:#1d4ed8; --pri-soft:#eff4ff; --bg:#f7f8fa; --card:#fff; --fg:#171a20; --soft:#5c6470; --line:#e5e7ec; --err:#b42318; }

  * { box-sizing:border-box; }

  body { margin:0; font-family:"Segoe UI","Microsoft YaHei",sans-serif; background:var(--bg); color:var(--fg); }

  .wrap { max-width:760px; margin:40px auto; padding:0 16px; }

  .card { background:var(--card); border:1px solid var(--line); border-radius:8px; padding:28px; margin-bottom:16px; }

  h1 { font-size:20px; } h2 { font-size:16px; margin:0 0 12px; }

  .logo { width:34px; height:34px; margin-bottom:10px; }

  table.chk { width:100%; border-collapse:collapse; margin:8px 0; }

  table.chk td { padding:7px 6px; border-bottom:1px solid var(--line); font-size:14px; }

  .ok { color:#1e8e4e; font-weight:600; } .bad { color:var(--err); font-weight:600; }

  label.f { display:block; margin:12px 0 4px; font-size:13px; color:var(--soft); font-weight:600; }

  input[type=text], input[type=password], input[type=search] { width:100%; padding:9px 12px; border:1px solid var(--line); border-radius:6px; font-size:14px; background:#fff; }

  input:focus { outline:none; border-color:var(--pri); box-shadow:0 0 0 3px var(--pri-soft, #eff4ff); }

  .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

  .radios { display:flex; gap:18px; margin:10px 0; flex-wrap:wrap; }

  .radios label { display:flex; gap:6px; align-items:center; font-size:14px; }

  button.primary { background:var(--pri); color:#fff; border:none; padding:10px 24px; border-radius:8px; font-size:14px; cursor:pointer; margin-top:18px; }

  button.primary:hover { background:var(--pri-hover); }

  .msg { padding:12px 14px; border-radius:8px; margin:12px 0; font-size:14px; }

  .msg.err { background:#fdecea; color:var(--err); border:1px solid #f5c6c0; }

  .msg.ok { background:#e9f9ef; color:#1e8e4e; border:1px solid #bfe8cd; }

  .hint { font-size:13px; color:var(--soft); line-height:1.7; background:#f2f4f7; padding:10px 12px; border-radius:8px; }

  a { color:var(--pri); text-decoration:none; }

  .divider { height:1px; background:var(--line); margin:22px 0; }

  code { background:#f2f4f7; padding:1px 6px; border-radius:4px; font-size:13px; }

  #sqliteFields { display:none; }

</style>

</head>

<body>

<div class="wrap">

  <div class="card">

    <div class="logo"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="#2563eb" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8Z"/></svg></div>

    <h1>TechQuickRef 安装向导</h1>

    <p style="color:#5a6478;font-size:14px;">PHP + MySQL/MariaDB 驱动的团队技术速查清单（仿 quickref.cn）</p>

  </div>



  <?php if ($installedNow): ?>

  <div class="card">

    <h2>🎉 安装完成</h2>

    <?php foreach ($done as $d): ?><div class="msg ok"><?= h($d) ?></div><?php endforeach; ?>

    <p style="font-size:14px;">现在可以进入系统。默认种子包含 PHP / HTML / CSS / JavaScript / MySQL / Java / C# / .NET / WPF / WinForm 十个技术栈的速查清单，登录管理员后可自由增删改。</p>

    <p>

      <a href="<?= $B ?>/index.php"><button class="primary" type="button">进入首页</button></a>

      &nbsp;

      <a href="<?= $B ?>/login.php"><button class="primary" style="background:#8a93a8" type="button">先去登录</button></a>

    </p>

    <div class="divider"></div>

    <div class="hint">

      部署小贴士：生产环境建议将站点根目录指向本目录并用 Nginx/Apache + PHP-FPM 运行；

      开发环境可直接使用 <code>php -S 0.0.0.0:8000</code> 在本目录启动。

      配置文件 <code>config.php</code> 含数据库密码，请注意保管。

    </div>

  </div>



  <?php elseif ($installed && empty($_POST['action'])) : ?>

  <div class="card">

    <h2>系统已安装</h2>

    <p style="font-size:14px;">检测到 <code>config.php</code> 且数据库连接正常。如需重新安装，请使用下方危险操作。</p>

    <p><a href="<?= $B ?>/index.php">进入首页 →</a></p>

    <div class="divider"></div>

    <h2 style="color:var(--err)">危险操作：重新安装</h2>

    <p style="font-size:13px;color:#7a8499;">将删除 config.php 并重新进入安装流程；数据库中的数据不会删除，重装时可选择「全新导入」覆盖原种子数据。</p>

    <form method="post" onsubmit="return confirm('确认要删除 config.php 并重新安装吗？')">

      <input type="hidden" name="action" value="reset">

      <label class="f">输入 RESET 确认</label>

      <input type="text" name="confirm" placeholder="RESET" required>

      <button class="primary" type="submit" style="background:var(--err)">执行重置</button>

    </form>

  </div>



  <?php else: ?>

  <div class="card">

    <h2>1 · 环境检查</h2>

    <table class="chk">

      <?php foreach ($checks as $name => $r): ?>

      <tr><td><?= h($name) ?></td><td style="text-align:right" class="<?= $r === 'ok' ? 'ok' : 'bad' ?>"><?= $r === 'ok' ? '通过' : '不满足' ?></td></tr>

      <?php endforeach; ?>

    </table>

    <?php if (!empty($error)): ?><div class="msg err"><?= h($error) ?></div><?php endif; ?>

  </div>



  <div class="card">

    <h2>2 · 数据库与管理员配置</h2>

    <form method="post" id="installForm">

      <input type="hidden" name="action" value="install">

      <label class="f">数据库类型</label>

      <div class="radios">

        <label><input type="radio" name="driver" value="mysql" <?= post_val('driver', 'mysql') === 'mysql' ? 'checked' : '' ?> onchange="toggleDb()"> MySQL / MariaDB（推荐）</label>

        <label><input type="radio" name="driver" value="sqlite" <?= post_val('driver', 'mysql') === 'sqlite' ? 'checked' : '' ?> onchange="toggleDb()"> SQLite（零依赖试用）</label>

      </div>



      <div id="mysqlFields">

        <div class="grid2">

          <div><label class="f">主机</label><input type="text" name="db_host" value="<?= post_val('db_host', '127.0.0.1') ?>"></div>

          <div><label class="f">端口</label><input type="text" name="db_port" value="<?= post_val('db_port', '3306') ?>"></div>

        </div>

        <div class="hint">主机不要填 <code>localhost</code> / <code>127.0.0.1</code>。InfinityFree 等免费空间请填控制面板“MySQL 数据库”里给的主机名，形如 <code>sqlXXX.infinityfree.com</code>；库名和用户名也按面板给的填（通常带 <code>if0_</code> 前缀），库需先在面板里创建。</div>

        <label class="f">数据库名（不存在将自动创建）</label>

        <input type="text" name="db_name" value="<?= post_val('db_name', 'techquickref') ?>">

        <div class="grid2">

          <div><label class="f">用户名</label><input type="text" name="db_user" value="<?= post_val('db_user', 'root') ?>"></div>

          <div><label class="f">密码</label><input type="password" name="db_pass" value="<?= post_val('db_pass', '') ?>"></div>

        </div>

      </div>



      <div id="sqliteFields">

        <div class="hint">将创建 <code>data/quickref.sqlite</code> 单文件数据库，适合个人本机快速体验；团队多人使用请选 MySQL/MariaDB。</div>

      </div>



      <div class="divider"></div>

      <label class="f">种子数据导入方式</label>

      <div class="radios">

        <label><input type="radio" name="seed_mode" value="fresh" checked> 全新导入（清空后写入完整种子）</label>

        <label><input type="radio" name="seed_mode" value="append"> 增量导入（保留已有栈，仅补充缺失）</label>

      </div>



      <div class="divider"></div>

      <label class="f">站点名称</label>

      <input type="text" name="site_name" value="<?= post_val('site_name', 'TechQuickRef 技术速查') ?>">

      <div class="grid2">

        <div><label class="f">管理员账号</label><input type="text" name="admin_user" value="<?= post_val('admin_user', 'admin') ?>"></div>

        <div><label class="f">管理员密码（≥6 位）</label><input type="password" name="admin_pass" value=""></div>

      </div>

      <label class="f">重复管理员密码</label>

      <input type="password" name="admin_pass2" value="">



      <button class="primary" type="submit">开始安装</button>

    </form>

    <script>

      function toggleDb() {

        var v = document.querySelector('input[name="driver"]:checked').value;

        document.getElementById('mysqlFields').style.display = v === 'mysql' ? '' : 'none';

        document.getElementById('sqliteFields').style.display = v === 'sqlite' ? '' : 'none';

      }

      toggleDb();

    </script>

    <div class="divider"></div>

    <div class="hint">

      安装会自动创建数据表并导入 PHP / HTML / CSS / JavaScript / MySQL / Java / C# / .NET / WPF / WinForm

      十个技术栈的预置速查清单；登录管理员后可在线增删改条目，团队其他成员注册普通账号后只读浏览。

    </div>

  </div>

  <?php endif; ?>

</div>

</body>

</html>





