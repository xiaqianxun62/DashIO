<?php
declare(strict_types=1);

/**
 * 音乐播放 - 数据层
 *
 * 曲目来源有两种，混成同一张歌单：
 *   1) 数据库 tracks 表里的记录（外链 URL，或 assets/music 下的文件被显式登记）
 *   2) assets/music 目录下扫到、但还没登记的本地音频文件（虚拟条目，id = 0，不写库）
 *
 * 表是懒建的：第一次用到时自动 CREATE TABLE IF NOT EXISTS，不需要改 install.php。
 */

const TQR_AUDIO_EXT = ['mp3', 'm4a', 'aac', 'ogg', 'oga', 'wav', 'flac', 'webm'];

/** 分片上传：每片大小 / 合并后总大小上限 / 临时目录保留时长 */
const TQR_CHUNK_SIZE = 10485760;  // 10MB（实际取主机单请求上限，略小几十 KB 防超）
const TQR_CHUNK_MAX = 1048576000;   // 1G
const TQR_CHUNK_TTL = 86400;      // 24 小时没合成成功就自动清掉

/** 整包直传的硬上限：和分片合并上限保持一致，实际可传大小仍受主机 php.ini 限制 */
const TQR_UPLOAD_MAX = 1048576000;  // 1G

/** 每个扩展名可接受的 MIME（有些浏览器/主机会给 octet-stream） */
const TQR_AUDIO_MIME = [
    'mp3' => ['audio/mpeg', 'audio/mp3', 'audio/x-mpeg-3', 'audio/mpeg3', 'application/octet-stream'],
    'm4a' => ['audio/mp4', 'audio/x-m4a', 'audio/aac', 'application/octet-stream'],
    'aac' => ['audio/aac', 'audio/x-aac', 'audio/aacp', 'application/octet-stream'],
    'ogg' => ['audio/ogg', 'application/ogg', 'audio/vorbis', 'application/octet-stream'],
    'oga' => ['audio/ogg', 'application/ogg', 'application/octet-stream'],
    'wav' => ['audio/wav', 'audio/x-wav', 'audio/wave', 'audio/vnd.wave', 'application/octet-stream'],
    'flac' => ['audio/flac', 'audio/x-flac', 'application/octet-stream'],
    'webm' => ['audio/webm', 'video/webm', 'application/octet-stream'],
];

/* 标签解析是可选增强：文件没传上去时不要让整站 500 */
if (is_file(__DIR__ . '/audio_tags.php')) {
    require_once __DIR__ . '/audio_tags.php';
}
if (!function_exists('audio_read_tags')) {
    /** @return array{title:string,artist:string} */
    function audio_read_tags(string $path): array
    {
        return ['title' => '', 'artist' => ''];
    }
}

/** 音频目录（相对站点根） */
function music_dir_rel(): string
{
    return 'assets/music';
}

/** 音频目录绝对路径 */
function music_dir_abs(): string
{
    return dirname(__DIR__) . '/' . music_dir_rel();
}

