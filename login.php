<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';

/* ---------- 登出 ---------- */
if (isset($_GET['logout'])) {
    unset($_SESSION['uid'], $_SESSION['csrf']);
    session_regenerate_id(true);
    flash_set('success', '已退出登录');
    redirect('index.php');
}

$allowRegister = setting_get('allow_register', '1') === '1';
$error = '';

/* ---------- 登录 / 注册 ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_post();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'login') {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $user = $username ? q_row('SELECT * FROM users WHERE username = ?', [$username]) : null;
        if ($user && password_verify($password, (string)$user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$user['id'];
            flash_set('success', '欢迎回来，' . ((string)$user['display_name'] !== '' ? (string)$user['display_name'] : (string)$user['username']));
            redirect('index.php');
        }
        $error = '用户名或密码不正确';
    } elseif ($action === 'register' && $allowRegister) {
        $username = trim((string)($_POST['username'] ?? ''));
        $display = trim((string)($_POST['display_name'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $password2 = (string)($_POST['password2'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_]{2,50}$/', $username)) {
            $error = '用户名仅限字母数字下划线，2-50 位';
        } elseif (strlen($password) < 6) {
            $error = '密码至少 6 位';
        } elseif ($password !== $password2) {
            $error = '两次输入的密码不一致';
        } elseif (q_val('SELECT id FROM users WHERE username = ?', [$username])) {
            $error = '该用户名已被占用';
        } else {
            q('INSERT INTO users (username, password_hash, display_name, role) VALUES (?, ?, ?, "member")',
                [$username, password_hash($password, PASSWORD_DEFAULT), mb_substr($display ?: $username, 0, 100)]);
            $newId = (int)db()->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['uid'] = $newId;
            flash_set('success', '注册成功，欢迎加入团队速查库！');
            redirect('index.php');
        }
    }
}

page_header(['title' => '登录']);
?>
<section class="auth-box">
  <div class="auth-card">
    <h1><?= icon('log-in') ?>欢迎使用</h1>
    <p class="auth-sub">登录后管理员可编辑速查条目，普通成员可浏览与搜索。</p>
    <?= flash_render() ?>
    <?php if ($error !== ''): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="auth-form">
      <input type="hidden" name="action" value="login">
      <?= csrf_field() ?>
      <label class="field"><span>用户名</span>
        <input type="text" name="username" required autocomplete="username" autofocus>
      </label>
      <label class="field"><span>密码</span>
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <button class="btn btn-primary btn-block" type="submit">登录</button>
    </form>

    <?php if ($allowRegister): ?>
    <div class="auth-divider"><span>或注册团队账号</span></div>
    <form method="post" class="auth-form">
      <input type="hidden" name="action" value="register">
      <?= csrf_field() ?>
      <label class="field"><span>用户名（字母数字下划线）</span>
        <input type="text" name="username" required pattern="[a-zA-Z0-9_]{2,50}">
      </label>
      <label class="field"><span>昵称（可选）</span>
        <input type="text" name="display_name" maxlength="100">
      </label>
      <div class="grid2">
        <label class="field"><span>密码（≥6 位）</span>
          <input type="password" name="password" required minlength="6">
        </label>
        <label class="field"><span>重复密码</span>
          <input type="password" name="password2" required minlength="6">
        </label>
      </div>
      <button class="btn btn-ghost btn-block" type="submit">注册（普通成员 · 只读）</button>
    </form>
    <?php else: ?>
    <p class="hint">当前站点已关闭注册，请联系管理员开通账号。</p>
    <?php endif; ?>
  </div>
</section>
<?php
page_footer();
