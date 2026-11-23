<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';

header('Content-Type: application/json; charset=utf-8');

function api_out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** 仅管理员可调用的写操作校验（含 CSRF） */
function api_require_admin(): void
{
    if (!is_admin()) {
        api_out(['ok' => false, 'error' => '需要管理员权限'], 403);
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)$token)) {
        api_out(['ok' => false, 'error' => 'CSRF 校验失败'], 403);
    }
}

function body_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$action = (string)($_GET['action'] ?? '');

/* ---------- 搜索联想 ---------- */
if ($action === 'suggest') {
    $q = trim((string)($_GET['q'] ?? ''));
    if ($q === '' || mb_strlen($q) < 1) {
        api_out(['ok' => true, 'items' => []]);
    }
    $kw = '%' . str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $q) . '%';
    $rows = q_all(
        'SELECT e.title, c.name AS cat_name, s.name AS stack_hit,
                c.id AS cat_id, s.slug, s.name, s.icon
         FROM entries e
         JOIN categories c ON e.category_id = c.id
         JOIN stacks s ON c.stack_id = s.id
         WHERE e.title LIKE ? OR c.name LIKE ? OR s.name LIKE ?
         ORDER BY (e.title LIKE ?) DESC, s.sort, c.sort, e.sort
         LIMIT 8',
        [$kw, $kw, $kw, $kw]
    );
    $items = [];
    $seenCats = [];
    foreach ($rows as $r) {
        $label = (string)$r['title'];
        $isTitleHit = mb_stripos($label, $q) !== false;
        if (!$isTitleHit) {
            // 标题未命中（分类名或栈名命中）：同一分类只推荐一条
            $catKey = (int)$r['cat_id'];
            if (isset($seenCats[$catKey])) {
                continue;
            }
            $seenCats[$catKey] = true;
            $label = (string)$r['cat_name'];
        }
        $items[] = [
            'title' => $label,
            'stack' => (string)$r['name'],
            'icon' => stack_icon((string)$r['slug'], (string)$r['icon']),
            'url' => u('sheet.php') . '?stack=' . urlencode((string)$r['slug']) . '#cat-' . (int)$r['cat_id'],
        ];
    }
    api_out(['ok' => true, 'items' => $items]);
}

/* ---------- 以下为管理员写操作 ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_out(['ok' => false, 'error' => '方法不允许'], 405);
}
api_require_admin();
$in = body_json();

if ($action === 'save_entry') {
    $id = (int)($in['id'] ?? 0);
    $catId = (int)($in['category_id'] ?? 0);
    $title = trim((string)($in['title'] ?? ''));
    $code = (string)($in['code'] ?? '');
    $note = (string)($in['note'] ?? '');
    $link = trim((string)($in['link'] ?? ''));
    if ($title === '' || mb_strlen($title) > 200) {
        api_out(['ok' => false, 'error' => '标题必填且不超过 200 字']);
    }
    if (!q_val('SELECT id FROM categories WHERE id = ?', [$catId])) {
        api_out(['ok' => false, 'error' => '分类不存在']);
    }
    $user = current_user();
    $by = (string)($user['username'] ?? '');
    if ($id > 0) {
        if (!q_val('SELECT id FROM entries WHERE id = ?', [$id])) {
            api_out(['ok' => false, 'error' => '条目不存在']);
        }
        q('UPDATE entries SET category_id = ?, title = ?, code = ?, note = ?, link = ?, updated_by = ?, updated_at = ? WHERE id = ?',
            [$catId, $title, $code, $note, $link !== '' ? $link : null, $by, db_now(), $id]);
        api_out(['ok' => true, 'id' => $id]);
    }
    $next = (int)q_val('SELECT COALESCE(MAX(sort), 0) + 1 FROM entries WHERE category_id = ?', [$catId]);
    q('INSERT INTO entries (category_id, title, code, note, link, sort, updated_by, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$catId, $title, $code, $note, $link !== '' ? $link : null, $next, $by, db_now()]);
    api_out(['ok' => true, 'id' => (int)db()->lastInsertId()]);
}

if ($action === 'delete_entry') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0 || !q_val('SELECT id FROM entries WHERE id = ?', [$id])) {
        api_out(['ok' => false, 'error' => '条目不存在']);
    }
    q('DELETE FROM entries WHERE id = ?', [$id]);
    api_out(['ok' => true]);
}

if ($action === 'delete_category') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0 || !q_val('SELECT id FROM categories WHERE id = ?', [$id])) {
        api_out(['ok' => false, 'error' => '分类不存在']);
    }
    q('DELETE FROM entries WHERE category_id = ?', [$id]);
    q('DELETE FROM categories WHERE id = ?', [$id]);
    api_out(['ok' => true]);
}

api_out(['ok' => false, 'error' => '未知操作'], 400);
