<?php
declare(strict_types=1);

/**
 * 音频标签读取（零依赖、纯 PHP）
 *
 * 只做一件事：从文件里找出「歌名」和「歌手」，找不到就返回空字符串。
 * 覆盖上传目录里允许的格式：
 *   mp3 → ID3v2（TIT2 / TPE1）与 ID3v1
 *   flac → VORBIS_COMMENT（TITLE / ARTIST）
 *   ogg / oga → vorbis / opus 的 comment header
 *   m4a / mp4 / aac(容器) → ilst 里的 ©nam / ©ART
 *   wav → RIFF 的 LIST/INFO 里的 INAM / IART
 *   webm → 不做解析（标签不常见），回退到文件名
 *
 * 读文件的方式：先读头部 1MB，mp4 找不到再读尾部 1MB（moov 常在文件末尾）；
 * 都读不到就老实返回空，调用方用文件名兜底。
 */

const TQR_TAG_HEAD = 1048576;   // 头部读取上限 1MB
const TQR_TAG_TAIL = 1048576;   // 尾部读取上限 1MB

/** 统一清洗标签文本：去空字节、折空白、限长 */
function tag_clean(string $s, int $max = 200): string
{
    $s = str_replace(["\0", "\r", "\n", "\t"], ' ', $s);
    $s = trim((string)preg_replace('/\s{2,}/u', ' ', $s));
    if ($s === '') {
        return '';
    }
    return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
}

/** Latin-1 / UTF-16 / UTF-8 → UTF-8 的宽松转换 */
function tag_to_utf8(string $s): string
{
    if ($s === '') {
        return '';
    }
    /* 已经是合法 UTF-8 就不用转 */
    if (preg_match('//u', $s) === 1) {
        return $s;
    }
    if (function_exists('mb_convert_encoding')) {
        return (string)mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
    }
    return $s;
}

/** 4 字节 syncsafe 整数（ID3v2.4 帧长、标签总长） */
function tag_syncsafe(string $b): int
{
    if (strlen($b) < 4) {
        return 0;
    }
    return ((ord($b[0]) & 0x7F) << 21) | ((ord($b[1]) & 0x7F) << 14) | ((ord($b[2]) & 0x7F) << 7) | (ord($b[3]) & 0x7F);
}

/** 读文件的一段；$len 为负数时表示“从末尾倒数” */
function tag_read(string $path, int $offset, int $len): string
{
    $size = @filesize($path);
    if ($size === false || $size <= 0) {
        return '';
    }
    if ($offset < 0) {
        $offset = max(0, $size + $offset);
    }
    if ($offset >= $size) {
        return '';
    }
    $len = max(0, min($len, $size - $offset));
    if ($len === 0) {
        return '';
    }
    $fh = @fopen($path, 'rb');
    if (!$fh) {
        return '';
    }
    fseek($fh, $offset);
    $data = (string)fread($fh, $len);
    fclose($fh);
    return $data;
}

/* ============ ID3（mp3） ============ */

/** UTF-16 码元串 → UTF-8（按字节序拼成 UTF-16BE 再交给 mbstring） */
function tag_utf16_to_utf8(string $raw, bool $littleEndian): string
{
    $be = '';
    for ($i = 0; $i + 1 < strlen($raw); $i += 2) {
        $a = ord($raw[$i]);
        $b = ord($raw[$i + 1]);
        $code = $littleEndian ? ($a | ($b << 8)) : (($a << 8) | $b);
        if ($code === 0) {
            break;
        }
        $be .= chr(($code >> 8) & 0xFF) . chr($code & 0xFF);
    }
    if ($be === '' || !function_exists('mb_convert_encoding')) {
        return '';
    }
    return (string)mb_convert_encoding($be, 'UTF-8', 'UTF-16BE');
}