/** 建表（按驱动区分 DDL），每进程只跑一次 */
function music_ensure_table(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (db_driver() === 'sqlite') {
        q("CREATE TABLE IF NOT EXISTS tracks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            artist TEXT NOT NULL DEFAULT '',
            src TEXT NOT NULL UNIQUE,
            sort INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        return;
    }

    q("CREATE TABLE IF NOT EXISTS tracks (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        artist VARCHAR(120) NOT NULL DEFAULT '',
        src VARCHAR(500) NOT NULL,
        sort INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_track_src (src(191))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/** 文件名字 → 歌名（去扩展名，下划线/连字符换空格） */
function music_title_from_file(string $file): string
{
    $name = (string)pathinfo($file, PATHINFO_FILENAME);
    $name = str_replace(['_', '-'], ' ', $name);
    $name = trim((string)preg_replace('/\s+/u', ' ', $name));
    return $name !== '' ? $name : $file;
}

/**
 * 扫描 assets/music，返回 [相对路径 => 歌名]，按文件名排序
 */
function music_local_files(): array
{
    $dir = music_dir_abs();
    if (!is_dir($dir)) {
        return [];
    }
    $files = glob($dir . '/*') ?: [];
    $out = [];
    foreach ($files as $f) {
        if (!is_file($f)) {
            continue;
        }
        $ext = strtolower((string)pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, TQR_AUDIO_EXT, true)) {
            continue;
        }
        $rel = music_dir_rel() . '/' . basename($f);
        $out[$rel] = music_title_from_file(basename($f));
    }
    ksort($out);
    return $out;
}

/**
 * 完整歌单：库里登记过的 + 目录里扫到但未登记的（虚拟条目）
 *
 * 每条：id(int，0 表示未入库)、title、artist、src、local(bool)
 */
function music_tracks(): array
{
    if (isset($GLOBALS['tqr_music_cache'])) {
        return $GLOBALS['tqr_music_cache'];
    }

    try {
        $rows = q_all('SELECT id, title, artist, src FROM tracks ORDER BY sort, id');
    } catch (Throwable $ex) {
        // 表还不存在（站点刚装好、还没人打开过音乐页）→ 建表后重试一次
        try {
            music_ensure_table();
            $rows = q_all('SELECT id, title, artist, src FROM tracks ORDER BY sort, id');
        } catch (Throwable $ex2) {
            return $GLOBALS['tqr_music_cache'] = [];
        }
    }

    $out = [];
    $known = [];
    foreach ($rows as $r) {
        $src = (string)$r['src'];
        $known[$src] = true;
        $out[] = [
            'id' => (int)$r['id'],
            'title' => (string)$r['title'],
            'artist' => (string)$r['artist'],
            'src' => $src,
            'local' => !preg_match('#^(https?:)?//#i', $src),
        ];
    }

    foreach (music_local_files() as $rel => $title) {
        if (isset($known[$rel])) {
            continue;
        }
        $out[] = [
            'id' => 0,
            'title' => $title,
            'artist' => '',
            'src' => $rel,
            'local' => true,
        ];
    }

    return $GLOBALS['tqr_music_cache'] = $out;
}

/** 写操作后清掉歌单缓存，避免同一次请求里读到旧列表 */
function music_cache_clear(): void
{
    unset($GLOBALS['tqr_music_cache']);
}

/**
 * 校验并归一化音源地址：
 *   - http(s):// 开头的按外链处理
 *   - 其他一律视为 assets/music 下的文件，必须存在
 * 返回 null 表示不合法
 */
function music_normalize_src(string $src): ?string
{
    $src = trim($src);
    if ($src === '') {
        return null;
    }
    if (preg_match('#^https?://#i', $src) === 1) {
        return $src;
    }
    $src = ltrim(str_replace('\\', '/', $src), '/');
    $dir = music_dir_rel() . '/';
    if (strpos($src, $dir) !== 0) {
        return null;
    }
    $name = basename($src);
    if ($name === '' || strpos($name, '.') === false) {
        return null;
    }
    if (!is_file(music_dir_abs() . '/' . $name)) {
        return null;
    }
    return $dir . $name;
}

/** 新增曲目（返回新 id，失败抛异常） */
function music_add(string $title, string $artist, string $src): int
{
    music_ensure_table();
    $title = trim($title);
    if ($title === '') {
        throw new RuntimeException('歌名不能为空');
    }
    $norm = music_normalize_src($src);
    if ($norm === null) {
        throw new RuntimeException('音源地址不合法：外链需以 http(s):// 开头，本地文件必须在 assets/music 目录下');
    }
    if (q_val('SELECT id FROM tracks WHERE src = ?', [$norm])) {
        throw new RuntimeException('这首已经加过了');
    }
    $next = (int)q_val('SELECT COALESCE(MAX(sort), 0) + 1 FROM tracks');
    q('INSERT INTO tracks (title, artist, src, sort) VALUES (?, ?, ?, ?)',
        [mb_substr($title, 0, 200), mb_substr(trim($artist), 0, 120), $norm, $next]);

    music_cache_clear();
    return (int)db()->lastInsertId();
}

/** 更新曲目信息 */
function music_update(int $id, string $title, string $artist, int $sort): void
{
    music_ensure_table();
    if (!q_val('SELECT id FROM tracks WHERE id = ?', [$id])) {
        throw new RuntimeException('曲目不存在');
    }
    if (trim($title) === '') {
        throw new RuntimeException('歌名不能为空');
    }
    q('UPDATE tracks SET title = ?, artist = ?, sort = ? WHERE id = ?',
        [mb_substr(trim($title), 0, 200), mb_substr(trim($artist), 0, 120), $sort, $id]);
    music_cache_clear();
}

/** 删除曲目记录（不动磁盘文件） */
function music_delete(int $id): void
{
    music_ensure_table();
    q('DELETE FROM tracks WHERE id = ?', [$id]);
    music_cache_clear();
}

/** 把目录里扫到但未登记的本地文件批量入库，返回登记数量（优先用文件标签里的歌名 / 歌手） */
function music_import_local(): int
{
    music_ensure_table();
    $added = 0;
    foreach (music_local_files() as $rel => $title) {
        if (q_val('SELECT id FROM tracks WHERE src = ?', [$rel])) {
            continue;
        }
        $tags = audio_read_tags(music_dir_abs() . '/' . basename($rel));
        $name = $tags['title'] !== '' ? $tags['title'] : $title;
        $artist = $tags['artist'];
        $next = (int)q_val('SELECT COALESCE(MAX(sort), 0) + 1 FROM tracks');
        q('INSERT INTO tracks (title, artist, src, sort) VALUES (?, ?, ?, ?)', [$name, $artist, $rel, $next]);
        $added++;
    }
    music_cache_clear();
    return $added;
}

/** 秒 → mm:ss */
function music_fmt_time(float $sec): string
{
    if (!is_finite($sec) || $sec < 0) {
        $sec = 0;
    }
    $s = (int)round($sec);
    return sprintf('%d:%02d', intdiv($s, 60), $s % 60);
}

/* ---------- 上传 ---------- */

/** php.ini 里的 "10M" / "512K" → 字节 */
function music_ini_bytes(string $val): int
{
    $val = trim($val);
    if ($val === '') {
        return 0;
    }
    $unit = strtolower(substr($val, -1));
    $num = (float)$val;
    if ($unit === 'g') {
        $num *= 1073741824;
    } elseif ($unit === 'm') {
        $num *= 1048576;
    } elseif ($unit === 'k') {
        $num *= 1024;
    }
    return (int)$num;
}

/** 当前真正可用的单文件上传上限（字节） */
function music_upload_limit(): int
{
    $cands = [TQR_UPLOAD_MAX];
    foreach (['upload_max_filesize', 'post_max_size'] as $k) {
        $v = music_ini_bytes((string)ini_get($k));
        if ($v > 0) {
            $cands[] = $v;
        }
    }
    return min($cands);
}

/** 字节 → "10 MB" 这种提示文案 */
function music_size_label(int $bytes): string
{
    if ($bytes >= 1048576) {
        return rtrim(rtrim(number_format($bytes / 1048576, 1, '.', ''), '0'), '.') . ' MB';
    }
    return max(1, (int)round($bytes / 1024)) . ' KB';
}

/** 上传文件名清洗：保留中英文、数字、点、下划线、连字符 */
function music_safe_filename(string $name): string
{
    $base = (string)pathinfo($name, PATHINFO_FILENAME);
    $ext = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
    $base = str_replace(['/', '\\', "\0"], ' ', $base);
    $base = (string)preg_replace('/[^\p{L}\p{N}._\- ]+/u', '', $base);
    $base = trim((string)preg_replace('/[\s_]+/u', ' ', $base));
    if ($base === '') {
        $base = 'track-' . date('Ymd-His');
    }
    $base = mb_substr($base, 0, 80);
    return $base . ($ext !== '' ? '.' . $ext : '');
}

/**
 * 客户端传来的文件名归一化：
 *   - 去掉路径与引号、换行
 *   - 少数客户端会把非 ASCII 文件名按 RFC 2047 编码（=?utf-8?B?...?=），这里还原
 */
function music_client_filename(string $name): string
{
    $name = trim(str_replace(["\r", "\n", '"'], '', $name));
    if (preg_match('/^=\?([^?]+)\?([BbQq])\?([^?]*)\?=/', $name, $m) === 1) {
        $charset = strtoupper($m[1]) === 'UTF-8' ? 'UTF-8' : $m[1];
        $body = $m[3];
        $raw = strtoupper($m[2]) === 'B' ? base64_decode($body, true) : quoted_printable_decode($body);
        if ($raw !== false && $raw !== '') {
            $name = function_exists('mb_convert_encoding') ? mb_convert_encoding($raw, 'UTF-8', $charset) : $raw;
        }
    }
    return basename($name);
}

/**
 * 校验一个音频文件（临时路径）
 * 校验：大小、扩展名白名单、真实 MIME、内容里不能出现 PHP 标签
 * 返回小写扩展名，不通过就抛异常
 */
function music_validate_audio_file(string $tmpPath, string $origName, int $size, int $maxSize): string
{
    if ($size <= 0) {
        throw new RuntimeException('文件是空的');
    }
    if ($size > $maxSize) {
        throw new RuntimeException('文件太大：' . music_size_label($size) . '，上限 ' . music_size_label($maxSize));
    }
    $orig = music_client_filename($origName);
    if ($orig === '') {
        throw new RuntimeException('文件名是空的');
    }
    $ext = strtolower((string)pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, TQR_AUDIO_EXT, true)) {
        throw new RuntimeException('只支持 ' . implode(' / ', TQR_AUDIO_EXT) . ' 格式');
    }

    /* 真实类型：有 fileinfo 就查，没有就退化为扩展名校验 */
    if (class_exists('finfo')) {
        $fi = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$fi->file($tmpPath);
        $allow = TQR_AUDIO_MIME[$ext] ?? [];
        if ($allow !== [] && !in_array(strtolower($mime), $allow, true)) {
            throw new RuntimeException('文件内容不像 ' . $ext . ' 音频（识别为 ' . $mime . '）');
        }
    }

    /* 内容里带 PHP 标签的一律拒绝：目录里就算被塞进可执行文件也不至于跑起来 */
    $head = (string)file_get_contents($tmpPath, false, null, 0, 512);
    if (stripos($head, '<?php') !== false || stripos($head, '<?=') !== false) {
        throw new RuntimeException('文件内容不合法：像是脚本文件，不是音频');
    }

    return $ext;
}

/** 在音频目录里取一个不重名的文件名（重名自动 -2、-3…） */
function music_unique_name(string $origName): string
{
    $dir = music_dir_abs();
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('音频目录不可写：' . music_dir_rel());
    }
    $name = music_safe_filename($origName);
    $base = (string)pathinfo($name, PATHINFO_FILENAME);
    $extPart = (string)pathinfo($name, PATHINFO_EXTENSION);
    $try = 1;
    while (is_file($dir . '/' . $name)) {
        $try++;
        $name = $base . '-' . $try . ($extPart !== '' ? '.' . $extPart : '');
    }
    return $name;
}

