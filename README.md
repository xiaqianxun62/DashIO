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

**音乐（全站）**

- 底部常驻迷你播放器：播放 / 暂停、上一首 / 下一首、进度拖动、音量与静音，切页面记住曲目与进度
- `music.php` 歌单页：点任意一行即播，支持本地文件与外链混排
- 管理员：页面上传音频（自动读文件标签填歌名 / 歌手，读不到再退文件名）、粘外链加曲目、把 `assets/music/` 里的音频一键登记、改名 / 演唱者 / 排序、移除

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
├── music.php            # 歌单页：曲目列表 + 管理员加曲目 / 登记 / 编辑 / 删除
├── api.php              # JSON 接口：搜索联想 + 条目/分类写操作（需管理员 + CSRF）
├── upload.php           # JSON 接口：音乐分片上传（init / chunk / finish / abort）
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
│   ├── music.php        # 曲目表读写 + 音源校验 + 扫 assets/music（懒建表，兼容老站点）
│   │                    # 含分片上传层：meta / part 落盘、流式合并、超时清理
│   ├── audio_tags.php   # 读音频标签（ID3 / FLAC / OGG / M4A ilst / WAV INFO），零依赖
│   └── seed_loader.php  # 种子装载：fresh 清库重导 / append 增量合并
├── assets/
│   ├── css/style.css    # 全站样式（单一主色、5 档字号、卡片不嵌套、浅色代码块）
│   ├── js/app.js        # 主题、Toast、复制、搜索联想、过滤、编辑弹窗
│   ├── js/player.js     # 全站迷你播放器（单 audio 实例 + sessionStorage 续播）
│   ├── js/upload.js     # 大文件分片上传（切片、逐片 POST、进度条、失败重试）
│   ├── js/highlight.js  # 轻量代码高亮（配色由 CSS token 控制）
│   ├── music/           # 本地音频目录（支持页面上传；含 .htaccess 禁止执行脚本）
│   │   └── .tmp/        # 分片临时目录（meta.json + p00000.part…），.htaccess 全拒
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
| `tracks` | `title`、`artist`、`src`(唯一)、`sort` | 歌单曲目，`src` 为 http(s) 外链或 `assets/music/文件名` |

`tracks` 表不在 `install.php` 里建，而是首次访问音乐相关页面时由 `lib/music.php` 的 `music_ensure_table()` 自动创建（MySQL / SQLite 两套 DDL），老站点不用重装也能用。

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

## 音乐播放

**曲目从哪来**（两路合并，顺序按 `sort`、`id`）：

1. `assets/music/` 目录里扫到的音频 → 没登记过的会以「未入库」临时条目出现（能播，不能改名）
2. `tracks` 表里登记的条目 → 外链 URL 或已登记的本地文件

管理员在 `music.php` 上：

- 「上传音频」框直接选文件上传，落地到 `assets/music/` 并自动加进歌单
  - 歌名 / 歌手留空时自动读文件标签，优先级：表单填的 → 标签 → 文件名
  - 读的标签：mp3 的 ID3v2（TIT2 / TPE1，支持 UTF-8 与 UTF-16）和 ID3v1、flac / ogg / oga 的 VORBIS_COMMENT、m4a 的 ilst（©nam / ©ART）、wav 的 RIFF INFO（INAM / IART）；webm 不解析
  - 上限取三者最小：10MB、`upload_max_filesize`、`post_max_size`，页面会把实际值显示出来
  - 三重校验：扩展名白名单 → 真实 MIME（`finfo`，允许 `application/octet-stream` 兜底）→ 内容里不能出现 `<?php` / `<?=`
  - 文件名清洗成中英文 / 数字 / 点 / 下划线 / 连字符；重名自动存成 `xxx-2.mp3`，不覆盖
  - `assets/music/.htaccess` 另外关掉该目录的脚本执行与目录列表
