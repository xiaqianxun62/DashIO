<?php
declare(strict_types=1);

/**
 * 音乐页：歌单 + 播放
 *
 * 歌单来源：
 *   - assets/music 下扫到的本地音频（文件即曲目，未登记的会显示为「未入库」）
 *   - 数据库 tracks 表里登记的条目（外链 URL 或本地文件）
 *
 * 管理员可以在这里上传音频、加外链、把本地文件批量登记、改名/排序/删除。
 * 播放器本身在页面底部（lib/helpers.php 的 page_footer 输出），全站共用。
 */

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';

$isAdmin = is_admin();
$error = '';
$editId = (int)($_GET['edit'] ?? 0);
$editTrack = null;

/* ---------- 写操作（仅管理员） ---------- */
$reqLen = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
/* 上传超过 post_max_size 时 PHP 直接把整个请求体丢掉，$_POST / $_FILES 都是空。
 * 这个判断放在权限之外：超限说明没写进任何东西，提示给谁看都无害 */
$bodyDropped = ($_SERVER['REQUEST_METHOD'] === 'POST' && $reqLen > 0 && $_POST === [] && $_FILES === []);
if ($bodyDropped) {
    $error = '上传失败：请求超过了主机 post_max_size（' . music_size_label(music_ini_bytes((string)ini_get('post_max_size'))) . '），换个小一点的文件再试';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    $action = (string)($_POST['action'] ?? '');
    try {
        csrf_check_post();

        if ($action === 'add') {
            $title = (string)($_POST['title'] ?? '');
            $artist = (string)($_POST['artist'] ?? '');
            $src = (string)($_POST['src'] ?? '');
            music_add($title, $artist, $src);
            flash_set('success', '已加入歌单：' . trim($title));
        } elseif ($action === 'upload') {
            $up = music_upload_and_add(
                is_array($_FILES['audio'] ?? null) ? $_FILES['audio'] : [],
                (string)($_POST['title'] ?? ''),
                (string)($_POST['artist'] ?? '')
            );
            flash_set('success', music_upload_message($up, (int)($_FILES['audio']['size'] ?? 0)));
        } elseif ($action === 'import_local') {
            $n = music_import_local();
            flash_set('success', $n > 0 ? '已登记 ' . $n . ' 首本地文件' : '没有需要登记的新文件');
        } elseif ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            music_update($id, (string)($_POST['title'] ?? ''), (string)($_POST['artist'] ?? ''), (int)($_POST['sort'] ?? 0));
            flash_set('success', '已保存');
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            music_delete($id);
            flash_set('success', '已从歌单移除（磁盘文件没删）');
        }
    } catch (Throwable $ex) {
        $error = $ex->getMessage();
    }
    if ($error === '') {
        redirect('music.php');
    }
}

/* ---------- 读 ---------- */
$tracks = music_tracks();
$localAll = music_local_files();
$uploadLimit = music_upload_limit();
$acceptExt = implode(',', array_map(static fn($e) => '.' . $e, TQR_AUDIO_EXT));
/* 分片：每片 10MB（受主机单请求上限约束），超过一片的大小才切；总大小 1G */
$chunkSize = max(262144, min(TQR_CHUNK_SIZE, music_chunk_limit()));
$chunkTrigger = $chunkSize;

/* 未入库的本地文件数量：只对比库里的记录，不能拿合并后的歌单比（虚拟条目一直都在） */
$localPending = 0;
try {
    $registered = [];
    foreach (q_all('SELECT src FROM tracks') as $r) {
        $registered[(string)$r['src']] = true;
    }
    foreach ($localAll as $rel => $title) {
        if (!isset($registered[$rel])) {
            $localPending++;
        }
    }
} catch (Throwable $ex) {
    $localPending = count($localAll);
}
if ($isAdmin && $editId > 0) {
    $editTrack = q_row('SELECT * FROM tracks WHERE id = ?', [$editId]);
    if (!$editTrack) {
        $editId = 0;
    }
}

page_header(['title' => '音乐']);
?>
<section class="hero" style="padding:10px 0 4px">
  <p class="hint" style="margin-bottom:6px"><?= icon('arrow-left') ?><a href="<?= u('index.php') ?>">回到首页</a><?php if ($isAdmin): ?> <span class="crumb-sep">/</span> <a href="<?= u('admin.php') ?>">管理后台</a><?php endif; ?></p>
  <h1 class="hero-title"><?= icon('music', 'icon-lg') ?>音乐</h1>
  <p class="hero-sub">歌单里的曲目会在全站底部播放器里播放，切页面会记住进度（浏览器允许自动播放时会接着播）。</p>
</section>

