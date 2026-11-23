<?php
/* C# 速查种子 */
return [
    'slug' => 'csharp',
    'name' => 'C#',
    'category' => '.NET 生态',
    'icon' => '🧩',
    'color' => '#68217A',
    'description' => 'C# 语法、面向对象、LINQ 与异步编程速查',
    'sort' => 40,
    'categories' => [

        ['name' => '基础语法', 'entries' => [
            ['title' => '程序入口', 'code' => <<<'CODE'
// 传统写法
class Program
{
    static void Main(string[] args) { }
}

// 顶级语句（C# 9+，整个项目仅一个文件）
Console.WriteLine("Hello, C#");
CODE
, 'note' => '顶级语句从第一行执行到尾，适合小工具与教学'],
            ['title' => '变量与类型', 'code' => <<<'CODE'
int i = 42;                long l = 42L;
double d = 3.14;           decimal money = 3.14m;
string s = "str";          char c = 'A';
bool ok = true;            var x = 1;      // 类型推断

const double PI = 3.14159;                 // 编译常量
readonly DateTime Init = DateTime.Now;   // 运行时只读
CODE
, 'note' => '金额用 decimal（m 后缀）；double 有二进制误差'],
            ['title' => '字符串', 'code' => <<<'CODE'
var s = $"用户 {name}，余额 {money:F2} 元";
$"{{{name}}}";                 // 转义花括号
s.Length; s[0];
string.Join(",", list);
string.Concat(a, b);
$"x={x,8:F3}";                  // 对齐+格式

// 原始字符串（C# 11）
var json = """
    { "a": 1 }
    """;
CODE
, 'note' => '插值冒号后是格式说明符，如 F2 保留两位'],
            ['title' => '常用字符串方法', 'code' => <<<'CODE'
s.Split(','); s.Split(new[] {','}, StringSplitOptions.RemoveEmptyEntries);
s.Contains("ab"); s.StartsWith("/api"); s.EndsWith(".php");
s.Substring(1, 3); s.Replace('a', 'b');
s.Trim(); s.TrimStart(); s.ToUpper(); s.ToLower();
s.IndexOf("ab"); s.PadLeft(4, '0');
string.IsNullOrWhiteSpace(s);
string.Compare(a, b, StringComparison.OrdinalIgnoreCase);
CODE
, 'note' => '判空用 IsNullOrWhiteSpace（空格也算空）'],
            ['title' => '流程控制', 'code' => <<<'CODE'
if (score > 90) { }
else if (score > 60) { }

switch (n)
{
    case 1: goto case 2;
    case >= 5 and <= 10: break;      // 关系模式
    case var other when other < 0: break;
    default: break;
}

var grade = score switch { 90..100 => 'A', _ => 'C' };  // switch 表达式
CODE
, 'note' => 'switch 表达式用 =>，_ 是弃元分支'],
            ['title' => '循环', 'code' => <<<'CODE'
for (int i = 0; i < 10; i++) { }
foreach (var item in list) { }
while (cond) { }
do { } while (cond);

foreach (var (k, v) in map) { }        // 元组解构
break; continue; goto label;            // 谨用 goto
CODE
, 'note' => 'foreach 遍历时不能修改集合本身'],
        ]],

        ['name' => '面向对象', 'entries' => [
            ['title' => '类与属性', 'code' => <<<'CODE'
public class Person
{
    public string Name { get; set; }            // 自动属性
    public int Age { get; private set; }         // 外读内写
    public string Info => $"{Name} {Age}";       // 只读计算属性

    private string _email;
    public string Email
    {
        get { return _email; }
        set { _email = value?.Trim().ToLower() ?? ""; }
    }
}
CODE
, 'note' => '=> 表达式属性每次求值，等价于只有 getter'],
            ['title' => '构造与初始化', 'code' => <<<'CODE'
public class Order
{
    public Order(string no) { No = no; }

    public string No { get; init; }     // init：仅构造/初始化器可赋
    public List<Item> Items { get; } = new();
}

var o = new Order("NO1") { No = "NO1" };   // 对象初始化器
var o2 = new Person { Name = "Tom" };
CODE
, 'note' => 'record/init 让对象天然不可变，适合 DTO'],
            ['title' => '继承 / 抽象 / 接口', 'code' => <<<'CODE'
public class Animal { public virtual void Speak() { } }
public class Dog : Animal
{
    public sealed override void Speak() { }   // sealed 阻止再重写
}

public abstract class Repo<T>
{
    public abstract T Find(int id);            // 必须实现
    public virtual void Save(T t) { }          // 可选重写
}

public interface ILogger { void Log(string m); }
public class Service : Repo<int>, ILogger
{
    public override int Find(int id) => id;
    public void Log(string m) { }
}
CODE
, 'note' => '一个类最多继承一个基类，但可实现多个接口'],
            ['title' => 'record 类型（C# 9+）', 'code' => <<<'CODE'
public record Point(int X, int Y);

var p1 = new Point(1, 2);
var p2 = p1 with { X = 10 };       // 非破坏性修改
p1 == p2;                            // 值相等

public record Person(string Name, int Age)
{
    public string Hello() => $"Hi {Name}";
}
CODE
, 'note' => 'record 自动生成 equals/hashCode/ToString，with 一步克隆'],
            ['title' => '结构体与枚举', 'code' => <<<'CODE'
public struct Money { public decimal Amount; public string Cur; }
public readonly struct Point(int x, int y);

public enum Status { Active = 1, Disabled = 2 }

var s = Status.Active;
(int) s;                              // 1
(Status) 2;                           // Disabled
if (s == Status.Active) { }
Status.TryParse("Active", out var st);
CODE
, 'note' => '小而频繁的值对象用 struct 免堆分配'],
            ['title' => 'partial 与 static', 'code' => <<<'CODE'
public partial class Order { public int Id { get; set; } }
public partial class Order { public void Ship() { } }   // 拆文件维护

public static class Util
{
    public static int Add(int a, int b) => a + b;   // 静态类不可实例化
}
CODE
, 'note' => '静态类是工具方法的家；partial 常配合代码生成器'],
        ]],

        ['name' => '委托与事件', 'entries' => [
            ['title' => '常用系统委托', 'code' => <<<'CODE'
Action a = () => Console.WriteLine("go");
Action<int, string> a2 = (n, s) => { };
Func<int, int> square = x => x * x;          // 有返回值
Predicate<int> p = x => x > 0;              // 返回 bool
Comparison<string> cmp = (x, y) => x.Length - y.Length;
CODE
, 'note' => 'Action 无返回、Func 最后一个泛型是返回类型'],
            ['title' => '自定义委托与事件', 'code' => <<<'CODE'
public delegate void ChangedHandler(object sender, string prop);

public class Model
{
    public event ChangedHandler? Changed;

    protected virtual void OnChanged(string prop)
    {
        Changed?.Invoke(this, prop);      // 判空调用
    }
}

model.Changed += (s, p) => Console.WriteLine(p);
model.Changed -= handler;                  // 取消订阅
CODE
, 'note' => '事件只能由声明类触发，外部只能 +=/-='],
            ['title' => 'lambda 进阶', 'code' => <<<'CODE'
Func<int, bool> f = static x => x > 0;    // 静态 lambda

var names = new[] { "a", "bb" };
var query = names.Where(n => n.Length > 1);

(int, string) tuple = (1, "a");
var (id, name) = tuple;                   // 解构

local int Add(int a, int b) => a + b;     // 本地函数（C# 9 声明风格）
CODE
, 'note' => '本地函数可被递归与引用捕获，lambda 不行'],
        ]],

        ['name' => '集合与 LINQ', 'entries' => [
            ['title' => '常用集合', 'code' => <<<'CODE'
var list = new List<int> { 1, 2, 3 };
var dict = new Dictionary<string, int>();
dict["a"] = 1;
dict.TryGetValue("a", out var v);      // 安全取值
var set = new HashSet<string>();
var queue = new Queue<int>(); queue.Enqueue(1); queue.Dequeue();
var stack = new Stack<int>(); stack.Push(1); stack.Pop();

// 不可变集合
var frozen = list.ToFrozenSet();       // .NET 8 查询极快
var im = ImmutableList<int>.Empty.Add(1);
CODE
, 'note' => '只读场景 Frozen* 系列性能最好'],
            ['title' => 'LINQ 基础', 'code' => <<<'CODE'
var q = from u in users
        where u.Age > 18
        orderby u.Name
        select new { u.Name, u.Age };

var q2 = users.Where(u => u.Age > 18)
             .OrderBy(u => u.Name)
             .Select(u => u.Name);
CODE
, 'note' => '两种写法等价；方法语法可链更多操作符'],
            ['title' => '常用操作符', 'code' => <<<'CODE'
list.Where(x => x > 1);
list.Select(x => x * 2);
list.SelectMany(g => g.Items);          // 展平
list.OrderBy(x => x.Age).ThenBy(x => x.Name);
list.OrderByDescending(x => x.Age);
list.Skip(10).Take(5);
list.Distinct(); list.GroupBy(x => x.Dept);
list.First(x => x > 1); list.FirstOrDefault(x => x > 1, fallback);
list.Any(x => x > 1); list.All(x => x > 0);
list.Count(x => x > 1); list.Sum(x => x.N); list.Average();
list.Max(x => x.N); list.Min(x => x.N);
list.Contains(1); list.DefaultIfEmpty();
list.Aggregate((a, b) => a + b);
CODE
, 'note' => 'First 无匹配抛异常，FirstOrDefault 返回默认值'],
            ['title' => '分组与联接', 'code' => <<<'CODE'
var groups = users.GroupBy(u => u.Dept)
                  .Select(g => new { Dept = g.Key, Count = g.Count() });

var join = from u in users
           join d in depts on u.DeptId equals d.Id
           select new { u.Name, d.DeptName };

var leftJoin = from u in users
               join d in depts on u.DeptId equals d.Id into g
               from d in g.DefaultIfEmpty()
               select new { u.Name, Dept = d?.DeptName };
CODE
, 'note' => 'into + DefaultIfEmpty 是 LINQ 的 LEFT JOIN 写法'],
            ['title' => '聚合与字典化', 'code' => <<<'CODE'
var dict = list.ToDictionary(x => x.Id, x => x.Name);

var lookup = list.ToLookup(x => x.Dept);   // 一对多字典
lookup["dev"].ToList();

var strings = string.Join(",", list);
var avg = list.Average(x => x.N);
var stats = list.Aggregate(
    new { Sum = 0, Count = 0 },
    (acc, x) => new { Sum = acc.Sum + x, Count = acc.Count + 1 });
CODE
, 'note' => 'ToLookup 与 GroupBy 类似但可反复枚举'],
            ['title' => 'yield 迭代器', 'code' => <<<'CODE'
IEnumerable<int> Evens(int max)
{
    for (int i = 0; i <= max; i += 2)
        yield return i;
}

foreach (var n in Evens(10)) { }

IEnumerable<T> Empty<T>() { yield break; }   // 立即结束
CODE
, 'note' => 'yield 惰性生成，处理大数据流省内存'],
        ]],

        ['name' => '异步编程', 'entries' => [
            ['title' => 'async / await 基础', 'code' => <<<'CODE'
public async Task<string> LoadAsync()
{
    using var http = new HttpClient();
    var res = await http.GetStringAsync(url);
    return res;
}

public async Task<int> AddAsync(int a, int b)
    => await Task.FromResult(a + b);

await LoadAsync();                     // 调用方也需 async
var t = LoadAsync(); t.Wait();         // 死锁风险，别这样
CODE
, 'note' => 'async 方法名以 Async 结尾；UI 线程禁 Wait/Result'],
            ['title' => 'Task 组合', 'code' => <<<'CODE'
var all = await Task.WhenAll(t1, t2, t3);   // 等全部
var first = await Task.WhenAny(t1, t2);      // 等任一
await Task.Delay(1000);                       // 异步等待
await Task.Run(() => Heavy());               // 后台跑
await Task.Yield();                          // 让出一拍

var tcs = new TaskCompletionSource<int>();
tcs.SetResult(42);
CODE
, 'note' => 'Task.Run 适合 CPU 密集；IO 用原生异步 API'],
            ['title' => '取消与异常', 'code' => <<<'CODE'
var cts = new CancellationTokenSource(3000);
await LongWork(cts.Token);

async Task LongWork(CancellationToken ct)
{
    foreach (var x in items)
    {
        ct.ThrowIfCancellationRequested();
        await DoAsync(x, ct);
    }
}

try { await t; }
catch (OperationCanceledException) { }
catch (HttpRequestException e) { }
CODE
, 'note' => '超时 3 秒自动取消并抛 OperationCanceledException'],
            ['title' => '并行处理', 'code' => <<<'CODE'
Parallel.For(0, 100, i => Work(i));
Parallel.ForEach(items, item => Work(item));

var results = items.AsParallel()
                  .WithDegreeOfParallelism(4)
                  .Where(x => x > 0)
                  .ToArray();
CODE
, 'note' => 'Parallel 阻塞调用线程；大数据 CPU 场景用 PLINQ'],
        ]],

        ['name' => '异常与语法糖', 'entries' => [
            ['title' => '异常处理', 'code' => <<<'CODE'
try
{
    int n = int.Parse(s);
}
catch (FormatException e) when (s.Length == 0)   // 条件捕获
{ }
catch (Exception e)
{
    throw;                        // 保留栈重新抛
    // throw new AppException("包装", e);   // 包装
}
finally { cleanup(); }
CODE
, 'note' => '单独 throw 保留原始堆栈，throw e 会重置'],
            ['title' => '可空引用类型', 'code' => <<<'CODE'
string? maybe = null;          // 启用 <Nullable>enable</Nullable>
string sure = "x";

if (maybe is not null) { }
var len = maybe?.Length ?? 0;

string? name = GetName();
_ = name ?? throw new ArgumentNullException(nameof(name));
CODE
, 'note' => '项目启用 Nullable 后编译器帮你找空引用风险'],
            ['title' => '模式匹配', 'code' => <<<'CODE'
if (obj is int n) { }
if (obj is int and > 5) { }
if (obj is int[] { Length: > 0 } arr) { }

var r = obj switch
{
    int n when n > 0 => "正数",
    string s => $"串 {s}",
    null => "空",
    _ => "其他"
};
CODE
, 'note' => '属性模式 { Length: > 0 } 可深入检查字段'],
            ['title' => '扩展方法', 'code' => <<<'CODE'
public static class StrExt
{
    public static string Truncate(this string s, int max)
        => s.Length <= max ? s : s[..max] + "…";
}

var short = longStr.Truncate(10);       // 像实例方法一样调用
CODE
, 'note' => '扩展方法必须静态类、第一参 this，命名空间要 using'],
            ['title' => '运算符与索引', 'code' => <<<'CODE'
var half = s[..4];          // 前4字符
var tail = s[4..];           // 第4到末尾
var mid  = s[1..^1];        // 去头尾各1
^1;                          // 倒数第1

var arr = new[] { 1, 2, 3 };
arr[^1];                     // 3

public int this[int i] => data[i];      // 索引器
CODE
, 'note' => '^ 与 .. 范围运算符不越界，安全截取'],
        ]],

        ['name' => '文件与序列化', 'entries' => [
            ['title' => '文件读写', 'code' => <<<'CODE'
File.WriteAllText(path, "hello");
File.ReadAllText(path);
File.ReadAllLines(path);
File.AppendAllText(path, "more");
File.Exists(path); File.Delete(path);

Directory.CreateDirectory(dir);
Directory.GetFiles(dir, "*.cs", SearchOption.AllDirectories);
Path.Combine("a", "b.txt");           // 跨平台拼接
Path.GetFileName(p); Path.GetExtension(p);
CODE
, 'note' => '路径拼接用 Path.Combine，别手写 + "/"'],
            ['title' => 'JSON 序列化', 'code' => <<<'CODE'
var json = JsonSerializer.Serialize(obj,
    new JsonSerializerOptions { WriteIndented = true });

var obj = JsonSerializer.Deserialize<Order>(json,
    new JsonSerializerOptions { PropertyNameCaseInsensitive = true });

// 属性改名
[JsonPropertyName("user_name")]
public string? UserName { get; set; }
CODE
, 'note' => 'System.Text.Json 高性能无依赖，首选'],
            ['title' => '特性（Attribute）', 'code' => <<<'CODE'
[Serializable]
[Obsolete("改用 NewMethod")]
[Conditional("DEBUG")]
public class Foo { }

// 自定义特性
[AttributeUsage(AttributeTargets.Method)]
public class LogAttribute : Attribute { }

typeof(Foo).GetCustomAttribute<ObsoleteAttribute>();
CODE
, 'note' => '特性是元数据，配合反射才能发挥作用'],
        ]],

    ],
];
