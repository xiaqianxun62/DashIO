<?php
declare(strict_types=1);

/**
 * TechQuickRef - 数据库访问层
 * 支持 MySQL / MariaDB（推荐生产使用）与 SQLite（轻量试用模式）
 * 统一使用 PDO 预处理语句，防注入
 */

/** 读取配置（未安装时自动跳转到安装器） */
function db_conf(): array
{
    static $conf = null;
    if ($conf === null) {
        $file = dirname(__DIR__) . '/config.php';
        if (!is_file($file)) {
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            header('Location: ' . $base . '/install.php');
            exit;
        }
        $loaded = require $file;
        $conf = is_array($loaded) ? $loaded : [];
    }
    return $conf;
}

function db_driver(): string
{
    $conf = db_conf();
    return ($conf['driver'] ?? 'mysql') === 'sqlite' ? 'sqlite' : 'mysql';
}

/** 获取 PDO 单例连接 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $conf = db_conf();
    if (db_driver() === 'sqlite') {
        $path = $conf['sqlite_path'] ?? (dirname(__DIR__) . '/data/quickref.sqlite');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $conf['host'] ?? '127.0.0.1',
            $conf['port'] ?? 3306,
            $conf['dbname'] ?? 'techquickref'
        );
        $opts = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $pdo = new PDO($dsn, $conf['user'] ?? 'root', $conf['pass'] ?? '', $opts);
    }
    return $pdo;
}

/** 执行预处理语句 */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

/** 查询多行 */
function q_all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

/** 查询单行（无结果返回 null） */
function q_row(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

/** 查询单值（第一列） */
function q_val(string $sql, array $params = [])
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

/** 统一时间戳（写入 updated_at 用，跨驱动一致） */
function db_now(): string
{
    return date('Y-m-d H:i:s');
}

/** 建表 DDL（按驱动返回差异化的 SQL 语句数组） */
function db_schema_sql(): array
{
    if (db_driver() === 'sqlite') {
        return [
            "CREATE TABLE IF NOT EXISTS stacks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                grp TEXT NOT NULL DEFAULT '其他',
                icon TEXT NOT NULL DEFAULT '📘',
                color TEXT NOT NULL DEFAULT '#5b6ef5',
                description TEXT NOT NULL DEFAULT '',
                sort INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                stack_id INTEGER NOT NULL REFERENCES stacks(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                sort INTEGER NOT NULL DEFAULT 0,
                UNIQUE (stack_id, name)
            )",
            "CREATE TABLE IF NOT EXISTS entries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
                title TEXT NOT NULL,
                code TEXT,
                note TEXT,
                link TEXT,
                sort INTEGER NOT NULL DEFAULT 0,
                updated_by TEXT,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                display_name TEXT NOT NULL DEFAULT '',
                role TEXT NOT NULL DEFAULT 'member',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS settings (
                k TEXT PRIMARY KEY,
                v TEXT NOT NULL DEFAULT ''
            )",
        ];
    }
    return [
        "CREATE TABLE IF NOT EXISTS stacks (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            grp VARCHAR(50) NOT NULL DEFAULT '其他',
            icon VARCHAR(16) NOT NULL DEFAULT '📘',
            color VARCHAR(20) NOT NULL DEFAULT '#5b6ef5',
            description VARCHAR(255) NOT NULL DEFAULT '',
            sort INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            stack_id INT UNSIGNED NOT NULL,
            name VARCHAR(100) NOT NULL,
            sort INT NOT NULL DEFAULT 0,
            UNIQUE KEY uq_stack_name (stack_id, name),
            CONSTRAINT fk_cat_stack FOREIGN KEY (stack_id) REFERENCES stacks(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS entries (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id INT UNSIGNED NOT NULL,
            title VARCHAR(200) NOT NULL,
            code MEDIUMTEXT NULL,
            note TEXT NULL,
            link VARCHAR(255) NULL,
            sort INT NOT NULL DEFAULT 0,
            updated_by VARCHAR(50) NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_entry_title (title),
            KEY idx_entry_cat (category_id),
            CONSTRAINT fk_ent_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            display_name VARCHAR(100) NOT NULL DEFAULT '',
            role VARCHAR(20) NOT NULL DEFAULT 'member',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS settings (
            k VARCHAR(50) PRIMARY KEY,
            v TEXT NOT NULL DEFAULT ''
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}

/** 尝试连接 MySQL（安装器用，返回 [PDO|null, 错误信息|null]） */
function db_try_mysql(string $host, int $port, string $dbname, string $user, string $pass): array
{
    try {
        $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, $opts);
        return [$pdo, null];
    } catch (PDOException $e) {
        return [null, $e->getMessage()];
    }
}
