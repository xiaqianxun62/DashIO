<?php
declare(strict_types=1);

/**
 * 线性图标集 —— Lucide 1.78.0（ISC 许可），路径数据取自 lucide-static 的 sprite.svg
 *
 * 用法：
 *   icon('search', 'icon-lg')        输出一个 <svg><use href="#i-search"/></svg>
 *   icon_sprite()                    在页面里输出一次 symbol 定义（page_header 已调用）
 *   stack_icon($slug, $iconField)    按技术栈取图标；后台 icon 字段填 Lucide 名可直接生效
 *
 * 增删图标：更新下面的 TQR_ICONS 白名单，并把对应 <symbol> 贴进 icon_sprite()。
 */

const TQR_ICONS = [
    'zap' => 1,
    'search' => 1,
    'moon' => 1,
    'sun' => 1,
    'settings' => 1,
    'upload' => 1,
    'database' => 1,
    'activity' => 1,
    'file-code' => 1,
    'plus' => 1,
    'trash-2' => 1,
    'pencil' => 1,
    'link-2' => 1,
    'copy' => 1,
    'x' => 1,
    'arrow-left' => 1,
    'chevron-right' => 1,
    'layers' => 1,
    'book-open' => 1,
    'folder-open' => 1,
    'users' => 1,
    'target' => 1,
    'code' => 1,
    'code-xml' => 1,
    'braces' => 1,
    'palette' => 1,
    'coffee' => 1,
    'box' => 1,
    'app-window' => 1,
    'monitor' => 1,
    'circle-check' => 1,
    'triangle-alert' => 1,
    'inbox' => 1,
    'external-link' => 1,
    'log-in' => 1,
    'file-text' => 1,
    'image' => 1,
];

/**
 * 页面内输出一次图标 sprite（放在 <body> 之后，CSS 中 .icon-sprite 已隐藏）
 */
