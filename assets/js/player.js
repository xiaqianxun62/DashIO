/* TechQuickRef 音乐播放器
 *
 * 一个页面只有一个 audio 实例；所有控件（迷你条 / 歌单行）都走同一份状态。
 * 跨页面续播：播放位置与曲目写进 sessionStorage，新页面加载后恢复进度，
 * 若浏览器拦截自动播放（没有用户手势），会在用户第一次点击/按键时接着播。
 */
(function () {
  'use strict';

  var dataEl = document.getElementById('tqrTracks');
  if (!dataEl) return;

  var tracks = [];
  try { tracks = JSON.parse(dataEl.textContent || '[]'); } catch (e) { tracks = []; }
  if (!tracks.length) return;

  var KEY = 'tqr-player';
  var audio = new Audio();
  /* 只有真正要播时才拉音频：默认不预载，避免一进页面就发媒体请求 */
  audio.preload = 'none';

  var idx = 0;
  var pendingPlay = false;
  var pendingSeek = 0;
  var loadedSrc = '';
  var lastSaved = 0;

  var el = {
    toggle: document.getElementById('plToggle'),
    prev: document.getElementById('plPrev'),
    next: document.getElementById('plNext'),
    title: document.getElementById('plTitle'),
    artist: document.getElementById('plArtist'),
    cur: document.getElementById('plCur'),
    dur: document.getElementById('plDur'),
    seek: document.getElementById('plSeek'),
    vol: document.getElementById('plVol'),
    mute: document.getElementById('plMute')
  };

  var icPlay = document.querySelector('.pl-icon-play');
  var icPause = document.querySelector('.pl-icon-pause');
  var icVol = document.querySelector('.pl-icon-vol');
  var icMute = document.querySelector('.pl-icon-mute');

  /* ---------- 状态存取 ---------- */

  function readState() {
    try {
      var raw = sessionStorage.getItem(KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  function saveState() {
    try {
      sessionStorage.setItem(KEY, JSON.stringify({
        i: idx,
        t: audio.currentTime || 0,
        p: audio.paused ? 0 : 1,
        v: audio.volume,
        m: audio.muted ? 1 : 0
      }));
    } catch (e) {}
  }

  /* ---------- 展示 ---------- */

  function fmt(sec) {
    if (!isFinite(sec) || sec < 0) sec = 0;
    var s = Math.round(sec);
    return Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2);
  }

  function paintButton() {
    var playing = !audio.paused;
    if (icPlay) icPlay.hidden = playing;
    if (icPause) icPause.hidden = !playing;
    if (el.toggle) {
      el.toggle.setAttribute('title', playing ? '暂停' : '播放');
      el.toggle.setAttribute('aria-label', playing ? '暂停' : '播放');
    }
  }

  function paintTrack() {
    var t = tracks[idx];
    if (!t) return;
    if (el.title) el.title.textContent = t.t || '未命名曲目';
    if (el.artist) el.artist.textContent = t.a || ' ';
    document.querySelectorAll('[data-track-index]').forEach(function (row) {
      row.classList.toggle('active', Number(row.getAttribute('data-track-index')) === idx);
    });
  }

  /* ---------- 播放控制 ---------- */

  function attachSrc(i) {
    var src = tracks[i] ? tracks[i].s : '';
    if (!src || src === loadedSrc) return;
    loadedSrc = src;
    audio.src = src;
    if (pendingSeek > 0) {
      var to = pendingSeek;
      pendingSeek = 0;
      var once = function () {
        try { audio.currentTime = to; } catch (e) {}
        audio.removeEventListener('loadedmetadata', once);
      };
      audio.addEventListener('loadedmetadata', once);
    }
  }

  function loadTrack(i, autoplay, seekTo) {
    if (i < 0 || i >= tracks.length) return;
    idx = i;
    pendingSeek = seekTo && seekTo > 0 ? seekTo : 0;
    paintTrack();
    if (autoplay) {
      attachSrc(i);
      tryPlay();
    }
    saveState();
  }

  function toggle() {
    if (audio.paused) {
      pendingPlay = false;
      attachSrc(idx);
      tryPlay();
    } else {
      audio.pause();
    }
  }

  function tryPlay() {
    var p = audio.play();
    if (p && typeof p.catch === 'function') {
      p.catch(function () {
        pendingPlay = true;
        paintButton();
      });
    }
  }

  function step(delta) {
    var next = idx + delta;
    if (next < 0) next = tracks.length - 1;
    if (next >= tracks.length) next = 0;
    pendingPlay = false;
    loadTrack(next, true);
  }

  /* ---------- 事件 ---------- */

  if (el.toggle) el.toggle.addEventListener('click', toggle);
  if (el.prev) el.prev.addEventListener('click', function () { step(-1); });
  if (el.next) el.next.addEventListener('click', function () { step(1); });

  audio.addEventListener('play', paintButton);
  audio.addEventListener('pause', function () { paintButton(); saveState(); });
  audio.addEventListener('ended', function () { step(1); });

  audio.addEventListener('loadedmetadata', function () {
    if (el.dur) el.dur.textContent = fmt(audio.duration);
  });

  audio.addEventListener('timeupdate', function () {
    if (el.cur) el.cur.textContent = fmt(audio.currentTime);
    if (el.seek && audio.duration && isFinite(audio.duration)) {
      el.seek.value = String(Math.round((audio.currentTime / audio.duration) * 1000));
    }
    var now = Date.now();
    if (now - lastSaved > 1000) { lastSaved = now; saveState(); }
  });

  if (el.seek) {
    el.seek.addEventListener('input', function () {
      if (!audio.duration || !isFinite(audio.duration)) return;
      audio.currentTime = (Number(el.seek.value) / 1000) * audio.duration;
    });
  }

  if (el.vol) {
    el.vol.addEventListener('input', function () {
      audio.muted = false;
      audio.volume = Number(el.vol.value) / 100;
      paintMute();
      saveState();
    });
  }

  function paintMute() {
    var muted = audio.muted || audio.volume === 0;
    if (icVol) icVol.hidden = muted;
    if (icMute) icMute.hidden = !muted;
  }

  if (el.mute) {
    el.mute.addEventListener('click', function () {
      audio.muted = !audio.muted;
      if (!audio.muted && audio.volume === 0) {
        audio.volume = 0.8;
        if (el.vol) el.vol.value = '80';
      }
      paintMute();
      saveState();
    });
  }

  /* 歌单行点击：任何带 data-track-index 的行都能切歌 */
  document.addEventListener('click', function (e) {
    var row = e.target.closest ? e.target.closest('[data-track-index]') : null;
    if (!row) return;
    if (e.target.closest('.track-tools')) return; /* 编辑/删除按钮不触发切歌 */
    pendingPlay = false;
    loadTrack(Number(row.getAttribute('data-track-index')), true);
  });

  window.addEventListener('pagehide', saveState);
  window.addEventListener('beforeunload', saveState);

  /* ---------- 初始化 ---------- */

  (function init() {
    var saved = readState();
    var startIdx = 0;
    var startTime = 0;
    var wantPlay = false;

    if (saved && typeof saved.i === 'number' && saved.i >= 0 && saved.i < tracks.length) {
      startIdx = saved.i;
      startTime = Number(saved.t) || 0;
      wantPlay = !!saved.p;
    }
    if (saved && typeof saved.v === 'number') {
      audio.volume = Math.max(0, Math.min(1, saved.v));
      if (el.vol) el.vol.value = String(Math.round(audio.volume * 100));
    } else {
      audio.volume = 0.8;
    }
    if (saved && saved.m) audio.muted = true;
    paintMute();

    loadTrack(startIdx, false, startTime);
    paintButton();
    if (el.dur && isFinite(audio.duration) && audio.duration > 0) el.dur.textContent = fmt(audio.duration);

    if (wantPlay) {
      attachSrc(startIdx);
      pendingPlay = true;
      tryPlay();
      /* 自动播放被拦 → 等用户第一次操作再续上 */
      var resume = function () {
        if (!pendingPlay) return;
        pendingPlay = false;
        tryPlay();
        document.removeEventListener('pointerdown', resume, true);
        document.removeEventListener('keydown', resume, true);
      };
      document.addEventListener('pointerdown', resume, true);
      document.addEventListener('keydown', resume, true);
    }

    /* 供其它页面/脚本调用：TQRPlayer.play(索引) */
    window.TQRPlayer = {
      play: function (i) { pendingPlay = false; loadTrack(Number(i) || 0, true); },
      pause: function () { audio.pause(); },
      next: function () { step(1); },
      prev: function () { step(-1); }
    };
  })();
})();
