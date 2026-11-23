<?php
/* CSS 速查种子 */
return [
    'slug' => 'css',
    'name' => 'CSS',
    'category' => '前端',
    'icon' => '🎨',
    'color' => '#2965F1',
    'description' => '选择器、盒模型、Flex / Grid 布局与动画速查',
    'sort' => 11,
    'categories' => [

        ['name' => '选择器', 'entries' => [
            ['title' => '基础选择器', 'code' => <<<'CODE'
p {}              /* 标签 */
#app {}           /* ID */
.card {}          /* 类 */
* {}              /* 通配 */
a, p {}           /* 分组 */
CODE
, 'note' => '单页面避免依赖 id 选择器，复用性差'],
            ['title' => '组合选择器', 'code' => <<<'CODE'
.card p {}        /* 后代（所有层级） */
.card > p {}      /* 直接子代 */
h2 + p {}         /* 相邻兄弟 */
h2 ~ p {}         /* 同级通用兄弟 */
CODE
, 'note' => '> 只选直接子元素，性能与语义都更精确'],
            ['title' => '属性选择器', 'code' => <<<'CODE'
[type="text"] {}            /* 等值 */
[href^="https"] {}          /* 开头 */
[href$=".pdf"] {}           /* 结尾 */
[class*="btn"] {}           /* 包含 */
[lang|="zh"] {}             /* zh 或 zh-* */
CODE
, 'note' => '配合 data-* 可做状态样式：[data-active="on"]'],
            ['title' => '伪类选择器', 'code' => <<<'CODE'
:hover :focus :active
:first-child :last-child :only-child
:nth-child(2)      /* 第2个 */
:nth-child(odd)    /* 奇数行 */
:nth-of-type(3n+1)
:not(.disabled)     /* 取反 */
:empty             /* 无子元素 */
:checked :disabled :required
CODE
, 'note' => 'odd/even 斑马纹表格最常用'],
            ['title' => '伪元素', 'code' => <<<'CODE'
p::first-line {}     /* 首行 */
p::first-letter {}   /* 首字符 */
li::marker {}        /* 列表符号 */
a::before { content: "→"; }
a::after  { content: ""; display: block; }
::selection {}       /* 选中文字样式 */
input::placeholder {}
CODE
, 'note' => '伪元素必须配 content 属性才生效'],
            ['title' => '优先级与覆盖', 'code' => <<<'CODE'
!important > 内联 style > #id > .class > p
同级看顺序：后者覆盖前者

/* 提权技巧 */
.list .item .name {}   /* 累加 class 提高优先级 */
CODE
, 'note' => '!important 谨慎使用，破坏级联难以维护'],
        ]],

        ['name' => '盒模型', 'entries' => [
            ['title' => '盒模型速览', 'code' => <<<'CODE'
.box {
  width: 200px;
  padding: 16px;
  border: 2px solid #ccc;
  margin: 20px;
  box-sizing: border-box;   /* 关键 */
}
CODE
, 'note' => 'border-box 让 width 含 padding/border，所见即所得'],
            ['title' => '全盒模型重置', 'code' => <<<'CODE'
*, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}
CODE
, 'note' => '现代项目标配，避免默认样式差异'],
            ['title' => '边框与圆角', 'code' => <<<'CODE'
.b {
  border: 1px solid #ddd;
  border-radius: 12px;
  border-top-left-radius: 0;
  outline: 2px dashed blue;   /* 不占空间的轮廓 */
  box-shadow: 0 4px 12px rgba(0,0,0,.15);
}
CODE
, 'note' => 'outline 不参与布局，适合聚焦提示'],
            ['title' => '显示与可见性', 'code' => <<<'CODE'
display: block | inline | inline-block | flex | grid | none;
visibility: hidden;   /* 保留占位 */
opacity: 0;           /* 透明仍占位仍可点击 */
overflow: hidden | auto | scroll;
CODE
, 'note' => '隐藏元素用 display:none（不可交互）'],
        ]],

        ['name' => '单位与变量', 'entries' => [
            ['title' => '单位对比', 'code' => <<<'CODE'
px     固定像素
em     相对父级字号（2em = 2 × 父字号）
rem    相对根 html 字号（16px 默认）
vw/vh  视口宽/高的 1%
%      相对父容器
min()  max()  clamp()
CODE
, 'note' => '字号用 rem、间距用 px/em 是常见规范'],
            ['title' => 'CSS 变量', 'code' => <<<'CODE'
:root {
  --pri: #4f6ef7;
  --gap: 16px;
}

.btn {
  color: var(--pri, #333);   /* 支持默认值 */
  padding: calc(var(--gap) * 2);
}

.btn:hover { --pri: #3d5bf5; }  /* 局部覆盖 */
CODE
, 'note' => '变量可被 JS 读写：getComputedStyle(el).getPropertyValue'],
            ['title' => '函数速查', 'code' => <<<'CODE'
calc(100% - 20px)          /* 运算 */
clamp(320px, 90vw, 960px)  /* 响应式取值 */
min(10px, 2vw)
url("bg.png")              /* 引用 */
linear-gradient(...)       /* 渐变 */
attr(data-tip)             /* 读取 HTML 属性 */
CODE
, 'note' => 'clamp 常用于响应式字号：clamp(14px, 2vw, 20px)'],
        ]],

        ['name' => 'Flexbox', 'entries' => [
            ['title' => '容器属性', 'code' => <<<'CODE'
.wrap {
  display: flex;
  flex-direction: row | row-reverse | column;
  flex-wrap: wrap | nowrap;
  justify-content: flex-start | center | space-between | space-around;
  align-items: stretch | center | flex-start | baseline;
  align-content: center;    /* 多行时行距 */
  gap: 12px 20px;          /* 行列间距 */
}
CODE
, 'note' => 'justify 主轴、align 交叉轴；方向换轴也互换'],
            ['title' => '子项属性', 'code' => <<<'CODE'
.item {
  flex-grow: 1;     /* 剩余空间分配比 */
  flex-shrink: 0;   /* 收缩比 */
  flex-basis: 200px;/* 基准尺寸 */
  flex: 1;          /* grow:1 shrink:1 basis:0 简写 */
  align-self: center; /* 单独覆盖对齐 */
  order: -1;         /* 排序权重 */
}
CODE
, 'note' => 'flex:1 常用于等分；固定侧栏用 flex: 0 0 240px'],
            ['title' => '经典布局：水平垂直居中', 'code' => <<<'CODE'
.center {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
}
CODE
, 'note' => '两行搞定任意居中，兼容性最好'],
            ['title' => '经典布局：粘性页脚', 'code' => <<<'CODE'
body {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
main { flex: 1; }   /* 撑开剩余空间 */
CODE
, 'note' => '内容不足一屏时 footer 依然贴底'],
        ]],

        ['name' => 'Grid', 'entries' => [
            ['title' => '网格容器', 'code' => <<<'CODE'
.grid {
  display: grid;
  grid-template-columns: 200px 1fr 1fr;  /* 轨道尺寸 */
  grid-template-rows: auto 100px;
  gap: 16px;
}
CODE
, 'note' => 'fr 按比例分配剩余空间，1fr 1fr 即两等分'],
            ['title' => 'repeat 与自适应', 'code' => <<<'CODE'
grid-template-columns: repeat(3, 1fr);          /* 固定3列 */
grid-template-columns: repeat(auto-fill, 240px);/* 自动换行 */
grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
CODE
, 'note' => 'auto-fit 最后一行拉伸铺满，auto-fill 保留空位'],
            ['title' => '区域放置', 'code' => <<<'CODE'
.page {
  display: grid;
  grid-template-areas:
    "header header"
    "side   main";
  grid-template-columns: 240px 1fr;
}
.header { grid-area: header; }
.side   { grid-area: side; }
CODE
, 'note' => '命名区域直观重构页面骨架'],
            ['title' => '子项跨格', 'code' => <<<'CODE'
.hero {
  grid-column: 1 / 3;      /* 跨第1~3列 */
  grid-row: 1 / span 2;   /* 从第1行起跨2行 */
  grid-area: 1 / 2 / 3 / 4;  /* row-start/col-start/row-end/col-end */
}
CODE
, 'note' => 'span N 表示跨 N 个轨道'],
        ]],

        ['name' => '文本与字体', 'entries' => [
            ['title' => '字体设置', 'code' => <<<'CODE'
body {
  font-family: "Segoe UI", "PingFang SC", sans-serif;
  font-size: 16px;
  font-weight: 400 | 700;
  line-height: 1.6;      /* 无单位按字号倍数 */
  font-style: italic;
}
CODE
, 'note' => 'line-height 用无单位倍数可继承缩放'],
            ['title' => '文本排版', 'code' => <<<'CODE'
p {
  text-align: left | center | justify;
  text-decoration: underline dotted;
  text-indent: 2em;       /* 首行缩进 */
  text-transform: uppercase | capitalize;
  letter-spacing: 0.5px;
  word-break: break-all;  /* 长串英文断行 */
  line-clamp: 2;          /* 见下行说明 */
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}
CODE
, 'note' => '多行省略三件套：-webkit-box + orient + line-clamp'],
            ['title' => '自定义字体', 'code' => <<<'CODE'
@font-face {
  font-family: "MyFont";
  src: url("myfont.woff2") format("woff2");
  font-display: swap;   /* 先显示后备字体 */
}
h1 { font-family: "MyFont", sans-serif; }
CODE
, 'note' => 'font-display:swap 避免 FOIT 白屏'],
        ]],

        ['name' => '背景与渐变', 'entries' => [
            ['title' => '背景属性', 'code' => <<<'CODE'
.card {
  background-color: #fff;
  background-image: url("bg.png");
  background-size: cover | contain | 100px 50px;
  background-position: center | 50% 30%;
  background-repeat: no-repeat;
  background-attachment: fixed;
}
CODE
, 'note' => 'cover 铺满裁剪、contain 完整显示留白'],
            ['title' => '渐变', 'code' => <<<'CODE'
background: linear-gradient(135deg, #667eea, #764ba2);
background: linear-gradient(to right, #fff0, #fff);
background: radial-gradient(circle at 30% 30%, #fff, #ccc);
CODE
, 'note' => '多段色逗号分隔；#fff0 是透明色简写'],
        ]],

        ['name' => '定位', 'entries' => [
            ['title' => 'position 取值', 'code' => <<<'CODE'
position: static;      /* 默认文档流 */
position: relative;    /* 相对自身偏移占位 */
position: absolute;    /* 相对最近定位祖先 */
position: fixed;       /* 相对视口 */
position: sticky;      /* 滚动触发吸附 */
top/right/bottom/left + z-index
CODE
, 'note' => 'absolute 需父级 relative 定界；否则相对 body'],
            ['title' => '层级与层叠上下文', 'code' => <<<'CODE'
.overlay { position: fixed; z-index: 999; }

/* 创建层叠上下文的方式 */
transform: translateZ(0);
opacity: 0.99;
isolation: isolate;
CODE
, 'note' => 'z-index 只在同一上下文中比较'],
        ]],

        ['name' => '变形与动画', 'entries' => [
            ['title' => 'transform 变换', 'code' => <<<'CODE'
.box {
  transform: translate(10px, 20px) rotate(45deg)
             scale(1.2) skew(10deg);
  transform-origin: top left;
}
CODE
, 'note' => 'transform 不触发重排，动画性能好'],
            ['title' => 'transition 过渡', 'code' => <<<'CODE'
.btn {
  transition: transform .3s ease, box-shadow .3s;
}
.btn:hover { transform: translateY(-3px); }

/* 贝塞尔缓动 */
transition: all .4s cubic-bezier(.4, 0, .2, 1);
CODE
, 'note' => 'hover 常配 transition 平滑衔接'],
            ['title' => 'animation 关键帧', 'code' => <<<'CODE'
@keyframes pulse {
  0%   { opacity: 0; transform: scale(.8); }
  50%  { opacity: 1; }
  100% { transform: scale(1); }
}

.loading {
  animation: pulse 1.2s ease-in-out infinite alternate;
}
CODE
, 'note' => 'infinite 交替 alternate 常用于呼吸动效'],
            ['title' => '媒体查询', 'code' => <<<'CODE'
@media (max-width: 768px) {   /* 平板以下 */
  .sidebar { display: none; }
}
@media (min-width: 1600px) { /* 大屏 */
  .container { max-width: 1400px; }
}
@media (prefers-color-scheme: dark) {
  body { background: #111; }
}
@media print { .toolbar { display: none; } }
CODE
, 'note' => '常见断点：640 / 768 / 1024 / 1280 / 1600'],
        ]],

        ['name' => '现代特性', 'entries' => [
            ['title' => ':has() 父选择器', 'code' => <<<'CODE'
/* 输入框有值时改变父级样式 */
label:has(input:checked) { color: green; }

/* 卡片含图片时压缩标题 */
.card:has(img) .title { font-size: 14px; }
CODE
, 'note' => 'CSS 终于能向上选择父级了（2023+ 浏览器支持）'],
            ['title' => '容器查询', 'code' => <<<'CODE'
.card-wrap { container-type: inline-size; }

@container (min-width: 400px) {
  .card { display: grid; grid-template-columns: 1fr 120px; }
}
CODE
, 'note' => '组件随容器宽度响应，而非视口'],
            ['title' => '滚动相关新属性', 'code' => <<<'CODE'
.chat {
  overflow-y: auto;
  scrollbar-width: thin;            /* 火狐细滚动条 */
  scroll-behavior: smooth;          /* 平滑滚动 */
  scroll-snap-type: y mandatory;    /* 翻页吸附 */
  overscroll-behavior: contain;    /* 阻止滚动穿透 */
}
CODE
, 'note' => 'overscroll-behavior 处理弹窗背景滚动穿透'],
        ]],

    ],
];