- **大文件走分片上传**（超过 **10MB** 自动启用，无需手动切换；每片也是 10MB，主机单请求上限更小时按 `min(10MB, 单请求上限-64KB)` 走）：
  - 前端（`assets/js/upload.js`）按页面 `data-chunk-size` 切片，逐片 POST 到 `upload.php?action=chunk`，每片失败重试 2 次，进度条显示「已传 X / Y MB（百分比）· 正在上传第 i/n 片」；上传中禁用表单与离开页面
  - 单片上限取 `min(10MB, post_max_size-64KB)`（留 64KB 给 multipart 头，所以在 10MB 主机上显示 9.9MB），合并后 ≤ 50MB
  - 没超过一片的文件仍走普通表单整包提交，行为不变
  - 服务端把每片写成 `assets/music/.tmp/<会话>/p00000.part`，`finish` 时按序 256KB 流式拼成 `merged.<ext>`，**总字节数必须等于 init 声明的 size**，再走与单文件完全相同的校验（扩展名 / MIME / PHP 标签）、改名、入库
  - 合并后就是普通文件，播放器照旧按 URL 播（进度条拖动、切歌、跨页续播都正常）；不做 MediaSource 边传边听
  - 上限：单片 ≤ `min(8MB, post_max_size-64KB)`，合并后 ≤ 50MB，分片数 1–4096；临时目录 24 小时未更新会被下一次 init 顺手清掉，失败/取消走 `abort` 立即删
  - 接口需管理员登录 + `X-CSRF-Token`，返回统一 JSON `{ok:true|false}`；`.tmp` 目录的 `.htaccess` 直接拒绝外部访问
- 「添加曲目」填歌名 / 演唱者 / 音源即可（不传文件，用于外链或已存在的本地文件），音源写 `https://…/song.mp3` 或 `assets/music/song.mp3`
  - 只有 `http(s)://` 外链，或真实存在于 `assets/music/` 下的相对路径才算合法，其他（`javascript:`、随便写的文件名）会被拒绝
  - 同一个 `src` 不能重复添加
- 目录里出现新文件后，页面会提示「还有 N 个音频文件没登记」，点一下批量入库，之后才能改名 / 排序 / 加演唱者
- 行内铅笔改歌名、演唱者、排序；垃圾桶从歌单移除（磁盘文件不动）

**播放器**：页面底部常驻，全站只有一条 bar；`lib/helpers.php` 的 `page_footer()` 在歌单非空时输出播放器与曲目 JSON（`id="tqrTracks"`），并载入 `assets/js/player.js`。登录页调用 `page_footer('', false)`，不显示播放器。

**跨页续播**：`sessionStorage['tqr-player']` 存 `{i: 曲目下标, t: 秒, p: 是否在播, v: 音量, m: 是否静音}`。新页面恢复曲目与进度；若之前是播放态则尝试续播，被浏览器自动播放策略拦下时，等你第一次点击 / 按键再接着播（这是浏览器限制，纯 PHP 多页站点做不到无缝衔接）。

**懒加载音频**：播放器 `preload='none'`，进页面只显示曲名（数据来自页面里的 JSON），**不设置 `audio.src`**，所以首屏不会出现 `media` 类型的请求；只有点播放 / 点歌单行 / 播放器 `TQRPlayer.play()` 时才挂载 src 并发起请求。没有曲目的页面整段播放器与 `<script id="tqrTracks">` 都不输出。

**加歌的方式**：页面直接上传，或 FTP / 面板传进 `assets/music/`。支持 mp3 / m4a / aac / ogg / oga / wav / flac / webm，文件名即歌名（`_`、`-` 转空格）。免费主机一般限 10MB / 单文件，超出后 PHP 丢整个请求体，页面会提示换小文件。

**脚本接口**：`window.TQRPlayer = { play(i), pause(), next(), prev() }`，需要从别的页面控制播放时可直接调用。

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

**分片上传（`upload.php`，全部 POST + `X-CSRF-Token`）**

| action | 字段 | 说明 |
| --- | --- | --- |
| `init` | `name`、`size`、`chunks` | 建临时会话，返回 `{ok, id, chunk_size, chunks}` |
| `chunk` | `id`、`index`、`blob`（multipart） | 存单片，返回 `{ok, index, bytes}` |
| `finish` | `id`、`title`、`artist` | 合并 + 校验 + 入库，返回 `{ok, url, id, title, artist}` |
| `abort` | `id` | 丢弃已传分片并删临时目录 |

