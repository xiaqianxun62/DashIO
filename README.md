# TechQuickRef · 团队技术速查清单

一个纯 PHP（无框架、无 Composer 依赖）的技术速查站点：按「技术栈 → 分类 → 条目」组织内容，条目可包含代码块、备注和参考链接，支持 Markdown 正文、全站搜索、后台可视化维护与 Markdown 批量导入。

线上跑在 InfinityFree 免费主机（Apache + MySQL），因此代码刻意做到：零依赖、单文件即拷即用、`.htaccess` 兜住敏感文件。

---

## 功能一览

**浏览**

- 首页按「分组」展示技术栈卡片，顶部给出技术栈 / 分类 / 条目总量
- 清单页：分类锚点导航、清单内即时过滤、条目卡片（代码块 + 备注 + 参考链接）
- 代码块一键复制、浅色高亮、长行自动折行不出现横向滚动
- 顶栏搜索框带联想下拉（`api.php?action=suggest`），回车进完整搜索页
- 深色 / 浅色主题（localStorage 记住选择）、响应式布局、Lucide 线性图标

**维护（管理员）**

- 后台：新增 / 编辑 / 删除技术栈，新增 / 删除分类，条目在清单页内直接编辑（弹窗）
- 团队用户管理：新增用户、改角色（admin / member）、删除用户
- 站点设置：站点名称、是否开放注册（注册用户默认只读成员）
- 修改自己的密码
- Markdown 导入：粘贴文本直接入库，或扫描 `data/md/*.md` 批量导入；可先「只看解析结果」
- 种子增量导入：只补数据库里缺失的分类与条目，不覆盖已有内容

---

## 环境要求

| 项目 | 要求 |
| --- | --- |
| PHP | ≥ 7.4，建议 8.1+（当前在 8.3 上验证） |
| 扩展 | `PDO` + `pdo_mysql`（生产推荐）或 `pdo_sqlite`（本地试用）；`mbstring`（搜索与联想用到 `mb_*`） |
| 目录权限 | 站点根目录可写（安装器要生成 `config.php`）；SQLite 模式还需 `data/` 可写 |
| Web 服务器 | Apache（`.htaccess` 生效）；Nginx 需自行等价配置禁止访问 `config.php` / `*.sqlite` |

数据库二选一：**MySQL / MariaDB**（推荐）或 **SQLite**（零依赖，数据库就是 `data/quickref.sqlite` 单个文件）。

---

## 目录结构

```
quickref/
├── index.php            # 首页：分组 + 技术栈卡片 + 搜索入口
├── sheet.php            # 清单页：分类、条目、过滤、管理员编辑入口
├── search.php           # 搜索结果页
├── login.php            # 登录 / 注册（注册可按开关关闭）
├── admin.php            # 管理后台：技术栈、用户、站点设置、改密
├── api.php              # JSON 接口：搜索联想 + 条目/分类写操作（需管理员 + CSRF）
├── import-md.php        # Markdown 导入（粘贴 / 文件 / 解析预览）
├── add-seed.php         # 种子增量导入（只补缺失，不覆盖）
├── install.php          # 安装向导（选驱动、建库表、建管理员、导种子）
├── php-check.php        # 部署自检（环境、目录权限、数据库连通性），用完建议删除
├── config.php           # 安装器生成，不入库不应提交（示例见下）
├── .htaccess            # DirectoryIndex / 关目录浏览 / 禁访问配置与 sqlite
├── lib/
│   ├── db.php           # PDO 单例、查询封装、建表 DDL（MySQL / SQLite 两套）
│   ├── helpers.php      # 页面框架、转义、会话、CSRF、设置、统计、图标接入
│   ├── icons.php        # Lucide 图标 sprite + icon() / stack_icon() 映射
│   ├── markdown.php     # md_render / 识别规则 / .md 文档解析
│   ├── Parsedown.php    # Parsedown 1.7.4（单文件，安全模式）
│   ├── importer.php     # Markdown 文档写库（按「分类+标题」判重，可反复导入）
│   └── seed_loader.php  # 种子装载：fresh 清库重导 / append 增量合并
├── assets/
│   ├── css/style.css    # 全站样式（单一主色、5 档字号、卡片不嵌套、浅色代码块）
│   ├── js/app.js        # 主题、Toast、复制、搜索联想、过滤、编辑弹窗
│   ├── js/highlight.js  # 轻量代码高亮（配色由 CSS token 控制）
│   └── favicon.svg
└── data/
    ├── seed/*.php       # 内置种子（11 个技术栈 / 95 个分类 / 410 条条目）
    └── md/*.md          # Markdown 导入扫描目录（只读，读完写库）
```

---

## 安装

1. **上传文件**到站点根目录（`config.php` 若不存在会自动跳 `install.php`）。
2. 访问 `https://你的域名/install.php`，依次填：
   - 运行环境自检（PHP 版本、PDO 驱动、目录可写）
   - 数据库：MySQL / MariaDB（主机、端口、库名、账号、密码）或 SQLite（走 `data/quickref.sqlite`）
   - 站点名称
   - 管理员账号（字母数字下划线 2–50 位）与密码（≥ 6 位）
   - 种子模式：**全新导入**（清空后写入完整种子）/ 追加（只补缺失）
3. 装完即可登录后台。若首页打不开，先访问 `/php-check.php` 判断是「PHP 没解析」还是「代码报错」。

`config.php` 形如（请勿把真实凭据提交到公开仓库）：

```php
<?php
return [
    'driver' => 'mysql',       // mysql | sqlite
    'host'   => '数据库主机',
    'port'   => '3306',
    'dbname' => '数据库名',
    'user'   => '数据库账号',
    'pass'   => '数据库密码',
    // SQLite 模式改为：'driver' => 'sqlite', 'sqlite_path' => __DIR__ . '/data/quickref.sqlite'
];
```