/**
 * 校验并落盘一个上传的音频，返回磁盘上的文件名（不含目录）
 * 校验：上传状态、大小、扩展名白名单、真实 MIME、内容里不能出现 PHP 标签
 */
function music_store_upload(array $file): string
{
    $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) {
        $msg = [
            UPLOAD_ERR_INI_SIZE => '文件超过服务器 upload_max_filesize 限制',
            UPLOAD_ERR_FORM_SIZE => '文件超过表单允许的大小',
            UPLOAD_ERR_PARTIAL => '文件只传了一半，请重试',
            UPLOAD_ERR_NO_FILE => '没有选择文件',
            UPLOAD_ERR_NO_TMP_DIR => '服务器缺少临时目录',
            UPLOAD_ERR_CANT_WRITE => '服务器写入磁盘失败',
            UPLOAD_ERR_EXTENSION => '被 PHP 扩展拦下了',
        ][$err] ?? '上传失败（错误码 ' . $err . '）';
        throw new RuntimeException($msg);
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('这个文件不是正常上传上来的');
    }

    $size = (int)($file['size'] ?? 0);
    $orig = (string)($file['name'] ?? '');
    music_validate_audio_file($tmp, $orig, $size, music_upload_limit());

    $name = music_unique_name($orig);
    if (!@move_uploaded_file($tmp, music_dir_abs() . '/' . $name)) {
        throw new RuntimeException('保存文件失败，检查 ' . music_dir_rel() . ' 的写权限');
    }
    @chmod(music_dir_abs() . '/' . $name, 0644);

    return $name;
}

