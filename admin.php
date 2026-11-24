<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';
require_admin();

$me = current_user();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check_post();
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'create_user') {
            $username = trim((string)($_POST['username'] ?? ''));
            $display = trim((string)($_POST['display_name'] ?? ''));
            $pass = (string)($_POST['password'] ?? '');
            $role = ($_POST['role'] ?? 'member') === 'admin' ? 'admin' : 'member';
            if (!preg_match('/^[a-zA-Z0-9_]{2,50}$/', $username)) {
                throw new RuntimeException('用户名仅限字母数字下划线（2-50 位）');
            }
            if (strlen($pass) < 6) {
                throw new RuntimeException('密码至少 6 位');
            }
            if (q_val('SELECT id FROM users WHERE username = ?', [$username])) {
                throw new RuntimeException('用户名已存在');
            }
            q('INSERT INTO users (username, password_hash, display_name, role) VALUES (?, ?, ?, ?)',
                [$username, password_hash($pass, PASSWORD_DEFAULT), mb_substr($display ?: $username, 0, 100), $role]);
            flash_set('success', '已创建用户 ' . $username . '（' . $role . '）');
        } 
        elseif ($action === 'set_role') {
            $uid = (int)($_POST['uid'] ?? 0);
            $role = ($_POST['role'] ?? 'member') === 'admin' ? 'admin' : 'member';
            if ($uid === (int)$me['id'] && $role !== 'admin') {
                throw new RuntimeException('不能取消自己的管理员权限');
            }
            q('UPDATE users SET role = ? WHERE id = ?', [$role, $uid]);
            flash_set('success', '已更新用户角色');
        } 
        elseif ($action === 'delete_user') {
            $uid = (int)($_POST['uid'] ?? 0);
            if ($uid === (int)$me['id']) {
                throw new RuntimeException('不能删除自己');
            }
            q('DELETE FROM users WHERE id = ?', [$uid]);
            flash_set('success', '已删除用户');
        } 
        elseif ($action === 'reset_user_pass') {
            $uid = (int)($_POST['uid'] ?? 0);
            $pass = (string)($_POST['password'] ?? '');
            if (strlen($pass) < 6) {
                throw new RuntimeException('新密码至少 6 位');
            }
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $uid]);
            flash_set('success', '已重置密码');
        } 
        elseif ($action === 'save_settings') {
            $site = trim((string)($_POST['site_name'] ?? '')) ?: 'TechQuickRef 技术速查';
            $allow = isset($_POST['allow_register']) ? '1' : '0';
            setting_set('site_name', mb_substr($site, 0, 60));
            setting_set('allow_register', $allow);
            flash_set('success', '站点设置已保存');
        } 
        elseif ($action === 'change_my_pass') {
            $old = (string)($_POST['old_pass'] ?? '');
            $new = (string)($_POST['new_pass'] ?? '');
            $row = q_row('SELECT password_hash FROM users WHERE id = ?', [(int)$me['id']]);
            if (!$row || !password_verify($old, (string)$row['password_hash'])) {
                throw new RuntimeException('旧密码不正确');
            }
            if (strlen($new) < 6) {
                throw new RuntimeException('新密码至少 6 位');
            }
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), (int)$me['id']]);
            flash_set('success', '密码已修改');
        } 
        elseif ($action === 'save_stack') {
            $id = (int)($_POST['id'] ?? 0);
            $slug = trim((string)($_POST['slug'] ?? ''));
            $name = trim((string)($_POST['name'] ?? ''));
            $grp = trim((string)($_POST['grp'] ?? '其他')) ?: '其他';
            $icon = trim((string)($_POST['icon'] ?? '')) ?: '';
            $color = trim((string)($_POST['color'] ?? '#5b6ef5')) ?: '#5b6ef5';
            $desc = trim((string)($_POST['description'] ?? ''));
            if (!preg_match('/^[a-z0-9-]{1,50}$/', $slug)) {
                throw new RuntimeException('slug 仅限小写字母数字连字符');
            }
            if ($name === '' || mb_strlen($name) > 100) {
                throw new RuntimeException('名称必填且不超过 100 字');
            }
            $dup = q_val('SELECT id FROM stacks WHERE slug = ? AND id != ?', [$slug, $id]);
            if ($dup) {
                throw new RuntimeException('slug 已被占用');
            }
            if ($id > 0) {
                q('UPDATE stacks SET slug = ?, name = ?, grp = ?, icon = ?, color = ?, description = ? WHERE id = ?',
                    [$slug, $name, $grp, mb_substr($icon, 0, 16), mb_substr($color, 0, 20), mb_substr($desc, 0, 255), $id]);
                flash_set('success', '技术栈已更新');
            } else {
                $next = (int)q_val('SELECT COALESCE(MAX(sort), 0) + 10 FROM stacks');
                q('INSERT INTO stacks (slug, name, grp, icon, color, description, sort) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [$slug, $name, $grp, mb_substr($icon, 0, 16), mb_substr($color, 0, 20), mb_substr($desc, 0, 255), $next]);
                flash_set('success', '技术栈已创建，去清单页添加分类和条目吧');
            }
        } 
        elseif ($action === 'delete_stack') {
            $id = (int)($_POST['id'] ?? 0);
            if (db_driver() === 'mysql') {
                db()->exec('SET FOREIGN_KEY_CHECKS = 0');
                q('DELETE FROM entries WHERE category_id IN (SELECT id FROM categories WHERE stack_id = ?)', [$id]);
                q('DELETE FROM categories WHERE stack_id = ?', [$id]);
                q('DELETE FROM stacks WHERE id = ?', [$id]);
                db()->exec('SET FOREIGN_KEY_CHECKS = 1');
            } else {
                q('DELETE FROM categories WHERE stack_id = ?', [$id]);
                q('DELETE FROM stacks WHERE id = ?', [$id]);
            }
            flash_set('success', '技术栈及其内容已删除');
        }
    } catch (Exception $ex) {
        $error = $ex->getMessage();
    }
    redirect('admin.php');
}