安装完成后建议删除或改名：`install.php`、`php-check.php`、`import-md.php`、`add-seed.php`（它们只用于一次性操作，且会暴露环境信息）。

---

## 数据模型

内容**全部写进数据库**，新增技术栈、分类、条目都不会生成或修改任何 PHP 文件。

| 表 | 关键字段 | 说明 |
| --- | --- | --- |
| `stacks` | `slug`(唯一)、`name`、`grp`(分组)、`icon`、`color`、`description`、`sort` | 一个技术栈 = 一张速查清单 |
| `categories` | `stack_id`、`name`、`sort`，`UNIQUE(stack_id, name)` | 清单内的分类 |
| `entries` | `category_id`、`title`、`code`、`note`、`link`、`sort`、`updated_by`、`updated_at` | 一条速查条目，`code` 可为 Markdown 正文 |
| `users` | `username`(唯一)、`password_hash`、`display_name`、`role` | `role` 为 `admin` / `member` |
| `settings` | `k`、`v` | `site_name`、`allow_register` 等站点配置 |

删除技术栈会级联清掉其下分类与条目；`sort` 由程序按当前最大值 +1 自动维护。

**注意**：重装时选「全新导入」会先执行 `DELETE FROM entries/categories/stacks`，手工添加的内容会被清掉，重装前先备份数据库。

---

## 维护内容

**后台 / 清单页**：新增技术栈时 `slug` 是唯一键（小写字母、数字、连字符，≤ 50 位），重复会报错；分类名在同一技术栈内也不能重名。

**Markdown 导入**（`import-md.php`，仅管理员）：

- 文档约定：`# 技术栈名称` / `## 分类名称` / `### 条目标题` + 正文；`#` 可省略（用文件名或表单里的名称）
- 判重规则：同「分类 + 标题」视为重复，跳过；因此可以反复导入做增量补充
- 代码围栏 ` ``` ` 内的 `##` / `###` 不会被当成标题
- 条目正文的渲染规则：首行 `[md]` 强制 Markdown、首行 `[code]` 强制代码块；不写则自动识别（命中 ≥2 个 Markdown 特征，或整段都是列表行 ≥2 行）

**种子增量导入**（`add-seed.php`，仅管理员）：按 `slug` 判重，新栈整栈插入，已有栈只补缺失的分类与条目，不覆盖现有内容。

---

## 图标与主题

- 所有界面图标来自 Lucide（内置 sprite，`lib/icons.php`），不再使用 emoji 当图标
- 技术栈图标：后台 `icon` 字段填合法 Lucide 名（如 `database`）即直接生效；留空则按 `slug` 自动匹配

  ```
  php→file-code  html→code-xml  css→palette  javascript→braces  mysql→database
  java→coffee    csharp→code    dotnet→box   wpf→app-window     winform→monitor
  interview→target            其他→layers
  ```

- 主题：`localStorage['tqr-theme']` 存 `dark` / `light`，`<head>` 内提前写入属性避免刷新闪白

---

## 接口（`api.php`）

| 方法 | 参数 | 说明 |
| --- | --- | --- |
| GET | `action=suggest&q=关键词` | 搜索联想，返回 JSON（标题 / 技术栈 / 图标 SVG / 跳转地址），最多 8 条 |
| POST | `action=save_entry`（JSON：`id` 可选、`category_id`、`title`、`code`、`note`、`link`） | 新增或更新条目 |
| POST | `action=delete_entry`（JSON：`id`） | 删除条目 |
| POST | `action=delete_category`（JSON：`id`） | 删除分类及其条目 |

写操作必须已登录管理员，并在请求头带 `X-CSRF-Token`（值取自页面里的 `csrf`）。

---

## 安全设计

- 全部 SQL 走 PDO 预处理（`q()` / `q_all()` / `q_row()` / `q_val()`），无字符串拼接
- 密码用 `password_hash` 存储，登录成功 `session_regenerate_id`
- 所有写操作校验 CSRF（表单 `csrf_field()`、接口 `X-CSRF-Token`）
- 输出统一 `e()` 转义；Markdown 走 Parsedown 安全模式，过滤原始 HTML
- `.htaccess`：`DirectoryIndex` 固定 `index.php`、`Options -Indexes`、禁止访问 `config.php` 与 `*.sqlite`

---

## 本地调试

```bash
php -S 127.0.0.1:8000 -t .
```

- 只用 SQLite 试玩：把 `config.php` 的 `driver` 设为 `sqlite`
- 需要 `mbstring` 才能跑搜索 / 联想；若内置服务器报 `mb_strlen` 未定义，启动时加 `-d extension=mbstring`
- 可以先用 `php-check.php` 确认扩展与权限，再走 `install.php`

---

## 已知限制

- 条目一次性全量展示，没有分页；条目特别多（几千条以上）时清单页会较长
- 仅支持单库单站点，没有多租户 / 多语言
- 免费主机上建议用 MySQL；SQLite 依赖 `data/` 可写，换主机或清缓存时注意一起备份

---

## 变更记录

- **2026-11-23 视觉改版**：去掉所有渐变与阴影、统一单一主色 `#2563eb`、字号收敛到 13/14/16/20/28 五档、卡片只在确需分组时使用且不嵌套、全站 emoji 图标替换为 Lucide 线性图标、正文默认左对齐；功能与内容零改动，同时修复了导入结果多行被 `flex` 挤成一行、后台 `grid2` 字段错位两处版式问题
- 更早：Markdown 条目渲染与导入闭环、种子增量合并（含「TCP 与 UDP」「WPF」「WinForm」等面试八股）、`admin.php` 正则 `[a-z0-9\-]` 修正（Chrome v-flag 兼容）