/**
 * 把一个已经落在音频目录里的文件登记进歌单
 * 标签优先级：表单填的 → 文件标签 → 文件名
 */
function music_register_file(string $name, string $title, string $artist): array
{
    $tags = audio_read_tags(music_dir_abs() . '/' . $name);
    $tagTitle = false;
    $tagArtist = false;

    $title = trim($title);
    if ($title === '' && $tags['title'] !== '') {
        $title = $tags['title'];
        $tagTitle = true;
    }
    if ($title === '') {
        $title = music_title_from_file($name);
    }

    $artist = trim($artist);
    if ($artist === '' && $tags['artist'] !== '') {
        $artist = $tags['artist'];
        $tagArtist = true;
    }

    $id = music_add($title, $artist, music_dir_rel() . '/' . $name);
    return [
        'id' => $id,
        'src' => music_dir_rel() . '/' . $name,
        'name' => $name,
        'title' => $title,
        'artist' => $artist,
        'tag_title' => $tagTitle,
        'tag_artist' => $tagArtist,
    ];
}

/**
 * 上传 + 直接登记进歌单。返回 [id, src, name, title, artist, tag_title, tag_artist]
 *
 * 歌名 / 演唱者的取值顺序：表单里填的 → 文件标签（ID3 / FLAC / OGG / M4A / WAV）→ 文件名
 */