$stats = stats_counts();
$users = q_all('SELECT id, username, display_name, role, created_at FROM users ORDER BY id');
$stacks = q_all(
    'SELECT s.*,
        (SELECT COUNT(*) FROM categories c WHERE c.stack_id = s.id) AS cat_count,
        (SELECT COUNT(*) FROM entries e JOIN categories c ON e.category_id = c.id WHERE c.stack_id = s.id) AS entry_count
     FROM stacks s ORDER BY s.sort, s.id'
);
$editId = (int)($_GET['edit_stack'] ?? 0);
$editStack = $editId ? q_row('SELECT * FROM stacks WHERE id = ?', [$editId]) : null;
$allowRegister = setting_get('allow_register', '1') === '1';

page_header(['title' => '管理后台']);
?>
<section class="admin-head">
  <h1><?= icon('settings') ?>管理后台</h1>
  <p class="hint">管理团队账号、技术栈与站点设置。条目的增删改在各技术栈清单页内直接进行（条目右侧的铅笔图标）。</p>
  <?= flash_render() ?>
  <?php if ($error !== ''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <div class="stat-row">
    <div class="stat-card"><b><?= (int)$stats['stacks'] ?></b><span>技术栈</span></div>
    <div class="stat-card"><b><?= (int)$stats['cats'] ?></b><span>分类</span></div>
    <div class="stat-card"><b><?= (int)$stats['entries'] ?></b><span>速查条目</span></div>
    <div class="stat-card"><b><?= (int)$stats['users'] ?></b><span>团队用户</span></div>
  </div>
</section>

<section class="cat-block">
  <h2 class="cat-title">内容导入</h2>
  <p class="hint">批量加内容走这两个入口，导入完会给出跳到对应清单页的按钮。这两个页面用完建议删除。</p>
  <div style="display:flex;flex-wrap:wrap;gap:10px">
    <?php if (is_file(__DIR__ . '/import-md.php')): ?>
      <a class="btn btn-primary" href="<?= u('import-md.php') ?>"><?= icon('upload') ?>导入 Markdown（粘贴 / .md 文件）</a>
    <?php endif; ?>
    <?php if (is_file(__DIR__ . '/add-seed.php')): ?>
      <a class="btn btn-ghost" href="<?= u('add-seed.php') ?>"><?= icon('database') ?>导入内置种子（只补缺失分类）</a>
    <?php endif; ?>
    <?php if (is_file(__DIR__ . '/php-check.php')): ?>
      <a class="btn btn-ghost" href="<?= u('php-check.php') ?>"><?= icon('activity') ?>部署自检</a>
    <?php endif; ?>
    <a class="btn btn-ghost" href="<?= u('music.php') ?>"><?= icon('list-music') ?>音乐 / 歌单管理</a>
    <?php if (!is_file(__DIR__ . '/import-md.php') && !is_file(__DIR__ . '/add-seed.php')): ?>
      <span class="hint">导入页已清理，需要时重新上传 import-md.php 即可。</span>
    <?php endif; ?>
  </div>
</section>

<section class="cat-block">
  <h2 class="cat-title">站点设置</h2>
  <form method="post" class="admin-form">
    <input type="hidden" name="action" value="save_settings">
    <?= csrf_field() ?>
    <label class="field"><span>站点名称</span>
      <input type="text" name="site_name" value="<?= e(site_name()) ?>" maxlength="60">
    </label>
    <label class="field inline"><input type="checkbox" name="allow_register" <?= $allowRegister ? 'checked' : '' ?>>
      <span>开放注册（新注册用户为只读成员）</span>
    </label>
    <button class="btn btn-primary" type="submit">保存设置</button>
  </form>
</section>

<section class="cat-block">
  <h2 class="cat-title">修改我的密码</h2>
  <form method="post" class="admin-form">
    <input type="hidden" name="action" value="change_my_pass">
    <?= csrf_field() ?>
    <div class="grid2">
      <label class="field"><span>旧密码</span><input type="password" name="old_pass" required></label>
      <label class="field"><span>新密码（≥6 位）</span><input type="password" name="new_pass" required minlength="6"></label>
    </div>
    <button class="btn btn-primary" type="submit">修改密码</button>
  </form>
</section>

<section class="cat-block">
  <h2 class="cat-title">技术栈管理</h2>
  <table class="data-table">
    <tr><th>#</th><th>图标</th><th>名称</th><th>分组</th><th>条目</th><th>操作</th></tr>
    <?php foreach ($stacks as $s): ?>
    <tr>
      <td><?= (int)$s['id'] ?></td>
      <td><?= stack_icon((string)$s['slug'], (string)($s['icon'] ?? '')) ?></td>
      <td><a href="<?= u('sheet.php') ?>?stack=<?= urlencode((string)$s['slug']) ?>"><?= e((string)$s['name']) ?></a></td>
      <td><?= e((string)$s['grp']) ?></td>
      <td><?= (int)$s['entry_count'] ?> / <?= (int)$s['cat_count'] ?> 类</td>
      <td>
        <a class="btn btn-ghost btn-sm" href="<?= u('admin.php') ?>?edit_stack=<?= (int)$s['id'] ?>">编辑</a>
        <form method="post" style="display:inline" onsubmit="return confirm('删除技术栈将连同其分类与条目一起删除，确认？')">
          <input type="hidden" name="action" value="delete_stack">
          <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
          <?= csrf_field() ?>
          <button class="btn btn-danger btn-sm" type="submit">删除</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>

  <h3 class="admin-sub"><?= $editStack ? '编辑技术栈 #' . (int)$editStack['id'] : '新增技术栈' ?></h3>
  <form method="post" class="admin-form">
    <input type="hidden" name="action" value="save_stack">
    <input type="hidden" name="id" value="<?= $editStack ? (int)$editStack['id'] : 0 ?>">
    <?= csrf_field() ?>
    <div class="grid2">
      <label class="field"><span>编号（数字）</span>
        <input type="text" name="slug" required pattern="[a-z0-9\-]{1,50}" value="<?= e((string)($editStack['slug'] ?? '')) ?>">
      </label>
      <label class="field"><span>名称</span>
        <input type="text" name="name" required maxlength="100" value="<?= e((string)($editStack['name'] ?? '')) ?>">
      </label>
      <label class="field"><span>分组</span>
        <input type="text" name="grp" maxlength="50" placeholder="前端 / 后端 / 数据库 / .NET 生态" value="<?= e((string)($editStack['grp'] ?? '')) ?>">
      </label>
      <label class="field"><span>图标（Lucide 名称，留空自动匹配）</span>
        <input type="text" name="icon" maxlength="16" value="<?= e((string)($editStack['icon'] ?? '')) ?>" placeholder="database">
      </label>
      <label class="field"><span>主题色（已并入站点主色）</span>
        <input type="text" name="color" maxlength="20" placeholder="#2563eb" value="<?= e((string)($editStack['color'] ?? '#2563eb')) ?>">
      </label>
      <label class="field"><span>简介</span>
        <input type="text" name="description" maxlength="255" value="<?= e((string)($editStack['description'] ?? '')) ?>">
      </label>
    </div>
    <button class="btn btn-primary" type="submit"><?= $editStack ? '保存修改' : '创建技术栈' ?></button>
    <?php if ($editStack): ?>
    <a class="btn btn-ghost" href="<?= u('admin.php') ?>">取消编辑</a>
    <?php endif; ?>
  </form>
</section>

<section class="cat-block">
  <h2 class="cat-title">团队用户</h2>
  <table class="data-table">
    <tr><th>#</th><th>用户名</th><th>昵称</th><th>角色</th><th>注册时间</th><th>操作</th></tr>
    <?php foreach ($users as $usr): ?>
    <tr>
      <td><?= (int)$usr['id'] ?></td>
      <td><?= e((string)$usr['username']) ?></td>
      <td><?= e((string)$usr['display_name']) ?></td>
      <td>
        <form method="post" style="display:flex;gap:6px;align-items:center">
          <input type="hidden" name="action" value="set_role">
          <input type="hidden" name="uid" value="<?= (int)$usr['id'] ?>">
          <?= csrf_field() ?>
          <select name="role" <?= ((int)$usr['id'] === (int)$me['id']) ? 'disabled' : '' ?>>
            <option value="member" <?= $usr['role'] === 'member' ? 'selected' : '' ?>>member</option>
            <option value="admin" <?= $usr['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
          </select>
          <button class="btn btn-ghost btn-sm" type="submit">改角色</button>
        </form>
      </td>
      <td><?= e((string)$usr['created_at']) ?></td>
      <td>
        <?php if ((int)$usr['id'] !== (int)$me['id']): ?>
        <form method="post" style="display:inline" onsubmit="return confirm('确认删除用户 <?= e((string)$usr['username']) ?> ？')">
          <input type="hidden" name="action" value="delete_user">
          <input type="hidden" name="uid" value="<?= (int)$usr['id'] ?>">
          <?= csrf_field() ?>
          <button class="btn btn-danger btn-sm" type="submit">删除</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>

  <h3 class="admin-sub">新增用户</h3>
  <form method="post" class="admin-form">
    <input type="hidden" name="action" value="create_user">
    <?= csrf_field() ?>
    <div class="grid2">
      <label class="field"><span>用户名</span>
        <input type="text" name="username" required pattern="[a-zA-Z0-9_]{2,50}">
      </label>
      <label class="field"><span>昵称（可选）</span>
        <input type="text" name="display_name" maxlength="100">
      </label>
      <label class="field"><span>密码（≥6 位）</span>
        <input type="text" name="password" required minlength="6">
      </label>
      <label class="field"><span>角色</span>
        <select name="role">
          <option value="member">member（只读）</option>
          <option value="admin">admin（可编辑）</option>
        </select>
      </label>
    </div>
    <button class="btn btn-primary" type="submit">创建用户</button>
  </form>
</section>

<?php
page_footer();
