/* TechQuickRef 前端交互（无外部依赖） */
(function () {
  'use strict';

  var CSRF = window.CSRF || '';

  /* ---------- 主题 ---------- */
  var themeBtn = document.getElementById('themeToggle');
  function applyTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    if (themeBtn) {
      var sun = themeBtn.querySelector('.theme-sun');
      var moon = themeBtn.querySelector('.theme-moon');
      if (sun) sun.hidden = t !== 'dark';
      if (moon) moon.hidden = t === 'dark';
      themeBtn.setAttribute('title', t === 'dark' ? '切换到浅色' : '切换到深色');
    }
  }
  var saved = null;
  try { saved = localStorage.getItem('tqr-theme'); } catch (e) {}
  applyTheme(saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      var cur = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      applyTheme(cur);
      try { localStorage.setItem('tqr-theme', cur); } catch (e) {}
    });
  }

  /* ---------- Toast ---------- */
  var toastEl = document.getElementById('toast');
  var toastTimer = null;
  function toast(msg, isErr) {
    if (!toastEl) return;
    toastEl.textContent = msg;
    toastEl.hidden = false;
    toastEl.className = isErr ? 'toast err' : 'toast';
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toastEl.hidden = true; }, 2200);
  }

  /* ---------- 复制代码 ---------- */
  function copyText(text, cb) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () { cb(true); }, function () { fallbackCopy(text, cb); });
    } else {
      fallbackCopy(text, cb);
    }
  }
  function fallbackCopy(text, cb) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    var ok = false;
    try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
    document.body.removeChild(ta);
    cb(ok);
  }
  document.addEventListener('click', function (e) {
    var btn = e.target.closest ? e.target.closest('.copy-btn') : null;
    if (!btn) return;
    var pre = btn.closest('.entry') && btn.closest('.entry').querySelector('pre.code-block code');
    if (!pre) return;
    copyText(pre.textContent || '', function (ok) {
      toast(ok ? '已复制到剪贴板' : '复制失败，请手动选择复制');
    });
  });

  /* ---------- 搜索联想 ---------- */
  document.querySelectorAll('.js-suggest-input').forEach(function (input) {
    var box = input.parentElement.querySelector('.js-suggest-box');
    if (!box) return;
    var timer = null;
    function hide() { box.hidden = true; box.innerHTML = ''; }
    function render(items) {
      if (!items.length) { hide(); return; }
      box.innerHTML = items.map(function (it) {
        return '<a class="suggest-item" href="' + it.url + '">' +
          '<span class="s-icon">' + it.icon + '</span>' +
          '<span class="s-title">' + it.title.replace(/</g, '&lt;') + '</span>' +
          '<span class="s-stack">' + it.stack + '</span></a>';
      }).join('');
      box.hidden = false;
    }
    input.addEventListener('input', function () {
      clearTimeout(timer);
      var q = input.value.trim();
      if (!q) { hide(); return; }
      timer = setTimeout(function () {
        fetch('api.php?action=suggest&q=' + encodeURIComponent(q))
          .then(function (r) { return r.json(); })
          .then(function (d) { render(d.ok ? d.items : []); })
          .catch(function () {});
      }, 220);
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') hide();
    });
    document.addEventListener('click', function (e) {
      if (box.contains(e.target) || e.target === input) return;
      hide();
    });
  });

  /* ---------- 清单页（sheet）逻辑 ---------- */
  var SHEET = window.SHEET;
  if (SHEET) {
    var entriesById = {};
    (SHEET.entries || []).forEach(function (en) { entriesById[en.id] = en; });

    /* 栈内过滤 */
    var filterInput = document.getElementById('sheetFilter');
    if (filterInput) {
      filterInput.addEventListener('input', function () {
        var kw = filterInput.value.trim().toLowerCase();
        var shown = 0;
        document.querySelectorAll('.entry').forEach(function (el) {
          var text = (el.textContent || '').toLowerCase();
          var hit = !kw || text.indexOf(kw) !== -1;
          el.style.display = hit ? '' : 'none';
          if (hit) shown++;
        });
        document.querySelectorAll('.cat-block').forEach(function (block) {
          var any = Array.prototype.some.call(block.querySelectorAll('.entry'), function (el) {
            return el.style.display !== 'none';
          });
          block.style.display = any ? '' : 'none';
        });
        var tip = document.getElementById('filterTip');
        if (!tip) {
          tip = document.createElement('p');
          tip.id = 'filterTip';
          tip.className = 'hint';
          tip.style.margin = '10px 0';
          var nav = document.getElementById('catNav');
          (nav ? nav.parentElement : document.body).insertBefore(tip, nav ? nav.nextSibling : null);
        }
        tip.textContent = kw ? ('过滤「' + kw + '」：命中 ' + shown + ' 条' + (shown === 0 ? '，试试其他关键词' : '')) : '';
        tip.hidden = !kw;
      });
    }

    /* 锚点导航 active 高亮 */
    var navLinks = Array.prototype.slice.call(document.querySelectorAll('#catNav a'));
    if ('IntersectionObserver' in window && navLinks.length) {
      var obs = new IntersectionObserver(function (records) {
        records.forEach(function (r) {
          if (r.isIntersecting) {
            var id = r.target.id;
            navLinks.forEach(function (a) {
              a.classList.toggle('active', a.getAttribute('href') === '#' + id);
            });
          }
        });
      }, { rootMargin: '-70px 0px -70% 0px' });
      document.querySelectorAll('.cat-block').forEach(function (b) { obs.observe(b); });
    }

    /* ---------- 编辑弹窗 ---------- */
    if (SHEET.isAdmin) {
      var backdrop = document.getElementById('modalBackdrop');
      var form = document.getElementById('entryForm');
      var fId = document.getElementById('f_id');
      var fCat = document.getElementById('f_cat');
      var fTitle = document.getElementById('f_title');
      var fCode = document.getElementById('f_code');
      var fNote = document.getElementById('f_note');
      var fLink = document.getElementById('f_link');
      var delBtn = document.getElementById('delBtn');
      var modalTitle = document.getElementById('modalTitle');

      function openModal(entry, catId) {
        if (entry) {
          fId.value = entry.id;
          fCat.value = entry.cat;
          fTitle.value = entry.title;
          fCode.value = entry.code;
          fNote.value = entry.note;
          fLink.value = entry.link || '';
          delBtn.hidden = false;
          modalTitle.textContent = '编辑条目 #' + entry.id;
        } else {
          fId.value = '';
          fCat.value = catId;
          fTitle.value = '';
          fCode.value = '';
          fNote.value = '';
          fLink.value = '';
          delBtn.hidden = true;
          modalTitle.textContent = '新增条目';
          setTimeout(function () { fTitle.focus(); }, 60);
        }
        backdrop.hidden = false;
      }
      function closeModal() { backdrop.hidden = true; }

      document.addEventListener('click', function (e) {
        var editBtn = e.target.closest ? e.target.closest('.js-edit-entry') : null;
        if (editBtn) {
          var id = parseInt(editBtn.getAttribute('data-id'), 10);
          var en = entriesById[id];
          if (en) openModal(en, en.cat);
          return;
        }
        var addBtn = e.target.closest ? e.target.closest('.js-add-entry') : null;
        if (addBtn) { openModal(null, parseInt(addBtn.getAttribute('data-cat'), 10)); return; }
        var addTop = e.target.closest ? e.target.closest('#addEntryTop') : null;
        if (addTop) { openModal(null, parseInt(addTop.getAttribute('data-cat'), 10)); return; }
        var delCat = e.target.closest ? e.target.closest('.js-del-cat') : null;
        if (delCat) {
          var name = delCat.getAttribute('data-name');
          if (confirm('删除分类「' + name + '」及其全部条目？此操作不可恢复！')) {
            api('delete_category', { id: parseInt(delCat.getAttribute('data-cat'), 10) })
              .then(function (d) {
                if (d.ok) { toast('分类已删除'); setTimeout(function () { location.reload(); }, 600); }
                else toast(d.error || '删除失败', true);
              });
          }
          return;
        }
        if (e.target === backdrop) closeModal();
      });
      document.getElementById('modalClose').addEventListener('click', closeModal);
      document.getElementById('modalCancel').addEventListener('click', closeModal);
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !backdrop.hidden) closeModal();
      });

      function api(action, data) {
        return fetch('api.php?action=' + action, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
          body: JSON.stringify(data)
        }).then(function (r) { return r.json(); });
      }

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var payload = {
          id: fId.value ? parseInt(fId.value, 10) : 0,
          category_id: parseInt(fCat.value, 10),
          title: fTitle.value.trim(),
          code: fCode.value,
          note: fNote.value.trim(),
          link: fLink.value.trim()
        };
        if (!payload.title) { toast('标题不能为空', true); return; }
        if (!payload.category_id) { toast('请先创建分类', true); return; }
        api('save_entry', payload).then(function (d) {
          if (d.ok) { toast('已保存'); setTimeout(function () { location.reload(); }, 500); }
          else toast(d.error || '保存失败', true);
        }).catch(function () { toast('网络错误', true); });
      });

      delBtn.addEventListener('click', function () {
        if (!fId.value) return;
        if (!confirm('确认删除该条目？')) return;
        api('delete_entry', { id: parseInt(fId.value, 10) }).then(function (d) {
          if (d.ok) { toast('已删除'); setTimeout(function () { location.reload(); }, 500); }
          else toast(d.error || '删除失败', true);
        });
      });
    }
  }

  /* ---------- 代码高亮 ---------- */
  if (window.HL && window.HL.runAll) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', window.HL.runAll);
    } else {
      window.HL.runAll();
    }
  }

  /* ---------- 卡片宽度跟随内容宽度 ---------- */
  /* 代码里最长的一行有多宽，卡片就允许多宽；只有超过可视宽度时才折行 */
  var measureCtx = (function () {
    try {
      var cv = document.createElement('canvas');
      return cv.getContext ? cv.getContext('2d') : null;
    } catch (err) { return null; }
  })();

  function longestLineWidth(code, font) {
    if (!measureCtx) return 0;
    measureCtx.font = font;
    var lines = (code.textContent || '').split('\n');
    var max = 0;
    for (var i = 0; i < lines.length; i++) {
      var w = measureCtx.measureText(lines[i]).width;
      if (w > max) max = w;
    }
    return max;
  }

  function fitCardWidths() {
    var wide = window.innerWidth > 960;
    var cards = document.querySelectorAll('.entry, .search-item');
    for (var i = 0; i < cards.length; i++) {
      var card = cards[i];
      var code = card.querySelector('.code-block code');
      if (!wide) { card.style.flexBasis = ''; continue; }

      var grid = card.parentElement;
      var avail = grid ? grid.clientWidth : 720;
      var need = 345;
      if (code) {
        var cs = window.getComputedStyle(code);
        var font = cs.fontStyle + ' ' + cs.fontWeight + ' ' + cs.fontSize + ' ' + cs.fontFamily;
        /* 文本宽 + 代码块左右内边距 30 + 边框 2 + 卡片左右内边距 32 + 边框 2 */
        need = Math.ceil(longestLineWidth(code, font)) + 66;
      }
      /* 卡片宽度 = 最长一行代码的实际宽度，只有在超过可视宽度时才折行 */
      card.style.flexBasis = Math.min(Math.max(need, 320), avail) + 'px';
    }
  }

  fitCardWidths();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fitCardWidths);
  }

  var fitTimer = null;
  window.addEventListener('resize', function () {
    clearTimeout(fitTimer);
    fitTimer = setTimeout(fitCardWidths, 150);
  });
})();