function icon_sprite(): string
{
    return <<<'SVG'
<svg class="icon-sprite" aria-hidden="true" focusable="false">
<symbol id="i-zap" viewBox="0 0 24 24">
      <path d="M15.914 4a1.5 1.5 0 00-2.474-1.561l-9 9A1.5 1.5 0 005.5 14h4.002a.5.5 0 01.471.666L8.086 20a1.5 1.5 0 002.475 1.56l9-9A1.5 1.5 0 0018.5 10h-3.997a.5.5 0 01-.472-.667z" />
    </symbol>
<symbol id="i-search" viewBox="0 0 24 24">
      <path d="m21 21-4.34-4.34" />
      <circle cx="11" cy="11" r="8" />
    </symbol>
<symbol id="i-moon" viewBox="0 0 24 24">
      <path d="M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6 6 0 0 0 8.268 8.268c.344-.215.825-.004.803.401" />
    </symbol>
<symbol id="i-sun" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="4" />
      <path d="M12 2v2" />
      <path d="M12 20v2" />
      <path d="m4.93 4.93 1.41 1.41" />
      <path d="m17.66 17.66 1.41 1.41" />
      <path d="M2 12h2" />
      <path d="M20 12h2" />
      <path d="m6.34 17.66-1.41 1.41" />
      <path d="m19.07 4.93-1.41 1.41" />
    </symbol>
<symbol id="i-settings" viewBox="0 0 24 24">
      <path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915" />
      <circle cx="12" cy="12" r="3" />
    </symbol>
<symbol id="i-upload" viewBox="0 0 24 24">
      <path d="M12 3v12" />
      <path d="m17 8-5-5-5 5" />
      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
    </symbol>
<symbol id="i-database" viewBox="0 0 24 24">
      <ellipse cx="12" cy="5" rx="9" ry="3" />
      <path d="M3 5V19A9 3 0 0 0 21 19V5" />
      <path d="M3 12A9 3 0 0 0 21 12" />
    </symbol>
<symbol id="i-activity" viewBox="0 0 24 24">
      <path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2" />
    </symbol>
<symbol id="i-file-code" viewBox="0 0 24 24">
      <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z" />
      <path d="M14 2v5a1 1 0 0 0 1 1h5" />
      <path d="M10 12.5 8 15l2 2.5" />
      <path d="m14 12.5 2 2.5-2 2.5" />
    </symbol>
<symbol id="i-plus" viewBox="0 0 24 24">
      <path d="M5 12h14" />
      <path d="M12 5v14" />
    </symbol>
<symbol id="i-trash-2" viewBox="0 0 24 24">
      <use href="#trash" />
    </symbol>
<symbol id="i-pencil" viewBox="0 0 24 24">
      <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
      <path d="m15 5 4 4" />
    </symbol>
<symbol id="i-link-2" viewBox="0 0 24 24">
      <path d="M9 17H7A5 5 0 0 1 7 7h2" />
      <path d="M15 7h2a5 5 0 1 1 0 10h-2" />
      <line x1="8" x2="16" y1="12" y2="12" />
    </symbol>
<symbol id="i-copy" viewBox="0 0 24 24">
      <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
      <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
    </symbol>
<symbol id="i-x" viewBox="0 0 24 24">
      <path d="M18 6 6 18" />
      <path d="m6 6 12 12" />
    </symbol>
<symbol id="i-arrow-left" viewBox="0 0 24 24">
      <path d="m12 19-7-7 7-7" />
      <path d="M19 12H5" />
    </symbol>
<symbol id="i-chevron-right" viewBox="0 0 24 24">
      <path d="m9 18 6-6-6-6" />
    </symbol>
<symbol id="i-layers" viewBox="0 0 24 24">
      <path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z" />
      <path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12" />
      <path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17" />
    </symbol>
<symbol id="i-book-open" viewBox="0 0 24 24">
      <path d="M12 5v16" />
      <path d="M20.001 19A2 2 0 0022 17V5a2 2 0 00-1.999-2L16 3.002A5 5 0 0012 5a5 5 0 00-4-2H4a2 2 0 00-2 2v12a2 2 0 001.999 2H8a5 5 0 014 2 5 5 0 014-2z" />
    </symbol>
<symbol id="i-folder-open" viewBox="0 0 24 24">
      <path d="m6 14 1.5-2.9A2 2 0 0 1 9.24 10H20a2 2 0 0 1 1.94 2.5l-1.54 6a2 2 0 0 1-1.95 1.5H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H18a2 2 0 0 1 2 2v2" />
    </symbol>
<symbol id="i-users" viewBox="0 0 24 24">
      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
      <path d="M16 3.128a4 4 0 0 1 0 7.744" />
      <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
      <circle cx="9" cy="7" r="4" />
    </symbol>
<symbol id="i-target" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" />
      <circle cx="12" cy="12" r="6" />
      <circle cx="12" cy="12" r="2" />
    </symbol>
<symbol id="i-code" viewBox="0 0 24 24">
      <path d="m16 18 6-6-6-6" />
      <path d="m8 6-6 6 6 6" />
    </symbol>
<symbol id="i-code-xml" viewBox="0 0 24 24">
      <path d="m18 16 4-4-4-4" />
      <path d="m6 8-4 4 4 4" />
      <path d="m14.5 4-5 16" />
    </symbol>
<symbol id="i-braces" viewBox="0 0 24 24">
      <path d="M8 3H7a2 2 0 0 0-2 2v5a2 2 0 0 1-2 2 2 2 0 0 1 2 2v5c0 1.1.9 2 2 2h1" />
      <path d="M16 21h1a2 2 0 0 0 2-2v-5c0-1.1.9-2 2-2a2 2 0 0 1-2-2V5a2 2 0 0 0-2-2h-1" />
    </symbol>
<symbol id="i-palette" viewBox="0 0 24 24">
      <path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z" />
      <circle cx="13.5" cy="6.5" r=".5" fill="currentColor" />
      <circle cx="17.5" cy="10.5" r=".5" fill="currentColor" />
      <circle cx="6.5" cy="12.5" r=".5" fill="currentColor" />
      <circle cx="8.5" cy="7.5" r=".5" fill="currentColor" />
    </symbol>
<symbol id="i-coffee" viewBox="0 0 24 24">
      <path d="M10 2v2" />
      <path d="M14 2v2" />
      <path d="M16 8a1 1 0 0 1 1 1v8a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V9a1 1 0 0 1 1-1h14a4 4 0 1 1 0 8h-1" />
      <path d="M6 2v2" />
    </symbol>
<symbol id="i-box" viewBox="0 0 24 24">
      <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
      <path d="m3.3 7 8.7 5 8.7-5" />
      <path d="M12 22V12" />
    </symbol>
<symbol id="i-app-window" viewBox="0 0 24 24">
      <rect x="2" y="4" width="20" height="16" rx="2" />
      <path d="M10 4v4" />
      <path d="M2 8h20" />
      <path d="M6 4v4" />
    </symbol>
<symbol id="i-monitor" viewBox="0 0 24 24">
      <rect width="20" height="14" x="2" y="3" rx="2" />
      <line x1="8" x2="16" y1="21" y2="21" />
      <line x1="12" x2="12" y1="17" y2="21" />
    </symbol>
<symbol id="i-circle-check" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" />
      <path d="m16 9-5.5 5.5L8 12" />
    </symbol>
<symbol id="i-triangle-alert" viewBox="0 0 24 24">
      <use href="#triangle-exclamation-point" />
    </symbol>
<symbol id="i-inbox" viewBox="0 0 24 24">
      <polyline points="22 12 16 12 14 15 10 15 8 12 2 12" />
      <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
    </symbol>
<symbol id="i-external-link" viewBox="0 0 24 24">
      <path d="M15 3h6v6" />
      <path d="M10 14 21 3" />
      <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
    </symbol>
<symbol id="i-log-in" viewBox="0 0 24 24">
      <path d="m10 17 5-5-5-5" />
      <path d="M15 12H3" />
      <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
    </symbol>
<symbol id="i-file-text" viewBox="0 0 24 24">
      <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z" />
      <path d="M14 2v5a1 1 0 0 0 1 1h5" />
      <path d="M10 9H8" />
      <path d="M16 13H8" />
      <path d="M16 17H8" />
    </symbol>
<symbol id="i-image" viewBox="0 0 24 24">
      <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
      <circle cx="9" cy="9" r="2" />
      <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
    </symbol>
</svg>
SVG;
}