function music_upload_and_add(array $file, string $title, string $artist): array
{
    $name = music_store_upload($file);
    return music_register_file($name, $title, $artist);
}

/** 上传成功后的提示文案（单次上传和分片上传共用） */
function music_upload_message(array $up, int $bytes): string
{
    $msg = '已上传并加入歌单：' . $up['title'];
    if ($up['artist'] !== '') {
        $msg .= ' — ' . $up['artist'];
    }
    if (!empty($up['tag_title']) && !empty($up['tag_artist'])) {
        $msg .= '（歌名/歌手读自文件标签）';
    } elseif (!empty($up['tag_title'])) {
        $msg .= '（歌名读自文件标签）';
    } elseif (!empty($up['tag_artist'])) {
        $msg .= '（歌手读自文件标签）';
    }
    if ($bytes > 0) {
        $msg .= ' · ' . music_size_label($bytes);
    }
    return $msg;
}

/* ---------- 分片上传 ---------- */

/** 分片临时目录：assets/music/.tmp（该目录自带 .htaccess，禁止直接访问） */
function music_chunk_root(): string
{
    return music_dir_abs() . '/.tmp';
}

/** 上传会话 id 只允许小写十六进制 */
function music_chunk_id_ok(string $id): bool
{
    return preg_match('/^[a-f0-9]{16,32}$/', $id) === 1;
}

/** 读会话元数据；不存在返回 null */
function music_chunk_meta(string $id): ?array
{
    if (!music_chunk_id_ok($id)) {
        return null;
    }
    $f = music_chunk_root() . '/' . $id . '/meta.json';
    if (!is_file($f)) {
        return null;
    }
    $d = json_decode((string)file_get_contents($f), true);
    return is_array($d) ? $d : null;
}

/** 递归删掉一个目录里的普通文件 */
function music_rmdir_recursive(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (glob($dir . '/*') ?: [] as $f) {
        if (is_file($f)) {
            @unlink($f);
        }
    }
    @rmdir($dir);
}

/** 清掉过期未完成的会话，避免临时目录越攒越大 */
function music_chunk_gc(): void
{
    $root = music_chunk_root();
    if (!is_dir($root)) {
        return;
    }
    foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $d) {
        $meta = $d . '/meta.json';
        $t = (int)@filemtime($meta);
        if ($t <= 0) {
            $t = (int)@filemtime($d);
        }
        if ($t > 0 && time() - $t > TQR_CHUNK_TTL) {
            music_rmdir_recursive($d);
        }
    }
}

/**
 * 单片上限：不超过配置的 10MB，也不超过主机单请求上限（留 64KB 给 multipart 头）
 */
function music_chunk_limit(): int
{
    return min(TQR_CHUNK_SIZE, max(262144, music_upload_limit() - 65536));
}

/**
 * 开一个上传会话。返回 [id, chunk_size]
 * $chunks 由客户端按自己的切片大小算出，服务端只做范围和总量校验
 */
function music_chunk_init(string $name, int $size, int $chunks): array
{
    music_chunk_gc();

    $name = music_client_filename($name);
    $ext = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
    if ($name === '' || !in_array($ext, TQR_AUDIO_EXT, true)) {
        throw new RuntimeException('只支持 ' . implode(' / ', TQR_AUDIO_EXT) . ' 格式');
    }
    if ($size <= 0) {
        throw new RuntimeException('文件是空的');
    }
    if ($size > TQR_CHUNK_MAX) {
        throw new RuntimeException('文件太大：上限 ' . music_size_label(TQR_CHUNK_MAX));
    }
    if ($chunks < 1 || $chunks > 4096) {
        throw new RuntimeException('分片数量不合法');
    }

    $root = music_chunk_root();
    if (!is_dir($root) && !@mkdir($root, 0775, true) && !is_dir($root)) {
        throw new RuntimeException('临时目录不可写：assets/music/.tmp');
    }

    $id = bin2hex(random_bytes(12));
    $dir = $root . '/' . $id;
    if (!@mkdir($dir, 0775, true)) {
        throw new RuntimeException('创建临时目录失败');
    }
    $meta = [
        'name' => $name,
        'size' => $size,
        'chunks' => $chunks,
        'created' => time(),
    ];
    if (@file_put_contents($dir . '/meta.json', json_encode($meta, JSON_UNESCAPED_UNICODE)) === false) {
        throw new RuntimeException('写入会话信息失败');
    }

    return [
        'id' => $id,
        'chunk_size' => (int)ceil($size / $chunks),
        'chunks' => $chunks,
    ];
}

