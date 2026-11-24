<?php
declare(strict_types=1);

/**
 * 分片上传接口（仅管理员）
 *
 * 单文件超过主机 upload_max_filesize / post_max_size 时，前端把文件切成 1–2MB 的小块，
 * 逐片 POST 到这里；服务端把分片按序拼回完整文件，再做一次校验，通过后登记进歌单。
 * 合并后的文件和直接上传的文件完全一样，播放器照常播放（不需要前端特殊处理）。
 *
 * 所有请求：已登录管理员 + X-CSRF-Token（等于页面里的 window.CSRF）。
 * 返回统一 JSON：{ok: true, ...} / {ok: false, error: '...'}
 */

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function up_out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function up_require_admin(): void
{
    if (!is_admin()) {
        up_out(['ok' => false, 'error' => '需要管理员权限'], 403);
    }
    $token = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $sess = (string)($_SESSION['csrf'] ?? '');
    if ($sess === '' || !hash_equals($sess, $token)) {
        up_out(['ok' => false, 'error' => 'CSRF 校验失败，请刷新页面'], 403);
    }
}

$action = (string)($_GET['action'] ?? '');

try {
    up_require_admin();

    if ($action === 'init') {
        $res = music_chunk_init(
            (string)($_POST['name'] ?? ''),
            (int)($_POST['size'] ?? 0),
            (int)($_POST['chunks'] ?? 0)
        );
        up_out(['ok' => true] + $res);
    }

    if ($action === 'chunk') {
        $id = (string)($_POST['id'] ?? '');
        $index = (int)($_POST['index'] ?? -1);
        $file = is_array($_FILES['blob'] ?? null) ? $_FILES['blob'] : [];
        $n = music_chunk_save($id, $index, $file);
        up_out(['ok' => true, 'index' => $index, 'bytes' => $n]);
    }

    if ($action === 'finish') {
        $id = (string)($_POST['id'] ?? '');
        $meta = music_chunk_meta($id);
        if ($meta === null) {
            up_out(['ok' => false, 'error' => '上传会话不存在或已过期，请重新上传'], 400);
        }
        $bytes = (int)$meta['size'];
        $up = music_upload_finish($id, (string)($_POST['title'] ?? ''), (string)($_POST['artist'] ?? ''));
        flash_set('success', music_upload_message($up, $bytes));
        up_out([
            'ok' => true,
            'url' => u('music.php'),
            'id' => $up['id'],
            'title' => $up['title'],
            'artist' => $up['artist'],
        ]);
    }

    if ($action === 'abort') {
        music_chunk_abort((string)($_POST['id'] ?? ''));
        up_out(['ok' => true]);
    }

    up_out(['ok' => false, 'error' => '未知操作'], 400);
} catch (Throwable $ex) {
    up_out(['ok' => false, 'error' => $ex->getMessage()], 400);
}