/** ID3v2 的一个文本帧 → UTF-8 文本 */
function tag_id3_text_frame(string $frame): string
{
    if ($frame === '') {
        return '';
    }
    $enc = ord($frame[0]);
    $raw = substr($frame, 1);

    if ($enc === 1) {
        /* UTF-16 + BOM */
        if (substr($raw, 0, 2) === "\xFF\xFE") {
            return tag_utf16_to_utf8(substr($raw, 2), true);
        }
        if (substr($raw, 0, 2) === "\xFE\xFF") {
            return tag_utf16_to_utf8(substr($raw, 2), false);
        }
        return tag_utf16_to_utf8($raw, false);
    }
    if ($enc === 2) {
        /* UTF-16BE，无 BOM */
        return tag_utf16_to_utf8($raw, false);
    }

    $raw = str_replace("\0", '', $raw);
    if ($enc === 3) {
        return $raw; /* UTF-8 */
    }
    return tag_to_utf8($raw); /* 0 = ISO-8859-1 */
}

/** 解析 ID3v2 标签（支持 2.3 / 2.4，2.2 放弃，太老） */
function tag_parse_id3v2(string $data): array
{
    $out = ['title' => '', 'artist' => ''];
    if (strlen($data) < 10 || substr($data, 0, 3) !== 'ID3') {
        return $out;
    }
    $ver = ord($data[3]);
    if ($ver < 3) {
        return $out;
    }
    $flags = ord($data[5]);
    $size = tag_syncsafe(substr($data, 6, 4));
    $body = substr($data, 10, $size > 0 ? $size : strlen($data) - 10);

    /* 扩展头（2.3/2.4 都有）先跳过 */
    if (($flags & 0x40) === 0x40 && strlen($body) >= 4) {
        $ext = ord($body[0]) === 0 && $ver === 3 ? 0 : tag_syncsafe(substr($body, 0, 4));
        $skip = $ver === 3 ? 4 + $ext : $ext;
        $body = substr($body, $skip);
    }

    $pos = 0;
    $len = strlen($body);
    while ($pos + 10 <= $len) {
        $fid = substr($body, $pos, 4);
        if (preg_match('/^[A-Z0-9]{4}$/', $fid) !== 1) {
            break;
        }
        if ($ver === 4) {
            $fsize = tag_syncsafe(substr($body, $pos + 4, 4));
        } else {
            $fsize = (int)unpack('N', substr($body, $pos + 4, 4))[1];
        }
        if ($fsize <= 0 || $pos + 10 + $fsize > $len) {
            break;
        }
        $frame = substr($body, $pos + 10, $fsize);
        if ($fid === 'TIT2' && $out['title'] === '') {
            $out['title'] = tag_clean(tag_id3_text_frame($frame), 200);
        } elseif ($fid === 'TPE1' && $out['artist'] === '') {
            $out['artist'] = tag_clean(tag_id3_text_frame($frame), 120);
        }
        $pos += 10 + $fsize;
        if ($out['title'] !== '' && $out['artist'] !== '') {
            break;
        }
    }
    return $out;
}

/** 解析 ID3v1（文件最后 128 字节） */
function tag_parse_id3v1(string $tail): array
{
    $out = ['title' => '', 'artist' => ''];
    if (strlen($tail) < 128) {
        return $out;
    }
    $last = substr($tail, -128);
    if (substr($last, 0, 3) !== 'TAG') {
        return $out;
    }
    $out['title'] = tag_clean(tag_to_utf8(substr($last, 3, 30)), 200);
    $out['artist'] = tag_clean(tag_to_utf8(substr($last, 33, 30)), 120);
    return $out;
}

/* ============ Vorbis / FLAC / OGG ============ */

/** "KEY=VAL" 形式的注释列表 → title / artist */
function tag_from_comments(array $comments): array
{
    $out = ['title' => '', 'artist' => ''];
    foreach ($comments as $c) {
        $p = strpos($c, '=');
        if ($p === false) {
            continue;
        }
        $k = strtoupper(substr($c, 0, $p));
        $v = tag_clean(tag_to_utf8(substr($c, $p + 1)), 200);
        if ($v === '') {
            continue;
        }
        if ($k === 'TITLE' && $out['title'] === '') {
            $out['title'] = $v;
        } elseif ($k === 'ARTIST' && $out['artist'] === '') {
            $out['artist'] = tag_clean($v, 120);
        }
    }
    return $out;
}

