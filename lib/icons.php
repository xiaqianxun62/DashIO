<?php
declare(strict_types=1);

/**
 * 线性图标集 —— Lucide 1.78.0（ISC 许可），路径数据取自 lucide-static 的 sprite.svg
 *
 * 用法：
 *   icon('search', 'icon-lg')        输出一个 <svg><use href="assets/icons.svg#i-search"/></svg>
 *   stack_icon($slug, $iconField)    按技术栈取图标；后台 icon 字段填 Lucide 名可直接生效
 *
 * 增删图标：更新下面的 TQR_ICONS 白名单，并把对应 <symbol> 贴进 assets/icons.svg（外部 sprite，可缓存）。
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
    'music' => 1,
    'list-music' => 1,
    'play' => 1,
    'pause' => 1,
    'skip-back' => 1,
    'skip-forward' => 1,
    'volume-2' => 1,
    'volume-x' => 1,
];

/**
 * 旧版在页面里输出 symbol 定义；现在改为 assets/icons.svg（可缓存），页面不再内联
 */
function icon_sprite(): string
{
    return '';
}

/** 输出一个图标（symbol 定义放在 assets/icons.svg，按页面缓存复用，不再随每个页面下发） */
function icon(string $name, string $class = ''): string
{
    if (!isset(TQR_ICONS[$name])) {
        $name = 'layers';
    }
    $cls = 'icon' . ($class !== '' ? ' ' . $class : '');
    $base = function_exists('u') ? u('assets/icons.svg') : 'assets/icons.svg';
    return '<svg class="' . $cls . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
        . '<use href="' . $base . '#i-' . $name . '"/></svg>';
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
