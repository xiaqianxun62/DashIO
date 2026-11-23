/* TechQuickRef 轻量语法高亮器（无外部依赖）
   支持：php / js / java / csharp / sql / css / html / xml
   用法：hl.highlight(codeText, lang) 返回 HTML 字符串 */
(function (global) {
  'use strict';

  var ESC = { '&': '&amp;', '<': '&lt;', '>': '&gt;' };
  function esc(s) {
    return s.replace(/[&<>]/g, function (c) { return ESC[c]; });
  }
  function span(cls, s) {
    return '<span class="' + cls + '">' + s + '</span>';
  }

  var KW = {
    php: 'abstract and array as break callable case catch class clone const continue declare default do echo else elseif empty enum extends final finally fn for foreach function global goto if implements include include_once instanceof insteadof interface isset list match namespace new or print private protected public readonly require require_once return static switch throw trait try unset use var while xor yield true false null int float string bool object mixed void self parent',
    js: 'async await break case catch class const continue debugger default delete do else enum export extends false finally for function if import in instanceof let new of return static super switch this throw true try typeof var void while with yield undefined null',
    java: 'abstract assert boolean break byte case catch char class continue default do double else enum extends final finally float for if implements import instanceof int interface long native new package private protected public return short static strictfp super switch synchronized this throw throws transient try void volatile while true false null var record sealed',
    csharp: 'abstract as async await base bool break byte case catch char checked class const continue decimal default delegate do double dynamic else enum event explicit extern false finally fixed float for foreach goto if implicit in int interface internal is lock long namespace new null object operator out override params partial private protected public readonly ref return sbyte sealed short sizeof stackalloc static string struct switch this throw true try typeof uint ulong unchecked unsafe ushort using var virtual void volatile while when record with',
    sql: 'select from where insert update delete create drop alter table database schema index view trigger procedure function grant revoke begin commit rollback start transaction and or not null is in like between join inner left right full outer cross on as order by group having limit offset distinct union all any some exists case when then else end asc desc into values set default primary key foreign references constraint unique check auto_increment engine charset collate add change modify rename truncate explain analyze show use describe dual mod xor true false unknown'
  };

  var TYPES = {
    php: ['PDO', 'DateTime', 'DateTimeImmutable', 'Exception', 'Error', 'TypeError', 'ArrayObject', 'SplStack', 'JsonSerializable', 'Countable', 'Stringable', 'Iterator', 'ArrayAccess'],
    js: ['Number', 'String', 'Boolean', 'Symbol', 'BigInt', 'Object', 'Array', 'Math', 'JSON', 'Date', 'Promise', 'Set', 'Map', 'WeakMap', 'WeakSet', 'RegExp', 'Error', 'Function', 'Proxy', 'Reflect', 'window', 'document', 'console', 'localStorage', 'navigator', 'fetch'],
    java: ['String', 'Integer', 'Long', 'Double', 'Float', 'Boolean', 'Character', 'System', 'Math', 'Object', 'List', 'ArrayList', 'Map', 'HashMap', 'Set', 'HashSet', 'Optional', 'Stream', 'StringBuilder', 'Thread', 'Exception', 'RuntimeException', 'LocalDate', 'LocalDateTime', 'BigDecimal', 'Arrays', 'Collections', 'ExecutorService'],
    csharp: ['Console', 'Math', 'Task', 'List', 'Dictionary', 'HashSet', 'IEnumerable', 'IList', 'Action', 'Func', 'Exception', 'ArgumentException', 'DateTime', 'TimeSpan', 'JsonSerializer', 'File', 'Directory', 'Path', 'Convert', 'IEnumerable', 'ICommand', 'INotifyPropertyChanged', 'ObservableCollection', 'Application'],
    sql: []
  };

  var CI = { php: true, sql: true }; // 关键字大小写不敏感的语言

  function buildSets(lang) {
    var kw = (KW[lang] || '').split(/\s+/);
    var kwSet = {};
    for (var i = 0; i < kw.length; i++) kwSet[kw[i]] = true;
    var tpSet = {};
    (TYPES[lang] || []).forEach(function (t) { tpSet[t] = true; });
    return { kwSet: kwSet, tpSet: tpSet };
  }

  /* ---------- 标记语言（HTML / XML / XAML）专用 ---------- */
  function hlMarkup(src) {
    var out = esc(src);
    // 注释
    out = out.replace(/&lt;!--[\s\S]*?--&gt;/g, function (m) { return span('tk-c', m); });
    // 标签：<name attrs...>
    out = out.replace(/&lt;(\/?)([\w:.-]+)((?:(?!&gt;)[\s\S])*?)(\/?&gt;)/g,
      function (m, slash, name, attrs, close) {
        var attrsHl = attrs.replace(/([\w:.-]+)(\s*=\s*)("[^"]*"|'[^']*')|([\w:.-]+)/g,
          function (am, an, eq, val, bare) {
            if (an !== undefined && val !== undefined) {
              return span('tk-a', an) + eq + span('tk-s', val);
            }
            if (bare !== undefined) return span('tk-a', bare);
            return am;
          });
        return '&lt;' + slash + span('tk-t', name) + attrsHl + close;
      });
    return out;
  }

  /* ---------- CSS 专用 ---------- */
  function hlCss(src) {
    var parts = [
      '(\\/\\*[\\s\\S]*?\\*\\/)',                                  // 1 注释
      '("[^"\\n]*"|\'[^\'\\n]*\')',                                   // 2 字符串
      '(#[0-9a-fA-F]{3,8}\\b)',                                     // 3 hex 颜色
      '(@[\\w-]+)',                                                 // 4 @规则
      '([\\w-]+(?=\\s*:))',                                         // 5 属性名
      '(\\b\\d+(?:\\.\\d+)?(?:px|em|rem|vw|vh|%|s|ms|deg|fr|ch)?\\b)', // 6 数值
      '([.#:]?[-\\w]+)'                                             // 7 其他词
    ];
    var master = new RegExp(parts.join('|'), 'g');
    var out = '', last = 0, m;
    while ((m = master.exec(src)) !== null) {
      out += esc(src.slice(last, m.index));
      var tok = m[0];
      var cls = m[1] ? 'tk-c' : m[2] ? 'tk-s' : m[3] ? 'tk-n' : m[4] ? 'tk-k' : m[5] ? 'tk-a' : m[6] ? 'tk-n' : 'tk-t';
      out += span(cls, esc(tok));
      last = m.index + tok.length;
    }
    out += esc(src.slice(last));
    return out;
  }

  /* ---------- 通用语言（php/js/java/csharp/sql） ---------- */
  function hlGeneric(src, lang) {
    var sets = buildSets(lang);
    var commentAlt;
    if (lang === 'sql') {
      commentAlt = '--[^\\n]*|\\/\\*[\\s\\S]*?\\*\\/|#[^\\n]*';
    } else if (lang === 'php') {
      commentAlt = '\\/\\/[^\\n]*|#[^\\n]*|\\/\\*[\\s\\S]*?\\*\\/';
    } else {
      commentAlt = '\\/\\/[^\\n]*|\\/\\*[\\s\\S]*?\\*\\/';
    }
    var parts = [
      '(' + commentAlt + ')',                                        // 1 注释
      '("(?:\\\\.|[^"\\\\\\n])*"' +                                 // 2 双引号
        "|'(?:\\\\.|[^'\\\\\\n])*'" +
        '|`(?:\\\\.|[^`\\\\])*`' +
        '|@"(?:""|[^"])*")',                                          //   模板/verbatim
      '(\\b(?:0x[0-9a-fA-F]+|\\d[\\d_]*(?:\\.\\d+)?)\\b)',          // 3 数字
      '(\\$?[A-Za-z_][\\w$]*)'                                        // 4 词（可带 $）
    ];
    var master = new RegExp(parts.join('|'), 'g');
    var out = '', last = 0, m;
    while ((m = master.exec(src)) !== null) {
      out += esc(src.slice(last, m.index));
      var tok = m[0];
      if (m[1]) {
        out += span('tk-c', esc(tok));
      } else if (m[2]) {
        out += span('tk-s', esc(tok));
      } else if (m[3]) {
        out += span('tk-n', esc(tok));
      } else {
        var isVar = tok.charAt(0) === '$';
        var probe = CI[lang] ? tok.toLowerCase() : tok;
        var after = src.charAt(master.lastIndex);
        if (isVar) {
          out += span('tk-a', esc(tok));
        } else if (sets.kwSet[probe]) {
          out += span('tk-k', esc(tok));
        } else if (sets.tpSet[tok]) {
          out += span('tk-t', esc(tok));
        } else if (after === '(' || after === '<') {
          out += span('tk-f', esc(tok));
        } else {
          out += esc(tok);
        }
      }
      last = m.index + tok.length;
    }
    out += esc(src.slice(last));
    return out;
  }

  function highlight(src, lang) {
    src = String(src == null ? '' : src);
    if (!src) return '';
    try {
      if (lang === 'html' || lang === 'xml' || lang === 'xaml') return hlMarkup(src);
      if (lang === 'css') return hlCss(src);
      if (lang === 'php' || lang === 'js' || lang === 'java' || lang === 'csharp' || lang === 'sql') {
        return hlGeneric(src, lang);
      }
      return esc(src);
    } catch (e) {
      return esc(src);
    }
  }

  /* 页面加载后自动高亮所有 code[data-lang] */
  function runAll() {
    var codes = document.querySelectorAll('pre.code-block code[data-lang]');
    for (var i = 0; i < codes.length; i++) {
      var el = codes[i];
      var raw = el.textContent || '';
      el.innerHTML = highlight(raw, el.getAttribute('data-lang') || 'plain');
    }
    var searchCodes = document.querySelectorAll('.search-item code');
    for (var j = 0; j < searchCodes.length; j++) {
      searchCodes[j].innerHTML = esc(searchCodes[j].textContent || '');
    }
  }

  global.HL = { highlight: highlight, runAll: runAll, esc: esc };
})(window);