/** 解析 vorbis / opus 的 comment 结构（vendor + 若干 KEY=VAL） */
function tag_parse_vorbis_comment(string $data, int $start): array
{
    $out = ['title' => '', 'artist' => ''];
    $len = strlen($data);
    if ($start < 0 || $start + 8 > $len) {
        return $out;
    }
    $vlen = (int)unpack('V', substr($data, $start, 4))[1];
    $pos = $start + 4 + $vlen;
    if ($pos + 4 > $len) {
        return $out;
    }
    $count = (int)unpack('V', substr($data, $pos, 4))[1];
    $pos += 4;
    $count = min($count, 64);
    $comments = [];
    for ($i = 0; $i < $count; $i++) {
        if ($pos + 4 > $len) {
            break;
        }
        $clen = (int)unpack('V', substr($data, $pos, 4))[1];
        $pos += 4;
        if ($clen <= 0 || $pos + $clen > $len) {
            break;
        }
        $comments[] = substr($data, $pos, $clen);
        $pos += $clen;
    }
    return tag_from_comments($comments);
}

/** FLAC：fLaC + 若干元数据块，取 type=4（VORBIS_COMMENT） */
function tag_parse_flac(string $data): array
{
    $out = ['title' => '', 'artist' => ''];
    if (substr($data, 0, 4) !== 'fLaC') {
        return $out;
    }
    $pos = 4;
    $len = strlen($data);
    while ($pos + 4 <= $len) {
        $type = ord($data[$pos]) & 0x7F;
        $last = (ord($data[$pos]) & 0x80) === 0x80;
        $blen = (ord($data[$pos + 1]) << 16) | (ord($data[$pos + 2]) << 8) | ord($data[$pos + 3]);
        $pos += 4;
        if ($blen < 0 || $pos + $blen > $len) {
            break;
        }
        if ($type === 4) {
            $out = tag_parse_vorbis_comment(substr($data, $pos, $blen), 0);
            if ($out['title'] !== '' || $out['artist'] !== '') {
                return $out;
            }
        }
        $pos += $blen;
        if ($last) {
            break;
        }
    }
    return $out;
}

/** OGG：在头部找 vorbis/opus 的 comment header */
function tag_parse_ogg(string $data): array
{
    $vp = strpos($data, "\x03vorbis");
    if ($vp !== false) {
        return tag_parse_vorbis_comment($data, $vp + 7);
    }
    $op = strpos($data, 'OpusTags');
    if ($op !== false) {
        return tag_parse_vorbis_comment($data, $op + 8);
    }
    return ['title' => '', 'artist' => ''];
}

/* ============ MP4 / M4A ============ */

/** 在 buffer 里按盒子结构找 ilst 下的 ©nam / ©ART */
function tag_parse_mp4(string $data): array
{
    $out = ['title' => '', 'artist' => ''];

    /* ilst 条目：4 字节长度 + 名称（©nam / ©ART）+ data 盒子；
     * data 盒子 = 4 字节长度 + 'data' + 4 字节类型 + 4 字节语言 + 正文 */
    $needles = ["\xA9nam" => 'title', "\xA9ART" => 'artist'];
    foreach ($needles as $needle => $field) {
        $p = strpos($data, $needle);
        if ($p === false || $p < 4) {
            continue;
        }
        $itemSize = (int)unpack('N', substr($data, $p - 4, 4))[1];
        if ($itemSize <= 8) {
            continue;
        }
        $d = strpos($data, 'data', $p);
        if ($d === false || $d - $p > 24 || $d < 4) {
            continue;
        }
        $dataSize = (int)unpack('N', substr($data, $d - 4, 4))[1];
        $textStart = $d + 4 + 8; /* 跳过 'data' + 类型 + 语言 */
        $textLen = $dataSize - 16;
        if ($textLen <= 0 || $textStart + $textLen > strlen($data)) {
            continue;
        }
        $val = tag_clean(tag_to_utf8(substr($data, $textStart, $textLen)), $field === 'artist' ? 120 : 200);
        if ($val !== '' && $out[$field] === '') {
            $out[$field] = $val;
        }
    }
    return $out;
}

