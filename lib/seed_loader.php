<?php
declare(strict_types=1);

/** 获取种子文件列表（data/seed/*.php，按文件名排序） */
function seed_files(): array
{
    $dir = dirname(__DIR__) . '/data/seed';
    $out = glob($dir . '/*.php') ?: [];
    sort($out);
    return $out;
}

/** 加载全部种子（每个文件返回一个栈结构） */
function seed_load_all(): array
{
    $seeds = [];
    foreach (seed_files() as $f) {
        $data = require $f;
        if (is_array($data) && !empty($data['slug'])) {
            $seeds[] = $data;
        }
    }
    return $seeds;
}

/**
 * 导入种子到数据库（统一使用 db() 单例连接，保证 lastInsertId 一致）
 * $mode = 'fresh'  全新安装（清空 stacks/categories/entries 后导入）
 * $mode = 'append' 增量导入（按 slug 判重；栈已存在时继续合并，只补缺失的分类与条目，不覆盖已有内容）
 * 返回导入统计
 */
function seed_import(string $mode = 'fresh'): array
{
    $pdo = db();
    $imported = ['stacks' => 0, 'cats' => 0, 'entries' => 0, 'skipped' => 0, 'entries_skipped' => 0];

    if ($mode === 'fresh') {
        if (db_driver() === 'mysql') {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        }
        $pdo->exec('DELETE FROM entries');
        $pdo->exec('DELETE FROM categories');
        $pdo->exec('DELETE FROM stacks');
        if (db_driver() === 'mysql') {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    foreach (seed_load_all() as $stack) {
        $slug = (string)($stack['slug'] ?? '');
        if ($slug === '') {
            continue;
        }

        $existing = q_val('SELECT id FROM stacks WHERE slug = ?', [$slug]);
        if ($existing) {
            if ($mode !== 'append') {
                $imported['skipped']++;
                continue;
            }
            /* 增量模式：栈已存在 → 走合并，补齐新增的分类与条目 */
            $stackId = (int)$existing;
            $imported['skipped']++;
        } else {
            q(
                'INSERT INTO stacks (slug, name, grp, icon, color, description, sort) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $slug,
                    (string)$stack['name'],
                    (string)($stack['category'] ?? '其他'),
                    (string)($stack['icon'] ?? '📘'),
                    (string)($stack['color'] ?? '#5b6ef5'),
                    (string)($stack['description'] ?? ''),
                    (int)($stack['sort'] ?? 0),
                ]
            );
            $stackId = (int)$pdo->lastInsertId();
            $imported['stacks']++;
        }

        $seedCatPos = 0;
        foreach (($stack['categories'] ?? []) as $cat) {
            $seedCatPos++;
            $catName = (string)$cat['name'];
            $catId = (int)(q_val('SELECT id FROM categories WHERE stack_id = ? AND name = ?', [$stackId, $catName]) ?? 0);
            if ($catId === 0) {
                /* 新分类按种子文件里的位置插入：先把该位置及之后的分类整体后移一位 */
                q('UPDATE categories SET sort = sort + 1 WHERE stack_id = ? AND sort >= ?', [$stackId, $seedCatPos]);
                q('INSERT INTO categories (stack_id, name, sort) VALUES (?, ?, ?)', [$stackId, $catName, $seedCatPos]);
                $catId = (int)$pdo->lastInsertId();
                $imported['cats']++;
            }

            foreach (($cat['entries'] ?? []) as $ent) {
                $title = (string)$ent['title'];
                if (q_val('SELECT id FROM entries WHERE category_id = ? AND title = ?', [$catId, $title])) {
                    $imported['entries_skipped']++;
                    continue;
                }
                $entSort = (int)(q_val('SELECT COALESCE(MAX(sort), 0) FROM entries WHERE category_id = ?', [$catId]) ?? 0) + 1;
                q(
                    'INSERT INTO entries (category_id, title, code, note, link, sort, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [
                        $catId,
                        $title,
                        (string)($ent['code'] ?? ''),
                        (string)($ent['note'] ?? ''),
                        (string)($ent['link'] ?? '') ?: null,
                        $entSort,
                        'system',
                    ]
                );
                $imported['entries']++;
            }
        }
    }
    return $imported;
}