/** 输出一个图标 */
function icon(string $name, string $class = ''): string
{
    if (!isset(TQR_ICONS[$name])) {
        $name = 'layers';
    }
    $cls = 'icon' . ($class !== '' ? ' ' . $class : '');
    return '<svg class="' . $cls . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
        . '<use href="#i-' . $name . '"/></svg>';
}

/**
 * 技术栈图标名：后台 icon 字段填 Lucide 名（如 database / code）优先，否则按 slug 自动匹配
 */
function stack_icon_name(string $slug, string $iconField = ''): string
{
    $field = trim($iconField);
    if ($field !== '' && isset(TQR_ICONS[$field])) {
        return $field;
    }
    $map = [
        'php' => 'file-code',
        'html' => 'code-xml',
        'css' => 'palette',
        'javascript' => 'braces',
        'mysql' => 'database',
        'java' => 'coffee',
        'csharp' => 'code',
        'dotnet' => 'box',
        'wpf' => 'app-window',
        'winform' => 'monitor',
        'interview' => 'target',
    ];
    return $map[$slug] ?? 'layers';
}

/** 技术栈图标（<svg>） */
function stack_icon(string $slug, string $iconField = '', string $class = ''): string
{
    return icon(stack_icon_name($slug, $iconField), $class);
}
