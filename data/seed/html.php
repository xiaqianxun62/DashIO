<?php
/* HTML 速查种子 */
return [
    'slug' => 'html',
    'name' => 'HTML',
    'category' => '前端',
    'icon' => '🌐',
    'color' => '#E44D26',
    'description' => 'HTML5 语义化标签、表单、媒体与常用属性速查',
    'sort' => 10,
    'categories' => [

        ['name' => '文档结构', 'entries' => [
            ['title' => '标准文档骨架', 'code' => <<<'CODE'
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>页面标题</title>
</head>
<body>
  <!-- 内容 -->
</body>
</html>
CODE
, 'note' => 'lang 帮助搜索引擎与读屏器；viewport 移动端必备'],
            ['title' => '常用 meta 标签', 'code' => <<<'CODE'
<meta name="description" content="页面描述，SEO 摘要">
<meta name="keywords" content="关键词列表">
<meta name="author" content="作者">
<meta name="robots" content="noindex, nofollow">
<meta http-equiv="refresh" content="5;url=/login">
<meta name="theme-color" content="#4f6ef7">
CODE
, 'note' => 'description 对搜索结果摘要仍有价值'],
            ['title' => '资源引入', 'code' => <<<'CODE'
<link rel="icon" href="favicon.ico">
<link rel="stylesheet" href="style.css">
<link rel="preload" href="font.woff2" as="font" crossorigin>
<script src="app.js" defer></script>
<script src="analytics.js" async></script>
CODE
, 'note' => 'defer：DOM 解析完执行；async：下载即执行不阻塞解析'],
            ['title' => 'Open Graph 分享卡片', 'code' => <<<'CODE'
<meta property="og:title" content="标题">
<meta property="og:description" content="描述">
<meta property="og:image" content="https://example.com/pic.jpg">
<meta property="og:url" content="https://example.com/page">
<meta property="og:type" content="article">
CODE
, 'note' => '微信/微博/Twitter 抓取分享卡片用'],
        ]],

        ['name' => '语义化布局', 'entries' => [
            ['title' => '常用布局标签', 'code' => <<<'CODE'
<header>页头：LOGO、导航</header>
<nav>导航容器</nav>
<main>页面主体（每页唯一）</main>
<aside>侧边栏</aside>
<footer>页脚</footer>
CODE
, 'note' => 'main 不能嵌套，一篇文档只出现一次'],
            ['title' => '内容分区', 'code' => <<<'CODE'
<section>  <h2>章节标题</h2> ... </section>
<article> <h2>文章标题</h2> ... </article>
<hgroup><h1>主标题</h1><p>副标题</p></hgroup>
CODE
, 'note' => 'article 独立成篇（博客/评论）；section 主题分节'],
            ['title' => '时间与地址', 'code' => <<<'CODE'
<time datetime="2026-11-16T10:30">11月16日</time>
<address>contact@example.com</address>
CODE
, 'note' => 'datetime 供机器解析，用于 SEO 与日历'],
        ]],

        ['name' => '文本内容', 'entries' => [
            ['title' => '标题与段落', 'code' => <<<'CODE'
<h1>一级标题</h1> ... <h6>六级标题</h6>
<p>段落文本</p>
<br>        <!-- 换行 -->
<hr>        <!-- 主题分隔 -->
<p>行内<em>强调</em>与<strong>重要</strong></p>
CODE
, 'note' => '一个页面只应有一个 h1；em 斜体强调、strong 加粗重要'],
            ['title' => '引用与代码', 'code' => <<<'CODE'
<blockquote cite="https://example.com">长引用</blockquote>
<q>行内引用</q>
<cite>作品名</cite>
<pre><code>预格式化代码</code></pre>
<code>行内代码</code>
<kbd>Ctrl</kbd> + <kbd>C</kbd>
<samp>程序输出</samp>
<var>变量</var>
CODE
, 'note' => 'pre 保留空白换行，配 CSS 设置等宽字体'],
            ['title' => '列表', 'code' => <<<'CODE'
<ul>            <!-- 无序列表 -->
  <li>项目</li>
</ul>
<ol start="3" type="a">   <!-- 有序列表 -->
  <li>有序项</li>
</ol>
<dl>            <!-- 描述列表 -->
  <dt>术语</dt>
  <dd>描述</dd>
</dl>
CODE
, 'note' => '导航菜单常用 ul>li>a 结构'],
            ['title' => '标记与注音', 'code' => <<<'CODE'
<mark>高亮标记</mark>
<small>小号注释</small>
<s>已删除内容</s>
<u>非名称性注解</u>
<del>旧文本</del> <ins>新文本</ins>
<sub>下标</sub> <sup>上标</sup>
<ruby>汉<rt>hàn</rt></ruby>
CODE
, 'note' => 'mark 默认黄色背景，可用 CSS 覆盖'],
        ]],

        ['name' => '链接与嵌入', 'entries' => [
            ['title' => '超链接', 'code' => <<<'CODE'
<a href="https://example.com">外部链接</a>
<a href="/about" target="_blank" rel="noopener">新窗口打开</a>
<a href="#section-id">页内锚点</a>
<a href="mailto:someone@example.com">发邮件</a>
<a href="tel:+8613800000000">打电话</a>
<a href="/files/doc.pdf" download>下载文件</a>
CODE
, 'note' => '外链 target=_blank 时务必加 rel="noopener" 防钓鱼'],
            ['title' => '图片', 'code' => <<<'CODE'
<img src="photo.jpg" alt="描述文本" width="600" height="400"
     loading="lazy" decoding="async">

<picture>
  <source media="(min-width: 800px)" srcset="big.jpg">
  <source type="image/webp" srcset="photo.webp">
  <img src="fallback.jpg" alt="兼容兜底">
</picture>
CODE
, 'note' => 'alt 缺失影响无障碍与 SEO；loading=lazy 原生懒加载'],
            ['title' => '视频与音频', 'code' => <<<'CODE'
<video controls width="640" poster="cover.jpg"
       autoplay muted loop preload="metadata">
  <source src="movie.mp4" type="video/mp4">
  浏览器不支持 video 标签
</video>

<audio controls src="music.mp3" preload="none"></audio>
CODE
, 'note' => 'autoplay 必须搭配 muted 才能自动播放'],
            ['title' => 'iframe 嵌入', 'code' => <<<'CODE'
<iframe src="https://example.com"
        width="600" height="400"
        title="嵌入内容说明"
        loading="lazy"
        referrerpolicy="no-referrer"
        sandbox="allow-scripts"></iframe>
CODE
, 'note' => 'sandbox 限制嵌入页权限，内网嵌入第三方页面时建议使用'],
        ]],

        ['name' => '表格', 'entries' => [
            ['title' => '基础表格', 'code' => <<<'CODE'
<table>
  <caption>2026 Q4 销售表</caption>
  <thead>
    <tr><th scope="col">月份</th><th scope="col">销售额</th></tr>
  </thead>
  <tbody>
    <tr><td>10月</td><td>12000</td></tr>
    <tr><td>11月</td><td>15000</td></tr>
  </tbody>
  <tfoot>
    <tr><th>合计</th><td>27000</td></tr>
  </tfoot>
</table>
CODE
, 'note' => 'scope 帮助读屏器理解表头方向'],
            ['title' => '单元格合并', 'code' => <<<'CODE'
<tr>
  <td colspan="2">跨两列</td>
</tr>
<tr>
  <td rowspan="3">跨三行</td>
  <td>值</td>
</tr>
CODE
, 'note' => '合并后对应行/列的单元格需省略'],
        ]],

        ['name' => '表单', 'entries' => [
            ['title' => 'form 元素', 'code' => <<<'CODE'
<form action="/submit" method="post"
      enctype="multipart/form-data"
      target="_self" novalidate>
  <!-- 控件们 -->
</form>
CODE
, 'note' => '默认 enctype 为 urlencoded；含文件上传必须 multipart/form-data'],
            ['title' => 'input 类型', 'code' => <<<'CODE'
<input type="text|password|email|tel|url|number">
<input type="search|date|time|datetime-local|month|week">
<input type="range|color|checkbox|radio|file|hidden">
<input type="submit|reset|button|image">
<input type="button" value="普通按钮">
CODE
, 'note' => 'datetime-local 不含时区，移动端体验好'],
            ['title' => 'input 常用属性', 'code' => <<<'CODE'
<input name="q" placeholder="提示" required
       minlength="2" maxlength="20"
       pattern="[a-z]{3,}" title="3位以上小写字母"
       min="1" max="100" step="0.5"
       value="默认值" autocomplete="off" readonly disabled>
CODE
, 'note' => 'required/minlength/pattern 触发表单原生校验'],
            ['title' => '下拉与文本域', 'code' => <<<'CODE'
<label for="city">城市</label>
<select id="city" name="city">
  <optgroup label="华东">
    <option value="sh">上海</option>
    <option value="hz" selected>杭州</option>
  </optgroup>
</select>

<input list="cities" name="city2">
<datalist id="cities">
  <option value="北京"><option value="上海">
</datalist>

<textarea name="remark" rows="4" cols="30"
          maxlength="200"></textarea>
CODE
, 'note' => 'datalist 提供可输入的自动补全下拉'],
            ['title' => '单选与复选', 'code' => <<<'CODE'
<fieldset>
  <legend>性别</legend>
  <label><input type="radio" name="gender" value="m" checked> 男</label>
  <label><input type="radio" name="gender" value="f"> 女</label>
</fieldset>

<label><input type="checkbox" name="tags" value="php"> PHP</label>
CODE
, 'note' => 'name 相同为单选组；checked/disabled 布尔属性'],
            ['title' => '原生表单验证提示', 'code' => <<<'CODE'
<input required oninvalid="this.setCustomValidity('该项必填')"
       oninput="this.setCustomValidity('')">

<form novalidate>  <!-- 关闭原生校验 -->
CODE
, 'note' => 'setCustomValidity 自定义提示，input 时记得清空'],
        ]],

        ['name' => '全局属性', 'entries' => [
            ['title' => '通用属性', 'code' => <<<'CODE'
<div id="unique" class="a b" style="color: red"
     title="悬停提示" lang="zh" dir="ltr"
     tabindex="0" hidden></div>
CODE
, 'note' => 'tabindex=0 可聚焦按文档序；正值跳序使用需谨慎'],
            ['title' => 'data-* 自定义数据', 'code' => <<<'CODE'
<li data-id="42" data-role="admin">Tom</li>

<script>
  const li = document.querySelector('li');
  li.dataset.id;        // '42'
  li.dataset.role;      // 'admin'
</script>
CODE
, 'note' => 'dataset 自动把 data-user-name 映射为 dataset.userName'],
            ['title' => '可编辑与拖放', 'code' => <<<'CODE'
<div contenteditable="true" spellcheck="false">可直接编辑</div>

<div draggable="true" ondragstart="drag(event)">拖我</div>
<div ondragover="allowDrop(event)" ondrop="drop(event)"></div>
CODE
, 'note' => 'contenteditable 常配合 localStorage 做轻量记事本'],
        ]],

        ['name' => '交互与模板', 'entries' => [
            ['title' => 'details 折叠面板', 'code' => <<<'CODE'
<details open>
  <summary>点击展开标题</summary>
  <p>折叠内容默认隐藏，open 属性展开</p>
</details>
CODE
, 'note' => '原生手风琴零 JS；可用 [open] 选择器做动画'],
            ['title' => 'dialog 弹窗', 'code' => <<<'CODE'
<dialog id="dlg">
  <p>模态内容</p>
  <button onclick="dlg.close()">关闭</button>
</dialog>
<button onclick="dlg.showModal()">打开</button>
CODE
, 'note' => 'showModal 自带遮罩与焦点圈定；close(reason) 可传返回值'],
            ['title' => 'template 模板', 'code' => <<<'CODE'
<template id="rowTpl">
  <tr><td class="name"></td><td class="age"></td></tr>
</template>

<script>
  const tpl = document.getElementById('rowTpl');
  const row = tpl.content.cloneNode(true);
  row.querySelector('.name').textContent = 'Tom';
  document.querySelector('tbody').append(row);
</script>
CODE
, 'note' => 'template 内容不渲染，clone 后再插入 DOM'],
            ['title' => '进度与计量', 'code' => <<<'CODE'
<progress value="70" max="100"></progress>
<meter value="0.6" high="0.8" low="0.2" optimum="1">60%</meter>
CODE
, 'note' => 'meter 阈值变色（绿/黄/红）适合磁盘用量等'],
            ['title' => '常用字符实体', 'code' => <<<'CODE'
&nbsp;      空格（不折叠）
&lt; &gt;     < >
&amp;        &
&quot; &#39;  " '
&copy; &reg; &trade;
&hellip; &mdash; &rarr;
CODE
, 'note' => '输出用户内容时 & < > 必须转义'],
        ]],

    ],
];