/** 收一片。$file 为 $_FILES 里的一项 */
function music_chunk_save(string $id, int $index, array $file): int
{
    $meta = music_chunk_meta($id);
    if ($meta === null) {
        throw new RuntimeException('上传会话不存在或已过期');
    }
    $chunks = (int)$meta['chunks'];
    if ($index < 0 || $index >= $chunks) {
        throw new RuntimeException('分片序号超出范围');
    }
    $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) {
        throw new RuntimeException('分片上传失败（错误码 ' . $err . '）');
    }
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('分片不是正常上传上来的');
    }
    $size = (int)($file['size'] ?? 0);
    $max = music_chunk_limit();
    if ($size <= 0 || $size > $max) {
        throw new RuntimeException('分片大小不合法：' . music_size_label($size) . '，单片上限 ' . music_size_label($max));
    }

    $dir = music_chunk_root() . '/' . $id;
    $part = $dir . '/' . sprintf('p%05d.part', $index);
    if (!@move_uploaded_file($tmp, $part)) {
        throw new RuntimeException('保存分片失败，检查目录权限');
    }
    @chmod($part, 0644);
    @touch($dir . '/meta.json'); /* 刷新时间，避免合成前被 GC 清掉 */

    return $size;
}

/**
 * 按顺序把分片拼起来，返回 ['path' => 绝对路径, 'name' => 原文件名, 'size' => 实际字节]
 * 缺片、总大小对不上都直接报错
 */
function music_chunk_assemble(string $id): array
{
    $meta = music_chunk_meta($id);
    if ($meta === null) {
        throw new RuntimeException('上传会话不存在或已过期');
    }
    $dir = music_chunk_root() . '/' . $id;
    $chunks = (int)$meta['chunks'];
    $expect = (int)$meta['size'];

    $ext = strtolower((string)pathinfo((string)$meta['name'], PATHINFO_EXTENSION));
    $out = $dir . '/merged.' . ($ext !== '' ? $ext : 'bin');

    $fh = @fopen($out, 'wb');
    if (!$fh) {
        throw new RuntimeException('无法创建合并文件');
    }
    $total = 0;
    for ($i = 0; $i < $chunks; $i++) {
        $part = $dir . '/' . sprintf('p%05d.part', $i);
        if (!is_file($part)) {
            fclose($fh);
            throw new RuntimeException('缺少第 ' . ($i + 1) . ' 片，请重新上传');
        }
        $buf = @fopen($part, 'rb');
        if (!$buf) {
            fclose($fh);
            throw new RuntimeException('读取第 ' . ($i + 1) . ' 片失败');
        }
        while (!feof($buf)) {
            $chunk = fread($buf, 262144);
            if ($chunk === false) {
                break;
            }
            fwrite($fh, $chunk);
            $total += strlen($chunk);
        }
        fclose($buf);
    }
    fclose($fh);

    if ($total !== $expect) {
        throw new RuntimeException('合并后大小不对：' . $total . ' / ' . $expect . ' 字节');
    }
    return ['path' => $out, 'name' => (string)$meta['name'], 'size' => $total];
}

/**
 * 合成 + 校验 + 入库，返回同 music_register_file 的结构
 */
function music_upload_finish(string $id, string $title, string $artist): array
{
    $meta = music_chunk_meta($id);
    if ($meta === null) {
        throw new RuntimeException('上传会话不存在或已过期');
    }
    $merged = music_chunk_assemble($id);

    music_validate_audio_file($merged['path'], $merged['name'], $merged['size'], TQR_CHUNK_MAX);

    $name = music_unique_name($merged['name']);
    $dest = music_dir_abs() . '/' . $name;
    if (!@rename($merged['path'], $dest)) {
        throw new RuntimeException('把合并文件移入音频目录失败');
    }
    @chmod($dest, 0644);
    music_rmdir_recursive(music_chunk_root() . '/' . $id);

    return music_register_file($name, $title, $artist);
}

/** 主动放弃一个会话 */
function music_chunk_abort(string $id): void
{
    if (music_chunk_id_ok($id)) {
        music_rmdir_recursive(music_chunk_root() . '/' . $id);
    }
}
