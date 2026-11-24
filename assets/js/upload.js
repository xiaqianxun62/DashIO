/* TechQuickRef 分片上传
 *
 * 只在文件超过「单片上限」（表单上的 data-chunk-trigger / data-chunk-size，默认 10MB）时才接管：
 *   1) init   创建会话，拿到 id
 *   2) chunk  按 data-chunk-size（10MB）切片逐片上传（失败自动重试 2 次）
 *   3) finish 服务端按序拼回完整文件、校验、入库
 * 没超过一片的文件仍然走普通表单提交，行为不变。
 */
(function () {
  'use strict';

  var form = document.getElementById('musicUploadForm');
  if (!form) return;

  var fileInput = form.querySelector('input[name="audio"]');
  var titleInput = form.querySelector('input[name="title"]');
  var artistInput = form.querySelector('input[name="artist"]');
  var submitBtn = form.querySelector('button[type="submit"]');
  var statusBox = document.getElementById('upStatus');
  var bar = document.getElementById('upBar');
  var text = document.getElementById('upText');

  var chunkSize = parseInt(form.getAttribute('data-chunk-size') || '2097152', 10);
  var maxSize = parseInt(form.getAttribute('data-max-size') || '52428800', 10);
  var trigger = parseInt(form.getAttribute('data-chunk-trigger') || '10485760', 10);
  if (!isFinite(chunkSize) || chunkSize < 65536) chunkSize = 1048576;
  if (!isFinite(trigger) || trigger < chunkSize) trigger = chunkSize;

  var busy = false;

  function fmt(bytes) {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1).replace('.0', '') + ' MB';
    return Math.max(1, Math.round(bytes / 1024)) + ' KB';
  }

  function show() { if (statusBox) statusBox.hidden = false; }
  function setBar(pct) { if (bar) bar.style.width = Math.max(0, Math.min(100, pct)) + '%'; }
  function say(msg, isError) {
    if (!text) return;
    text.textContent = msg;
    text.className = 'hint' + (isError ? ' up-error' : '');
  }
  function lock(on) {
    busy = on;
    if (submitBtn) {
      submitBtn.disabled = on;
      submitBtn.classList.toggle('is-busy', on);
    }
    if (fileInput) fileInput.disabled = on;
    if (titleInput) titleInput.disabled = on;
    if (artistInput) artistInput.disabled = on;
  }

  function request(action, fields) {
    var body = new FormData();
    Object.keys(fields).forEach(function (k) { body.append(k, fields[k]); });
    return fetch('upload.php?action=' + encodeURIComponent(action), {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { 'X-CSRF-Token': window.CSRF || '' }
    }).then(function (res) {
      return res.text().then(function (t) {
        var data;
        try { data = JSON.parse(t); } catch (e) {
          throw new Error('服务器返回异常：' + t.replace(/\s+/g, ' ').slice(0, 80));
        }
        if (!data.ok) throw new Error(data.error || '上传失败');
        return data;
      });
    });
  }

  function sendChunk(id, index, blob, fileName, retry) {
    retry = retry || 0;
    var body = new FormData();
    body.append('id', id);
    body.append('index', String(index));
    body.append('blob', blob, fileName);
    return fetch('upload.php?action=chunk', {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { 'X-CSRF-Token': window.CSRF || '' }
    }).then(function (res) {
      return res.text().then(function (t) {
        var data;
        try { data = JSON.parse(t); } catch (e) { data = { ok: false, error: '分片 ' + (index + 1) + ' 响应异常' }; }
        if (!data.ok) throw new Error(data.error || ('分片 ' + (index + 1) + ' 上传失败'));
        return data;
      });
    }).catch(function (err) {
      if (retry >= 2) throw err;
      return new Promise(function (r) { setTimeout(r, 400); })
        .then(function () { return sendChunk(id, index, blob, fileName, retry + 1); });
    });
  }

  function uploadBig(file) {
    var chunks = Math.ceil(file.size / chunkSize);
    var id = null;
    show();
    setBar(0);
    say('开始上传：' + file.name + '（' + fmt(file.size) + '，共 ' + chunks + ' 片）');
    lock(true);

    return request('init', { name: file.name, size: file.size, chunks: chunks })
      .then(function (init) {
        id = init.id;
        var chain = Promise.resolve();
        for (var i = 0; i < chunks; i++) {
          (function (index) {
            chain = chain.then(function () {
              var start = index * chunkSize;
              var blob = file.slice(start, Math.min(file.size, start + chunkSize));
              var done = Math.min(file.size, start + blob.size);
              var pct = Math.round((done / file.size) * 100);
              var pctDone = Math.round((start / file.size) * 100);
              say('已传 ' + pctDone + '% · 正在上传第 ' + (index + 1) + ' / ' + chunks + ' 片…');
              return sendChunk(id, index, blob, file.name).then(function () {
                setBar(pct);
                say('已传 ' + fmt(done) + ' / ' + fmt(file.size) + '（' + pct + '%）· 第 ' + (index + 1) + ' / ' + chunks + ' 片');
              });
            });
          })(i);
        }
        return chain.then(function () {
          say('分片传完了，正在合并校验…');
          return request('finish', {
            id: id,
            title: titleInput ? titleInput.value : '',
            artist: artistInput ? artistInput.value : ''
          });
        });
      })
      .then(function (fin) {
        say('已完成：' + (fin.title || '') + (fin.artist ? ' — ' + fin.artist : ''));
        setBar(100);
        window.location.href = fin.url || 'music.php';
      })
      .catch(function (err) {
        if (id) {
          request('abort', { id: id }).catch(function () {});
        }
        say('上传失败：' + err.message, true);
        lock(false);
      });
  }

  form.addEventListener('submit', function (e) {
    if (busy) { e.preventDefault(); return; }
    var file = fileInput && fileInput.files && fileInput.files[0];
    if (!file) return; /* 交给浏览器校验「请选择文件」 */
    if (file.size <= trigger) return; /* 没到阈值，走普通表单整包提交 */
    e.preventDefault();

    if (file.size > maxSize) {
      show();
      say('文件太大：' + fmt(file.size) + '，上限 ' + fmt(maxSize), true);
      return;
    }
    uploadBig(file);
  });

  window.addEventListener('beforeunload', function (e) {
    if (!busy) return;
    e.preventDefault();
    e.returnValue = '';
  });
})();