失败统一 `{"ok":false,"error":"…"}` + HTTP 400；未登录或 CSRF 不符返回 403。

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
- 音乐：单文件上传受主机 `upload_max_filesize` / `post_max_size` 限制，超出时页面提示换小文件；**需要更大文件就走分片上传**（自动触发，上限 50MB，且每片仍受 `post_max_size` 约束）；合并完成后不会被当成流式播放，必须等整首传完才能播；webm 不解析标签（歌名退到文件名）；跨页续播受浏览器自动播放策略限制，首次进页面可能需要点一下才开始播；外链音源是否能播取决于对方站点允许跨域直链
- 分片上传依赖 `assets/music/.tmp/` 可写；有些 FTP / 面板上传隐藏目录会失败，部署后确认 `.tmp` 与其中的 `.htaccess` 都在（否则分片能传但会被外部扫到）

---

## 变更记录

- **2026-11-24 播放器懒加载**：`assets/js/player.js` 改为 `preload='none'` + 点播放才挂 `src`，首屏不再发 `media` 请求（此前一进首页就会用 preload=metadata 拉音频，线上实测一条 557KB / 1.34s 的 206 请求）；曲名仍从页面 JSON 直接显示，续播行为不变
- **2026-11-24 分片改为 10MB/片**：`TQR_CHUNK_SIZE` 由 2MB 提到 10MB，`music_chunk_limit()` 去掉 8MB 旧上限，改为 `min(10MB, 主机单请求上限-64KB)`；阈值与单片一致，超过 10MB 才切分片，10MB 以内整包直传。`TQR_UPLOAD_MAX` 同步提到 50MB，避免出现「10 MB 上限」的误导提示
- **2026-11-24 音乐分片上传**：新增 `upload.php`（init / chunk / finish / abort 四个 JSON 动作，管理员 + `X-CSRF-Token`）与 `assets/js/upload.js`（按 `data-chunk-size` 切片、逐片重试 2 次、进度条、离开拦截），`lib/music.php` 增加分片层（`music_chunk_*`）：分片落 `assets/music/.tmp`、256KB 流式合并、字节数校验、24h 超时清理、`.tmp/.htaccess` 全拒；超过单请求上限的大文件现在能传了，合并后按普通文件播放。顺带把 `music_validate_audio_file` / `music_unique_name` / `music_register_file` / `music_upload_message` 从 `music_store_upload` 里抽出来复用，并修了 WAV INFO 块不补对齐字节时 `IART` 读不到的问题
- **2026-11-24 音乐标签识别**：新增 `lib/audio_tags.php`（零依赖纯 PHP 读标签：ID3v2.3/2.4、ID3v1、FLAC / OGG VORBIS_COMMENT、M4A 的 ilst、WAV 的 LIST/INFO），上传时歌名 / 歌手留空即自动识别，批量登记 `import_local` 同样优先用标签；webm 不解析
- **2026-11-24 音乐上传**：`music.php` 增加「上传音频」表单，落地 `assets/music/` 并自动入歌单；扩展名 + 真实 MIME + 内容三重校验，文件名清洗与重名自动编号，上限按 `upload_max_filesize` / `post_max_size` 动态读取并显示；`assets/music/.htaccess` 关闭该目录脚本执行
- **2026-11-24 音乐播放**：新增 `music.php` 歌单页 + 全站底部迷你播放器（`assets/js/player.js`、`lib/music.php`、`tracks` 表懒建），本地 `assets/music/` 与外链两种音源混排，`sessionStorage` 记住曲目与进度；后台「内容导入」按钮组新增「音乐 / 歌单管理」入口；顺手修掉两个图标别名指向不存在的 symbol（`trash-2`、`triangle-alert`）导致的空白图标
- **2026-11-23 视觉改版**：去掉所有渐变与阴影、统一单一主色 `#2563eb`、字号收敛到 13/14/16/20/28 五档、卡片只在确需分组时使用且不嵌套、全站 emoji 图标替换为 Lucide 线性图标、正文默认左对齐；功能与内容零改动，同时修复了导入结果多行被 `flex` 挤成一行、后台 `grid2` 字段错位两处版式问题
- 更早：Markdown 条目渲染与导入闭环、种子增量合并（含「TCP 与 UDP」「WPF」「WinForm」等面试八股）、`admin.php` 正则 `[a-z0-9\-]` 修正（Chrome v-flag 兼容）