<?= flash_render() ?>
<?php if ($error !== ''): ?>
<div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<section class="cat-block">
  <h2 class="cat-title">上传音频</h2>
  <p class="hint">传进 <code>assets/music/</code> 并直接加入歌单。歌名、歌手留空会先读文件标签（mp3 的 ID3、flac / ogg 的 VORBIS、m4a 的 ilst、wav 的 INFO），没有标签再用文件名。支持 <?= e(implode(' / ', TQR_AUDIO_EXT)) ?>。超过 <b><?= e(music_size_label($chunkTrigger)) ?></b> 的文件自动分片（每片 <?= e(music_size_label($chunkSize)) ?>，合并后总上限 <?= e(music_size_label(TQR_CHUNK_MAX)) ?>），更小的直接整包上传；合并完和普通文件一样播放。</p>

  <form method="post" enctype="multipart/form-data" class="music-form music-upload-form" id="musicUploadForm"
        data-chunk-size="<?= (int)$chunkSize ?>" data-chunk-trigger="<?= (int)$chunkTrigger ?>" data-max-size="<?= (int)TQR_CHUNK_MAX ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="upload">
    <label class="field field-file"><span>音频文件</span><input type="file" name="audio" accept="<?= e($acceptExt) ?>,audio/*" required></label>
    <label class="field"><span>歌名（留空自动识别）</span><input type="text" name="title" maxlength="200" placeholder="留空读标签，再退到文件名"></label>
    <label class="field"><span>歌手（留空自动识别）</span><input type="text" name="artist" maxlength="120" placeholder="留空读标签"></label>
    <button class="btn btn-primary" type="submit"><?= icon('upload') ?>上传</button>
  </form>
  <div class="up-status" id="upStatus" hidden>
    <div class="up-bar"><span id="upBar"></span></div>
    <p class="hint" id="upText">准备上传…</p>
  </div>
</section>

<section class="cat-block">
  <h2 class="cat-title">添加曲目</h2>
  <p class="hint">不传文件，直接登记已有音源：外链粘 http(s) 地址，本地填 <code>assets/music/文件名</code>。目录里扫到但没登记的文件，可以用下面的按钮批量入库。</p>

  <form method="post" class="music-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <label class="field"><span>歌名</span><input type="text" name="title" maxlength="200" required placeholder="如：晴天"></label>
    <label class="field"><span>演唱者（可选）</span><input type="text" name="artist" maxlength="120" placeholder="周杰伦"></label>
    <label class="field"><span>音源（外链 URL 或 assets/music/文件名）</span><input type="text" name="src" required placeholder="https://…/song.mp3 或 assets/music/song.mp3"></label>
    <button class="btn btn-primary" type="submit"><?= icon('plus') ?>添加</button>
  </form>

  <?php if ($localPending > 0): ?>
  <form method="post" class="music-import-row">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="import_local">
    <span class="hint" style="margin:0">目录里还有 <b><?= (int)$localPending ?></b> 个音频文件没登记，登记后才能改名、排序、加演唱者。</span>
    <button class="btn btn-ghost btn-sm" type="submit"><?= icon('database') ?>把本地文件登记进歌单</button>
  </form>
  <?php endif; ?>

  <?php if ($editTrack): ?>
  <h2 class="cat-title" style="margin-top:22px">编辑曲目 <em><?= e((string)$editTrack['title']) ?></em></h2>
  <form method="post" class="music-form music-edit-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int)$editTrack['id'] ?>">
    <label class="field"><span>歌名</span><input type="text" name="title" maxlength="200" required value="<?= e((string)$editTrack['title']) ?>"></label>
    <label class="field"><span>演唱者</span><input type="text" name="artist" maxlength="120" value="<?= e((string)$editTrack['artist']) ?>"></label>
    <label class="field"><span>排序（小的在前）</span><input type="number" name="sort" value="<?= (int)$editTrack['sort'] ?>"></label>
    <button class="btn btn-primary" type="submit">保存</button>
    <a class="btn btn-ghost" href="<?= u('music.php') ?>">取消</a>
  </form>
  <p class="hint">音源：<code><?= e((string)$editTrack['src']) ?></code>（改音源请删掉后重新添加）</p>
  <?php endif; ?>
</section>
<?php endif; ?>

<section class="cat-block">
  <h2 class="cat-title">歌单 <em><?= count($tracks) ?> 首</em></h2>

  <?php if ($tracks === []): ?>
  <div class="empty-tip">
    <h2><?= icon('music') ?>还没有歌曲</h2>
    <p class="hint"><?= $isAdmin ? '用上面的上传框传一首，或粘外链、填目录里已有的文件名。' : '管理员可以在后台添加或上传。' ?></p>
  </div>
  <?php else: ?>
  <div class="track-list">
    <?php foreach ($tracks as $i => $t): ?>
    <div class="track-row" data-track-index="<?= (int)$i ?>">
      <span class="track-no"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
      <button type="button" class="track-play" data-play="<?= (int)$i ?>">
        <span class="track-title"><?= e((string)$t['title']) ?></span>
        <?php if ((string)$t['artist'] !== ''): ?><span class="track-artist"><?= e((string)$t['artist']) ?></span><?php endif; ?>
      </button>
      <span class="track-badge"><?= $t['local'] ? '本地' : '外链' ?><?= ($isAdmin && (int)$t['id'] === 0) ? ' · 未入库' : '' ?></span>
      <?php if ($isAdmin && (int)$t['id'] > 0): ?>
      <span class="track-tools">
        <a class="icon-btn" href="<?= u('music.php') ?>?edit=<?= (int)$t['id'] ?>" title="编辑"><?= icon('pencil') ?></a>
        <form method="post" onsubmit="return confirm('从歌单移除这首？磁盘文件不会删。')">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
          <button class="icon-btn" type="submit" title="移除"><?= icon('trash-2') ?></button>
        </form>
      </span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<?php
$uploadJs = $isAdmin
    ? '<script src="' . u('assets/js/upload.js') . '?v=' . (int)@filemtime(__DIR__ . '/assets/js/upload.js') . '"></script>'
    : '';
page_footer($uploadJs);