/* ============ WAV ============ */

/** WAV：RIFF 里的 LIST/INFO → INAM（标题）/ IART（作者） */
function tag_parse_wav(string $data): array
{
    $out = ['title' => '', 'artist' => ''];
    $len = strlen($data);
    $pos = 12; /* 跳过 'RIFF' + size + 'WAVE' */
    while ($pos + 8 <= $len) {
        $id = substr($data, $pos, 4);
        $size = (int)unpack('V', substr($data, $pos + 4, 4))[1];
        $pos += 8;
        if ($size < 0 || $pos + $size > $len) {
            if ($size > $len) {
                break;
            }
        }
        if ($id === 'LIST' && $size >= 4) {
            $form = substr($data, $pos, 4);
            if ($form === 'INFO') {
                $p = $pos + 4;
                $endp = min($len, $pos + $size);
                while ($p + 8 <= $endp) {
                    $cid = substr($data, $p, 4);
                    $csize = (int)unpack('V', substr($data, $p + 4, 4))[1];
                    $p += 8;
                    if ($csize < 0 || $p + $csize > $endp) {
                        break;
                    }
                    $val = tag_clean(tag_to_utf8(substr($data, $p, $csize)), 200);
                    if ($cid === 'INAM' && $out['title'] === '') {
                        $out['title'] = $val;
                    } elseif ($cid === 'IART' && $out['artist'] === '') {
                        $out['artist'] = tag_clean($val, 120);
                    }
                    $p += $csize + ($csize % 2); /* 块按 2 字节对齐 */
                }
            }
        }
        $pos += $size + ($size % 2);
    }

    /* 兜底：有些工具写 INFO 块时不补对齐字节，按块走会错位；
     * 这里直接扫 'INAM' / 'IART' 标记，缺哪个补哪个 */
    foreach (['INAM' => 'title', 'IART' => 'artist'] as $cid => $field) {
        if ($out[$field] !== '') {
            continue;
        }
        $p = strpos($data, $cid);
        if ($p === false || $p + 8 > $len) {
            continue;
        }
        $csize = (int)unpack('V', substr($data, $p + 4, 4))[1];
        if ($csize <= 0 || $csize > 512 || $p + 8 + $csize > $len) {
            continue;
        }
        $out[$field] = tag_clean(tag_to_utf8(substr($data, $p + 8, $csize)), $field === 'artist' ? 120 : 200);
    }
    return $out;
}

/* ============ 对外入口 ============ */

/**
 * 读音频标签，返回 ['title' => '', 'artist' => '']（找不到就是空字符串）
 */
function audio_read_tags(string $path): array
{
    $empty = ['title' => '', 'artist' => ''];
    if (!is_file($path) || !is_readable($path)) {
        return $empty;
    }
    $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, TQR_AUDIO_EXT, true)) {
        return $empty;
    }

    $head = tag_read($path, 0, TQR_TAG_HEAD);
    if ($head === '') {
        return $empty;
    }

    $out = $empty;
    switch ($ext) {
        case 'mp3':
            $out = tag_parse_id3v2($head);
            if ($out['title'] === '' && $out['artist'] === '') {
                $out = tag_parse_id3v1(tag_read($path, -128, 128));
            }
            break;
        case 'flac':
            $out = tag_parse_flac($head);
            break;
        case 'ogg':
        case 'oga':
            $out = tag_parse_ogg($head);
            break;
        case 'm4a':
        case 'aac':
            $out = tag_parse_mp4($head);
            if ($out['title'] === '' && $out['artist'] === '') {
                $out = tag_parse_mp4(tag_read($path, -TQR_TAG_TAIL, TQR_TAG_TAIL));
            }
            break;
        case 'wav':
            $out = tag_parse_wav($head);
            break;
        case 'webm':
        default:
            $out = $empty;
            break;
    }

    return [
        'title' => tag_clean($out['title'], 200),
        'artist' => tag_clean($out['artist'], 120),
    ];
}
