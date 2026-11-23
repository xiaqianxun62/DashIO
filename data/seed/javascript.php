<?php
/* JavaScript 速查种子 */
return [
    'slug' => 'javascript',
    'name' => 'JavaScript',
    'category' => '前端',
    'icon' => '⚡',
    'color' => '#F0DB4F',
    'description' => 'ES6+ 语法、DOM、异步编程与数组方法速查',
    'sort' => 12,
    'categories' => [

        ['name' => '基础语法', 'entries' => [
            ['title' => '变量声明', 'code' => <<<'CODE'
let x = 1;          // 可重新赋值，块作用域
const y = 2;        // 常量（对象内容可变）
var z = 3;          // 函数作用域（旧代码）

const list = [1];   // ✓ 可以 push
list = [2];         // ✗ 报错：重新绑定
CODE
, 'note' => '默认 const，需要重赋值才用 let；不再使用 var'],
            ['title' => '数据类型判断', 'code' => <<<'CODE'
typeof 1;            // 'number'
typeof 'a';          // 'string'
typeof null;         // 'object'（历史缺陷！）
typeof [];           // 'object' → 用下面
Array.isArray(x);    // 判数组

x === null;                       // 判 null
Number.isNaN(x);                   // 判 NaN
Object.prototype.toString.call(x); // '[object Date]' 精判'
CODE
, 'note' => 'null 判断用 x === null，别信 typeof'],
            ['title' => '解构赋值', 'code' => <<<'CODE'
const [a, b, ...rest] = [1, 2, 3, 4];
const { name, age = 18 } = user;
const { name: userName } = user;   // 重命名

function f({ title, page = 1 }) {}  // 参数解构

const [x, , y] = [1, 2, 3];        // 跳过元素
CODE
, 'note' => '函数参数解构可带默认值，最常用于配置对象'],
            ['title' => '展开与可选链', 'code' => <<<'CODE'
const merged = [...a, ...b];
const copy = { ...obj, name: 'new' };

user?.profile?.email;     // 安全访问
fn?.();                   // 方法存在才调用
res?.data ?? [];          // 空值给默认

null ?? 'x';              // 'x'（仅 null/undefined）
0 || 'x';                 // 'x'（所有假值，注意区别）
CODE
, 'note' => '?? 只拦 null/undefined，0 与空串保留'],
            ['title' => '模板字符串', 'code' => <<<'CODE'
const msg = `你好，${name}！
共 ${items.length} 项`;

const html = `
  <li class="${active ? 'on' : ''}">${title}</li>
`;
CODE
, 'note' => '模板串内可任意表达式与换行'],
            ['title' => '箭头函数与 this', 'code' => <<<'CODE'
const add = (a, b) => a + b;
const fn = (x) => ({ id: x });    // 返回对象要括号
const log = () => { /* 多行 */ };

// 箭头函数没有自己的 this，继承外层
setTimeout(() => this.count++, 100);
CODE
, 'note' => '对象方法/原型方法不要用箭头函数，this 会丢'],
        ]],

        ['name' => '数组方法', 'entries' => [
            ['title' => '遍历与转换', 'code' => <<<'CODE'
list.map(x => x * 2);            // 映射新数组
list.filter(x => x > 1);         // 过滤
list.reduce((s, x) => s + x, 0); // 归约
list.forEach(x => console.log(x));  // 仅遍历
list.flatMap(x => [x, x]);       // 映射+展平
CODE
, 'note' => 'forEach 无返回值且不可提前退出'],
            ['title' => '查找与判断', 'code' => <<<'CODE'
list.find(x => x.id === 3);       // 首个元素
list.findIndex(x => x.id === 3);  // 索引，无则 -1
list.some(x => x > 10);           // 任一满足
list.every(x => x > 0);           // 全部满足
list.includes(2);                  // 含值
CODE
, 'note' => 'find 返回 undefined 表示未找到'],
            ['title' => '排序与生成', 'code' => <<<'CODE'
[...list].sort((a, b) => a.age - b.age);  // 升序，先拷贝！

list.slice(1, 3);        // 切片（不改原数组）
list.splice(1, 1, 'new'); // 删除+插入（改原数组）

Array.from({ length: 5 }, (_, i) => i * 2);  // [0,2,4,6,8]
Array.of(1, 2);
'abc'.split('');
CODE
, 'note' => 'sort 会改变原数组，排序前先拷贝'],
            ['title' => '新增与删除', 'code' => <<<'CODE'
list.push(4);        // 尾部加，返回新长度
list.pop();          // 尾部删
list.unshift(0);     // 头部加
list.shift();        // 头部删

list.concat([5, 6]);     // 合并（新数组）
list.join(',');           // 拼字符串
list.reverse();           // 原地反转
CODE
, 'note' => '大量头部插入请用队列思路（unshift O(n)）'],
            ['title' => '数组去重与分组', 'code' => <<<'CODE'
const unique = [...new Set([1, 1, 2, 3])];  // [1,2,3]

const byDept = {};
users.forEach(u => (byDept[u.dept] ??= []).push(u));

// ES2022 分组
Object.groupBy(users, u => u.dept);
Map.groupBy(users, u => u.role);
CODE
, 'note' => '??= 空值合并赋值初始化分组'],
        ]],

        ['name' => '对象与类', 'entries' => [
            ['title' => '对象操作', 'code' => <<<'CODE'
Object.keys(obj);      // 键数组
Object.values(obj);    // 值数组
Object.entries(obj);  // [k, v] 对数组

const copy = Object.assign({}, obj, { a: 1 });
const o2 = { ...obj, b: 2 };

Object.freeze(obj);    // 冻结（浅层）
Object.entries(obj).map(([k, v]) => `${k}=${v}`).join('&');
CODE
, 'note' => 'URL 查询串拼装常用最后那行'],
            ['title' => '类与继承', 'code' => <<<'CODE'
class Animal {
  #secret = 1;               // 私有字段
  static count = 0;          // 静态属性
  constructor(name) { this.name = name; }
  speak() { return `${this.name} 叫`; }
  get upper() { return this.name.toUpperCase(); }  // getter
  static create(n) { return new Animal(n); }
}

class Dog extends Animal {
  speak() { return `${super.speak()}：汪汪`; }
}
CODE
, 'note' => '#字段真正的运行时私有，类外访问报错'],
            ['title' => '可选/必选参数默认值', 'code' => <<<'CODE'
function connect(host, { port = 3306, timeout = 5 } = {}) {
  return `${host}:${port}`;
}
connect('localhost');
connect('localhost', { port: 3307 });
CODE
, 'note' => '选项对象参数是配置类函数的标准写法'],
            ['title' => 'getter / setter 拦截属性', 'code' => <<<'CODE'
class Temp {
  #c = 0;
  get celsius() { return this.#c; }
  set celsius(v) { this.#c = v; }
  get fahrenheit() { return this.#c * 9 / 5 + 32; }
}
CODE
, 'note' => '代理 Proxy 可拦截对象全部读写'],
        ]],

        ['name' => 'DOM 操作', 'entries' => [
            ['title' => '选择元素', 'code' => <<<'CODE'
document.querySelector('.card');        // 首个
document.querySelectorAll('li');       // 全部 NodeList

document.getElementById('app');         // 唯一
document.getElementsByClassName('a');    // 实时集合
document.querySelector('[data-id="42"]');
CODE
, 'note' => 'querySelectorAll 返回静态集合可 forEach'],
            ['title' => '创建与插入', 'code' => <<<'CODE'
const li = document.createElement('li');
li.textContent = 'new';
li.classList.add('active');
ul.append(li);           // 尾部
ul.prepend(li);         // 头部
li.remove();            // 自删

parent.replaceChild(newEl, oldEl);
oldEl.insertAdjacentHTML('beforeend', '<b>x</b>');
CODE
, 'note' => 'beforeend/afterbegin/beforebegin/afterend 四位置'],
            ['title' => '读取与修改', 'code' => <<<'CODE'
el.textContent = '纯文本';        // 安全，自动转义
el.innerHTML = '<b>html</b>';   // 慎用，注意 XSS
el.value;                          // input 值
el.dataset.id;                      // data-id
el.getAttribute('href');
el.style.color = 'red';           // 单条样式
el.classList.add/remove/toggle/contains
CODE
, 'note' => '用户内容一律 textContent，禁止拼 innerHTML'],
            ['title' => '尺寸与位置', 'code' => <<<'CODE'
el.getBoundingClientRect();   // {x,y,width,height,top...}
el.offsetTop / offsetLeft;     // 相对偏移父级
window.innerHeight;           // 视口高
window.scrollY;               // 滚动位置
el.scrollIntoView({ behavior: 'smooth', block: 'center' });
CODE
, 'note' => '视口判断用 getBoundingClientRect().top < vh'],
        ]],

        ['name' => '事件', 'entries' => [
            ['title' => '监听与解绑', 'code' => <<<'CODE'
btn.addEventListener('click', handler);
btn.addEventListener('click', handler, { once: true });  // 触发一次
btn.removeEventListener('click', handler);   // 需同一引用

// 传参
btn.addEventListener('click', () => fn(1, 2));
CODE
, 'note' => '解绑必须传同一个函数引用，箭头包装无法解绑'],
            ['title' => '事件对象常用属性', 'code' => <<<'CODE'
el.addEventListener('click', (e) => {
  e.target;          // 实际点击的元素
  e.currentTarget;  // 绑定监听的元素
  e.preventDefault(); // 阻止默认行为（表单提交/链接跳转）
  e.stopPropagation(); // 阻止冒泡
});
CODE
, 'note' => 'target ≠ currentTarget 是委托的基础'],
            ['title' => '事件委托', 'code' => <<<'CODE'
list.addEventListener('click', (e) => {
  const btn = e.target.closest('button[data-id]');
  if (!btn) return;
  doSomething(btn.dataset.id);
});
CODE
, 'note' => '一个监听器管理所有子项，动态元素也生效'],
            ['title' => '常用事件速查', 'code' => <<<'CODE'
input:  input | change | focus | blur | keydown
form:   submit | reset
mouse:  click | dblclick | mouseover | mouseout | wheel
key:    keydown | keyup    (e.key, e.ctrlKey)
drag:   dragstart | dragover | drop
window: scroll | resize | load | DOMContentLoaded
CODE
, 'note' => '输入实时用 input，取最终值用 change'],
        ]],

        ['name' => '异步编程', 'entries' => [
            ['title' => 'Promise 基础', 'code' => <<<'CODE'
const p = new Promise((resolve, reject) => {
  setTimeout(() => resolve('ok'), 100);
});

p.then(v => v.toUpperCase())
 .catch(err => console.error(err))
 .finally(() => console.log('done'));
CODE
, 'note' => 'then 链返回新 Promise，可继续链'],
            ['title' => 'async / await', 'code' => <<<'CODE'
async function load() {
  try {
    const res = await fetch('/api/data');
    if (!res.ok) throw new Error(res.status);
    const data = await res.json();
    return data;
  } catch (e) {
    console.error('加载失败', e);
    return null;
  }
}
CODE
, 'note' => 'await 只能用于 async 函数内（或模块顶层）'],
            ['title' => '并发控制', 'code' => <<<'CODE'
const [a, b, c] = await Promise.all([f1(), f2(), f3()]);
// 任一失败则整体 reject

const results = await Promise.allSettled([f1(), f2()]);
// results[i].status === 'fulfilled' | 'rejected'

await Promise.race([f1(), timeout(3000)]);  // 谁快用谁
await Promise.any([f1(), f2(), f3()]);     // 首个成功
CODE
, 'note' => 'allSettled 永不抛错，适合聚合结果'],
            ['title' => '延迟与节流', 'code' => <<<'CODE'
// 防抖：停止触发后 n 秒执行
function debounce(fn, ms = 300) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

// 节流：n 秒内最多一次
function throttle(fn, ms = 300) {
  let last = 0;
  return (...a) => {
    const now = Date.now();
    if (now - last > ms) { last = now; fn(...a); }
  };
}
CODE
, 'note' => '搜索输入用防抖，滚动监听用节流'],
            ['title' => '定时器', 'code' => <<<'CODE'
const id = setTimeout(fn, 1000);
clearTimeout(id);

const tick = setInterval(fn, 500);
clearInterval(tick);

requestAnimationFrame(step);   // 动画专用帧回调
CODE
, 'note' => '动画用 rAF 替代 setInterval 16ms'],
        ]],

        ['name' => '存储与请求', 'entries' => [
            ['title' => '本地存储', 'code' => <<<'CODE'
localStorage.setItem('token', 'x');   // 永久
localStorage.getItem('token');
localStorage.removeItem('token');

sessionStorage.setItem('k', 'v');     // 标签页会话级

document.cookie = 'k=v; max-age=3600; path=/; secure; samesite=lax';
CODE
, 'note' => 'storage 只存字符串，对象用 JSON.stringify'],
            ['title' => 'fetch 请求', 'code' => <<<'CODE'
const res = await fetch('/api/users', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ name: 'Tom' }),
  credentials: 'include',      // 带上 cookie
});
const data = await res.json();

// 上传文件
const fd = new FormData();
fd.append('file', fileInput.files[0]);
await fetch('/upload', { method: 'POST', body: fd });
CODE
, 'note' => 'fetch 404/500 不会 reject，需手动检查 res.ok'],
            ['title' => 'URL 与跳转', 'code' => <<<'CODE'
location.href = '/login';         // 跳转
location.reload();                // 刷新
location.search;                  // ?a=1&b=2

const q = new URLSearchParams(location.search);
q.get('a'); q.set('b', 2); q.toString();

history.back(); history.pushState({}, '', '/page2');
CODE
, 'note' => 'URLSearchParams 处理查询串最安全'],
            ['title' => 'JSON 与复制', 'code' => <<<'CODE'
JSON.parse(text);
JSON.stringify(obj, null, 2);   // 美化缩进

// 深拷贝（可结构化克隆的数据）
structuredClone(obj);
JSON.parse(JSON.stringify(obj));  // 老办法，丢函数与 undefined
CODE
, 'note' => 'structuredClone 支持日期、Map、循环引用'],
        ]],

        ['name' => '正则表达式', 'entries' => [
            ['title' => '正则语法速记', 'code' => <<<'CODE'
\d 数字   \w 字符   \s 空白
^ 行首   $ 行尾     . 任意字符（除换行）
[abc] 字符集   [^abc] 取反   a{2,} 两次以上
+ 一个或多个   * 零或多个   ? 可选/非贪婪
(x) 分组捕获   (?:x) 不捕获   x|y 或
CODE
, 'note' => '标志：g 全局 i 忽略大小写 m 多行 s 单行模式 u unicode'],
            ['title' => 'RegExp 方法', 'code' => <<<'CODE'
/^(\d{4})-(\d{2})$/.exec('2026-11');   // [full, y, m]
'2026-11'.match(/(\d+)/g);             // ['2026','11']
str.replace(/a/g, 'b');
str.replaceAll('a', 'b');

const re = /user-(\d+)/y; re.lastIndex = 4;  // sticky 光标匹配
CODE
, 'note' => '带 g 的正则对象多次 exec 有 lastIndex 状态'],
            ['title' => '常用正则片段', 'code' => <<<'CODE'
手机号:    /^1[3-9]\d{9}$/
邮箱:      /^[\w.-]+@[\w-]+(\.[\w-]+)+$/
URL:       /^https?:\/\/[^\s]+$/
日期:      /^\d{4}-\d{2}-\d{2}$/
空白清理:  str.replace(/\s+/g, '').trim()
驼峰转下划线: s.replace(/[A-Z]/g, m => '_' + m.toLowerCase())
CODE
, 'note' => '邮箱/URL 复杂场景建议用库或原生校验'],
        ]],

    ],
];
