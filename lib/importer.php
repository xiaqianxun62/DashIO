<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * 把 md_parse_doc() 解析出来的结构写进数据库
 *   - 技术栈：按 slug 判重，不存在则新建
 *   - 分类：按名称复用，不存在则追加在末尾
 *   - 条目：按「分类 + 标题」判重，重复跳过，所以可以反复导入做增量补充
 * 返回统计：added / skipped / cats / stack_new
 */
function md_import_doc(
    array $doc,
    string $slug,
    string $stackName,
    string $grp = 'Markdown 笔记',
    string $icon = '📝',
    string $color = '#0ea5e9',
    string $desc = '由 Markdown 导入'
): array {
    $out = ['added' => 0, 'skipped' => 0, 'cats' => 0, 'stack_new' => false];

    $stackId = (int)(q_val('SELECT id FROM stacks WHERE slug = ?', [$slug]) ?? 0);
    if ($stackId === 0) {
        $sort = (int)q_val('SELECT COALESCE(MAX(sort), 0) + 1 FROM stacks');
        q(
            'INSERT INTO stacks (slug, name, grp, icon, color, description, sort) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$slug, $stackName, $grp, $icon, $color, $desc, $sort]
        );
        $stackId = (int)db()->lastInsertId();
        $out['stack_new'] = true;
    }

    foreach (($doc['cats'] ?? []) as $cat) {
        $catName = trim((string)($cat['name'] ?? ''));
        if ($catName === '') {
            continue;
        }

        $catId = (int)(q_val('SELECT id FROM categories WHERE stack_id = ? AND name = ?', [$stackId, $catName]) ?? 0);
        if ($catId === 0) {
            $catSort = (int)q_val('SELECT COALESCE(MAX(sort), 0) + 1 FROM categories WHERE stack_id = ?', [$stackId]);
            q('INSERT INTO categories (stack_id, name, sort) VALUES (?, ?, ?)', [$stackId, $catName, $catSort]);
            $catId = (int)db()->lastInsertId();
            $out['cats']++;
        }

        foreach (($cat['entries'] ?? []) as $ent) {
            $title = trim((string)($ent['title'] ?? ''));
            $body = trim((string)($ent['body'] ?? ''));
            if ($title === '' || $body === '') {
                continue;
            }
            if (q_val('SELECT id FROM entries WHERE category_id = ? AND title = ?', [$catId, $title])) {
                $out['skipped']++;
                continue;
            }
            $entSort = (int)q_val('SELECT COALESCE(MAX(sort), 0) + 1 FROM entries WHERE category_id = ?', [$catId]);
            q(
                'INSERT INTO entries (category_id, title, code, note, link, sort, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$catId, $title, "[md]\n" . $body, '', null, $entSort, 'md-import']
            );
            $out['added']++;
        }
    }

    return $out;
}

/** 由技术栈名称生成 slug；中文名生成 md-xxxxxxxx 形式，$hint 优先（用户手填） */
function md_slug_from(string $name, string $hint = ''): string
{
    $src = $hint !== '' ? $hint : $name;
    $slug = strtolower(trim($src));
    $slug = trim((string)preg_replace('/[^a-z0-9_-]+/', '-', $slug), '-_');
    if ($slug !== '' && preg_match('/^[a-z0-9_-]{1,50}$/', $slug) === 1) {
        return substr($slug, 0, 50);
    }
    return 'md-' . substr(md5($name), 0, 8);
}
