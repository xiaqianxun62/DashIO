<?php
declare(strict_types=1);

/**
 * Markdown 支持（基于 Parsedown 1.7.4，单文件、无 Composer 依赖）
 *
 * 用法约定：
 *   1) 内容首行写 [md]   → 强制按 Markdown 渲染
 *   2) 内容首行写 [code] → 强制按代码块渲染
 *   3) 什么都不写        → 自动判断（命中 2 个以上 Markdown 特征，或以标题开头）
 */

require_once __DIR__ . '/Parsedown.php';

/** 渲染 Markdown（安全模式：过滤原始 HTML，避免 XSS） */
function md_render(string $text): string
{
    static $pd = null;
    if ($pd === null) {
        $pd = new Parsedown();
        $pd->setSafeMode(true);
        $pd->setMarkupEscaped(true);
        $pd->setBreaksEnabled(true);
    }
    return $pd->text($text);
}

/** 读取首行的显式标记：'md' / 'code' / ''（未标记） */
function md_flag(string $text): string
{
    $head = strtolower((string)strtok(ltrim($text), "\n"));
    $head = trim($head);
    if ($head === '[md]') {
        return 'md';
    }
    if ($head === '[code]') {
        return 'code';
    }
    return '';
}

/** 去掉首行的 [md] / [code] 标记 */
function md_strip_marker(string $text): string
{
    if (md_flag($text) === '') {
        return $text;
    }
    $pos = strpos($text, "\n");
    return $pos === false ? '' : substr($text, $pos + 1);
}

/**
 * 解析 Markdown 文档为 [name, cats] 结构，用于 .md 文件导入
 * 约定：# 技术栈名称 / ## 分类名称 / ### 条目标题 + 正文
 */
function md_parse_doc(string $raw): array
{
    $raw = str_replace(["\r\n", "\r"], "\n", $raw);
    $lines = explode("\n", $raw);

    $name = '';
    $cats = [];
    $catIdx = -1;
    $ent = null;
    $fence = false;

    $flush = function () use (&$ent, &$cats, &$catIdx) {
        if ($ent !== null && $catIdx >= 0) {
            $cats[$catIdx]['entries'][] = [
                'title' => $ent['title'],
                'body' => trim(implode("\n", $ent['buf'])),
            ];
        }
        $ent = null;
    };

    foreach ($lines as $line) {
        if (preg_match('/^```/', $line) === 1) {
            $fence = !$fence;
            if ($ent !== null) {
                $ent['buf'][] = $line;
            }
            continue;
        }

        if (!$fence && preg_match('/^(#{1,3})[ \t]+(.+)$/u', $line, $m) === 1) {
            $level = strlen($m[1]);
            $title = trim($m[2]);

            if ($level === 1) {
                if ($name === '' && $cats === []) {
                    $name = $title;
                }
                continue;
            }
            if ($level === 2) {
                $flush();
                $cats[] = ['name' => $title, 'entries' => []];
                $catIdx = count($cats) - 1;
                continue;
            }
            if ($catIdx < 0) {
                continue;
            }
            $flush();
            $ent = ['title' => $title, 'buf' => []];
            continue;
        }

        if ($ent !== null) {
            $ent['buf'][] = $line;
        }
    }
    $flush();

    if ($cats === []) {
        $cats[] = ['name' => '正文', 'entries' => []];
    }

    return ['name' => $name, 'cats' => $cats];
}

/** 判断内容是否按 Markdown 渲染：[md]/[code] 优先，否则按特征自动识别 */
function is_md_content(string $text): bool
{
    $flag = md_flag($text);
    if ($flag === 'md') {
        return true;
    }
    if ($flag === 'code') {
        return false;
    }

    $text = trim($text);
    if ($text === '') {
        return false;
    }

    $hits = 0;
    if (preg_match_all('/^[ \t]*#{1,6}[ \t]+\S/m', $text) > 0) { $hits++; }    // 标题（1~6 级）
    if (preg_match_all('/^[ \t]*[-+][ \t]+\S/m', $text) > 0) { $hits++; }    // 无序列表（排除 * 号，避免和 CSS 冲突）
    if (preg_match_all('/^[ \t]*\d+\.[ \t]+\S/m', $text) > 0) { $hits++; }   // 有序列表
    if (preg_match('/\*\*[^*\n]{1,80}\*\*/', $text) === 1) { $hits++; }      // 加粗
    if (preg_match_all('/^```/m', $text) > 0) { $hits++; }                   // 代码围栏
    if (preg_match_all('/^[ \t]*\|.*\|[ \t]*$/m', $text) > 0) { $hits++; }   // 表格
    if (preg_match_all('/^>[ \t]+\S/m', $text) > 0) { $hits++; }             // 引用

    if ($hits >= 2) {
        return true;
    }

    /* 整段都是列表行（≥2 行）也按 Markdown 处理，例如只写了 “- 1 / - 2” 的短条目 */
    $lines = array_values(array_filter(explode("\n", $text), fn($l) => trim($l) !== ''));
    if (count($lines) < 2) {
        return false;
    }
    foreach ($lines as $line) {
        if (preg_match('/^[ \t]*([-+]|\d+\.)[ \t]+\S/', $line) !== 1) {
            return false;
        }
    }
    return true;
}
