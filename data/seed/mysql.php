<?php
/* MySQL / MariaDB 速查种子 */
return [
    'slug' => 'mysql',
    'name' => 'MySQL / MariaDB',
    'category' => '数据库',
    'icon' => '🗄️',
    'color' => '#00758F',
    'description' => 'DDL / DML / 查询 / JOIN / 索引 / 事务与运维速查',
    'sort' => 30,
    'categories' => [

        ['name' => '库表与连接', 'entries' => [
            ['title' => '连接与退出（命令行）', 'code' => <<<'CODE'
mysql -h 127.0.0.1 -P 3306 -u root -p
mysql -u root -p mydb          # 直接选库
SHOW DATABASES;
USE mydb;
STATUS;                         # 服务器状态
exit;                           # 退出
CODE
, 'note' => '密码紧跟 -p 无空格；推荐加 --default-character-set=utf8mb4'],
            ['title' => '数据库管理', 'code' => <<<'CODE'
CREATE DATABASE app
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

DROP DATABASE IF EXISTS old_db;
SHOW CREATE DATABASE app\G
ALTER DATABASE app CHARACTER SET utf8mb4;
CODE
, 'note' => 'utf8mb4 才能存 emoji；utf8(mb3) 会报错或丢字'],
            ['title' => '数据类型速查', 'code' => <<<'CODE'
TINYINT / SMALLINT / INT / BIGINT        # 整数（UNSIGNED 可选）
DECIMAL(10,2)                             # 精确小数（金额必用）
FLOAT / DOUBLE                           # 近似小数
CHAR(32) / VARCHAR(255)                  # 定长/变长字符串
TEXT / MEDIUMTEXT / LONGTEXT              # 长文本
DATE / TIME / DATETIME / TIMESTAMP        # 日期时间
JSON                                      # JSON 文档
ENUM('a','b')                             # 枚举
BLOB                                      # 二进制
CODE
, 'note' => '金额用 DECIMAL，时间用 DATETIME；TIMESTAMP 受时区影响'],
        ]],

        ['name' => '建表与约束（DDL）', 'entries' => [
            ['title' => '建表模板', 'code' => <<<'CODE'
CREATE TABLE users (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(50) NOT NULL UNIQUE,
  email       VARCHAR(100) DEFAULT NULL,
  age         TINYINT UNSIGNED COMMENT '年龄',
  status      ENUM('active','disabled') DEFAULT 'active',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
              ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CODE
, 'note' => 'InnoDB 支持事务与外键；MyISAM 只读场景才考虑'],
            ['title' => '主键 / 外键 / 唯一', 'code' => <<<'CODE'
PRIMARY KEY (id),
CONSTRAINT uq_email UNIQUE (email),
KEY idx_name (username),

CONSTRAINT fk_order_user
  FOREIGN KEY (user_id) REFERENCES users(id)
  ON DELETE CASCADE ON UPDATE RESTRICT
CODE
, 'note' => '外键会让批量写入变慢，互联网大表常用应用层约束'],
            ['title' => '修改表结构', 'code' => <<<'CODE'
ALTER TABLE users
  ADD COLUMN phone VARCHAR(20) NULL AFTER email,
  DROP COLUMN age,
  MODIFY COLUMN email VARCHAR(120) NOT NULL,
  CHANGE COLUMN name username VARCHAR(60),
  ADD INDEX idx_phone (phone),
  DROP INDEX idx_name,
  RENAME TO members;

RENAME TABLE users TO members;
TRUNCATE TABLE logs;      # 清空保留结构，重置自增
DROP TABLE IF EXISTS logs;
CODE
, 'note' => '大表 ALTER 可能锁表很久，用 gh-ost / pt-osc'],
            ['title' => '查看表结构', 'code' => <<<'CODE'
SHOW TABLES;
DESC users;
SHOW CREATE TABLE users\G
SHOW INDEX FROM users;
SHOW TABLE STATUS LIKE 'users'\G
CODE
, 'note' => '\G 竖向显示长内容更清晰'],
        ]],

        ['name' => '增删改（DML）', 'entries' => [
            ['title' => '插入', 'code' => <<<'CODE'
INSERT INTO users (username, email) VALUES ('tom', 'a@b.c');

INSERT INTO users (username, email) VALUES
  ('a', 'a@b.c'), ('b', 'b@b.c');          # 批量

INSERT INTO users SET username = 'tom';   # SET 语法

INSERT INTO users (id, name) SELECT id, name FROM old_users;
CODE
, 'note' => '批量插入一次几百条性能远好于循环单条'],
            ['title' => '存在即更新（UPSERT）', 'code' => <<<'CODE'
INSERT INTO stats (k, n) VALUES ('pv', 1)
ON DUPLICATE KEY UPDATE n = n + 1;

REPLACE INTO stats (k, n) VALUES ('pv', 2);
-- REPLACE = 删旧行+插新行，慎用
CODE
, 'note' => 'MariaDB 10.5+ 也支持 INSERT ... ON DUPLICATE KEY'],
            ['title' => '更新与删除', 'code' => <<<'CODE'
UPDATE users SET email = 'x@y.z', age = age + 1 WHERE id = 3;

UPDATE users u JOIN dept d ON u.dept_id = d.id
SET u.title = d.default_title;             # 联表更新

DELETE FROM logs WHERE created_at < '2026-01-01'
LIMIT 1000;                                 # 分批删

DELETE u FROM users u LEFT JOIN orders o ON o.uid = u.id
WHERE o.id IS NULL;                         # 删无订单用户
CODE
, 'note' => 'UPDATE/DELETE 必须带 WHERE，先 SELECT 验证范围'],
        ]],

        ['name' => '查询（DQL）', 'entries' => [
            ['title' => 'SELECT 基础', 'code' => <<<'CODE'
SELECT id, username AS name, age FROM users;

SELECT DISTINCT dept FROM users;        -- 去重
SELECT * FROM users WHERE age BETWEEN 18 AND 30
  AND dept IN ('dev','ops') AND email LIKE '%@corp.com';
SELECT * FROM users WHERE name IS NULL; -- 判空只能用 IS
CODE
, 'note' => '生产代码禁 SELECT *：多耗流量且列序不稳'],
            ['title' => '排序与分页', 'code' => <<<'CODE'
SELECT * FROM users ORDER BY age DESC, id ASC;

SELECT * FROM users ORDER BY id LIMIT 10;          -- 前 10
SELECT * FROM users ORDER BY id LIMIT 20, 10;      -- 跳20取10
SELECT * FROM users ORDER BY id LIMIT 10 OFFSET 20;-- 等价写法
CODE
, 'note' => '深分页慢：WHERE id > 100000 LIMIT 10 游标式更快'],
            ['title' => '模糊匹配', 'code' => <<<'CODE'
WHERE name LIKE 'Tom%'       -- 前缀匹配（可用索引）
WHERE name LIKE '%Tom%'      -- 后缀通配（索引失效）
WHERE name LIKE '_om'        -- 单字通配
WHERE content RLIKE '^\\d{3}' -- 扩展正则
WHERE JSON_EXTRACT(meta, '$.age') > 18
CODE
, 'note' => '%前置会让索引失效，全量扫描'],
            ['title' => '聚合与分组', 'code' => <<<'CODE'
SELECT dept, COUNT(*) AS total,
       AVG(age) avg_age, MAX(salary), MIN(salary), SUM(salary)
FROM users
GROUP BY dept
HAVING total > 5          -- 分组后过滤
ORDER BY total DESC
LIMIT 10;
CODE
, 'note' => 'WHERE 过滤行、HAVING 过滤组；ONLY_FULL_GROUP_BY 需列全在组内'],
            ['title' => 'CASE 分支', 'code' => <<<'CODE'
SELECT id,
  CASE
    WHEN score >= 90 THEN 'A'
    WHEN score >= 60 THEN 'B'
    ELSE 'C'
  END AS grade
FROM scores;

UPDATE u SET level = CASE WHEN n>10 THEN 'vip' ELSE 'normal' END;
CODE
, 'note' => 'CASE 可用在 SELECT/ORDER BY/WHERE 中'],
            ['title' => '窗口函数', 'code' => <<<'CODE'
SELECT name, dept, salary,
  ROW_NUMBER() OVER (PARTITION BY dept ORDER BY salary DESC) AS rn,
  RANK()     OVER (ORDER BY salary DESC) AS rnk,
  AVG(salary) OVER (PARTITION BY dept) AS dept_avg
FROM emp;

-- 每组取前 2 名
SELECT * FROM (
  SELECT *, ROW_NUMBER() OVER (PARTITION BY dept ORDER BY salary DESC) rn
  FROM emp
) t WHERE rn <= 2;
CODE
, 'note' => 'MySQL 8.0+ / MariaDB 10.2+ 支持；每组前 N 就这么写'],
        ]],

        ['name' => '多表 JOIN', 'entries' => [
            ['title' => 'JOIN 类型', 'code' => <<<'CODE'
SELECT u.name, o.no
FROM users u
INNER JOIN orders o ON o.user_id = u.id;      -- 交集

SELECT u.name, o.no
FROM users u
LEFT JOIN orders o ON o.user_id = u.id;       -- 左全+右匹配

RIGHT JOIN / FULL JOIN(MariaDB 无,用 UNION)/ CROSS JOIN
CODE
, 'note' => 'LEFT JOIN 后筛右表空列放 ON 外的 WHERE'],
            ['title' => '子查询', 'code' => <<<'CODE'
SELECT name FROM users
WHERE id IN (SELECT user_id FROM orders WHERE amount > 100);

SELECT u.name,
  (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS cnt
FROM users u;

SELECT * FROM emp
WHERE salary > (SELECT AVG(salary) FROM emp);
CODE
, 'note' => '相关子查询行数多时慢，考虑改 JOIN'],
            ['title' => 'UNION 合并结果', 'code' => <<<'CODE'
SELECT name, 'type1' FROM a
UNION ALL               -- 不去重，快
SELECT name, 'type2' FROM b;

SELECT name FROM a UNION SELECT name FROM b;  -- 去重
CODE
, 'note' => '列数类型需一致；UNION ALL 性能更好'],
        ]],

        ['name' => '函数', 'entries' => [
            ['title' => '字符串函数', 'code' => <<<'CODE'
CONCAT('a', 'b')            # ab（null 传染）
CONCAT_WS('-', 'a','b')     # a-b（跳过 null）
LENGTH('中') / CHAR_LENGTH('中')   # 3字节 / 1字符
UPPER / LOWER / TRIM / LTRIM / RTRIM
SUBSTRING('abcdef', 2, 3)   # bcd
REPLACE(name, 'a', 'b')
LEFT('abcde', 3) / RIGHT('abc', 2)
LOCATE('b', 'abc')          # 2（找不到 0）
LPAD(id, 4, '0')            # 0007
FIELD / FIND_IN_SET('a', csv)
CODE
, 'note' => '中文长度用 CHAR_LENGTH；LENGTH 是字节数'],
            ['title' => '日期函数', 'code' => <<<'CODE'
NOW() / CURDATE() / CURTIME()
DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i')
STR_TO_DATE('2026-11-16', '%Y-%m-%d')
DATEDIFF(d1, d2)                    # 天差
DATE_ADD(NOW(), INTERVAL 1 DAY)
DATE_SUB(NOW(), INTERVAL 30 MINUTE)
YEAR(d) / MONTH(d) / DAY(d) / HOUR(d)
UNIX_TIMESTAMP(NOW()) / FROM_UNIXTIME(ts)
LAST_DAY(NOW())                    # 月末日
CODE
, 'note' => '%H 24小时、%h 12小时、%i 分钟、%s 秒'],
            ['title' => '数值与流程函数', 'code' => <<<'CODE'
ROUND(3.567, 2)          # 3.57
CEIL(3.1) / FLOOR(3.9)   # 4 / 3
ABS(-2) MOD(7,3)         # 2 1
RAND()                   # 0~1 随机
IFNULL(x, 0)             # null 取默认
NULLIF(a, b)             # 相等返 null
IF(cond, x, y)           # 类三元
GREATEST(1,2,3) / LEAST(1,2,3)
CODE
, 'note' => '任何值与 NULL 比较都为 NULL，聚合函数除外'],
            ['title' => 'JSON 函数', 'code' => <<<'CODE'
JSON_EXTRACT(meta, '$.name')           -- 取值
meta->>'$.name'                          -- 取值(文本简写)
JSON_UNQUOTE(JSON_EXTRACT(meta,'$.n'))  -- 去引号
JSON_OBJECT('a',1,'b',2)                -- 构造
JSON_ARRAY(1,2,3)
JSON_VALID('{"x":1}')                    -- 校验
JSON_CONTAINS(meta, '2', '$.tags')
CODE
, 'note' => 'MySQL5.7+/MariaDB10.2+ 支持 JSON 类型'],
        ]],

        ['name' => '索引与性能', 'entries' => [
            ['title' => '索引操作', 'code' => <<<'CODE'
CREATE INDEX idx_name ON users(username);
CREATE UNIQUE INDEX uq_email ON users(email);
ALTER TABLE users ADD FULLTEXT INDEX ft_body(body); -- 全文(MariaDB 中文差)
CREATE INDEX idx_dept_age ON users(dept, age);      -- 联合索引
SHOW INDEX FROM users;
DROP INDEX idx_name ON users;
CODE
, 'note' => '联合索引最左前缀原则：(dept,age) 能服务 dept 或 dept+age 查询'],
            ['title' => 'EXPLAIN 分析', 'code' => <<<'CODE'
EXPLAIN SELECT * FROM users WHERE username = 'tom'\G

-- 重点列：
type: system > const > eq_ref > ref > range
      > index > ALL(全表扫描,坏)
key:  实际用到的索引
rows: 预估扫描行数
Extra: Using index(好) / Using filesort(排序) / Using temporary(临时表,坏)
CODE
, 'note' => '上线前所有核心 SQL 过一遍 EXPLAIN'],
            ['title' => '索引失效的常见写法', 'code' => <<<'CODE'
-- 以下会让索引失效或退化：
WHERE YEAR(created_at) = 2026     -- 函数包裹列
WHERE name LIKE '%Tom%'           -- 前导通配
WHERE age + 1 = 18                -- 参与运算
WHERE dept = 'a' OR other = 'b'   -- 非 or 同索引列
隐式类型转换: varchar 列 = 数字
CODE
, 'note' => "改成范围条件：created_at >= 2026-01-01 且小于下一年"],
        ]],

        ['name' => '事务与锁', 'entries' => [
            ['title' => '事务语法', 'code' => <<<'CODE'
START TRANSACTION;   -- 或 BEGIN
UPDATE acc SET money = money - 100 WHERE id = 1;
UPDATE acc SET money = money + 100 WHERE id = 2;
COMMIT;              -- 提交
-- ROLLBACK;         -- 回滚

SET autocommit = 0;  -- 关自动提交(会话级)
SAVEPOINT sp1;
ROLLBACK TO sp1;
CODE
, 'note' => 'DDL(ALTER 等)会隐式提交，无法回滚'],
            ['title' => '隔离级别', 'code' => <<<'CODE'
READ UNCOMMITTED    -- 脏读
READ COMMITTED      -- 防脏读（不可重复读）
REPEATABLE READ     -- 默认，防不可重复读
SERIALIZABLE        -- 串行化，最安全最慢

SELECT @@transaction_isolation;
SET SESSION transaction_isolation
    = 'READ-COMMITTED';
CODE
, 'note' => 'MySQL 可重复读用 MVCC 实现，读不加锁'],
            ['title' => '行锁与死锁', 'code' => <<<'CODE'
-- InnoDB 行锁加在索引上
SELECT ... FOR UPDATE;      -- 排他锁(写)
SELECT ... LOCK IN SHARE MODE;  -- 共享锁(读)
SELECT ... FOR UPDATE SKIP LOCKED;  -- 队列抢任务

SHOW ENGINE INNODB STATUS\G   -- 查最近死锁
SET innodb_lock_wait_timeout = 5;
CODE
, 'note' => '任务队列场景用 SKIP LOCKED 避免互相等待'],
        ]],

        ['name' => '视图 / 存储过程 / 触发器', 'entries' => [
            ['title' => '视图', 'code' => <<<'CODE'
CREATE VIEW v_active AS
SELECT id, name FROM users WHERE status = 'active';

SELECT * FROM v_active;
DROP VIEW IF EXISTS v_active;

CREATE OR REPLACE VIEW v_active AS SELECT ...;
CODE
, 'note' => '简单视图可更新；复杂视图只读'],
            ['title' => '存储过程（简）', 'code' => <<<'CODE'
DELIMITER $$
CREATE PROCEDURE add_user(IN p_name VARCHAR(50), OUT p_id BIGINT)
BEGIN
  INSERT INTO users (username) VALUES (p_name);
  SET p_id = LAST_INSERT_ID();
END$$
DELIMITER ;

CALL add_user('tom', @id);
SELECT @id;
DROP PROCEDURE add_user;
CODE
, 'note' => '逻辑复杂、跨多语句才用；日常逻辑放应用层更好维护'],
            ['title' => '触发器', 'code' => <<<'CODE'
CREATE TRIGGER trg_log
AFTER INSERT ON orders
FOR EACH ROW
INSERT INTO order_log (oid, at) VALUES (NEW.id, NOW());

-- AFTER UPDATE 可用 NEW 列与 OLD 列对比
DROP TRIGGER trg_log;
CODE
, 'note' => '触发器隐性执行难排查，能不用则不用'],
        ]],

        ['name' => '用户与备份运维', 'entries' => [
            ['title' => '用户授权', 'code' => <<<'CODE'
CREATE USER 'app'@'%' IDENTIFIED BY 'pass@123';
ALTER USER 'app'@'%' IDENTIFIED BY 'newpass';

GRANT SELECT, INSERT, UPDATE ON appdb.* TO 'app'@'%';
GRANT ALL PRIVILEGES ON appdb.* TO 'app'@'%';
REVOKE DELETE ON appdb.* FROM 'app'@'%';
FLUSH PRIVILEGES;
SHOW GRANTS FOR 'app'@'%';
DROP USER 'app'@'%';
CODE
, 'note' => '应用账号最小权限；root 禁止 % 全网开放'],
            ['title' => '备份恢复', 'code' => <<<'CODE'
mysqldump -u root -p --single-transaction appdb > app.sql
mysqldump -u root -p --all-databases > all.sql
mysqldump -u root -p --no-data appdb > schema.sql

mysql -u root -p appdb < app.sql          # 恢复

-- 物理快照与增量
mariabackup --backup --target-dir=/bak
CODE
, 'note' => '--single-transaction 备份时不锁表（InnoDB）'],
            ['title' => '常用运维命令', 'code' => <<<'CODE'
SHOW PROCESSLIST;                 -- 在跑的连接
KILL 1234;                        -- 杀连接
SHOW VARIABLES LIKE 'max_%';       -- 配置项
SET GLOBAL wait_timeout = 28800;
SHOW STATUS LIKE 'Threads%';
SHOW ENGINE INNODB STATUS\G        -- InnoDB 内情
SELECT NOW(), @@version;
SOURCE /path/init.sql;             -- 执行脚本
CODE
, 'note' => '慢查询排查先看 PROCESSLIST 与慢日志'],
            ['title' => '导入导出数据', 'code' => <<<'CODE'
LOAD DATA INFILE '/tmp/a.csv'
INTO TABLE t FIELDS TERMINATED BY ','
ENCLOSED BY '"' LINES TERMINATED BY '\n'
IGNORE 1 LINES;

SELECT * FROM t
INTO OUTFILE '/tmp/out.csv'
FIELDS TERMINATED BY ',';
CODE
, 'note' => '需要 secure_file_priv 允许的目录；CSV 权限注意安全'],
        ]],

    ],
];
