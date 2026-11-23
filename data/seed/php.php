<?php
/* PHP 速查种子 - 片A：基础语法 / 字符串 / 数组 */
return [
    'slug' => 'php',
    'name' => 'PHP',
    'category' => '后端开发',
    'icon' => '🐘',
    'color' => '#8892BF',
    'description' => 'PHP 8 常用语法、函数、面向对象与 PDO 数据库操作速查',
    'sort' => 20,
    'categories' => [

        ['name' => '基础语法', 'entries' => [
            ['title' => '变量与数据类型', 'code' => <<<'CODE'
$name = 'PHP';          // string
$age  = 30;             // int
$pi   = 3.14;           // float
$ok   = true;           // bool
$n    = null;           // null
$arr  = [1, 2, 3];      // array

echo gettype($age);      // integer
var_dump($pi);           // float(3.14)
CODE
, 'note' => '变量以 $ 开头，无需声明类型；弱类型自动转换'],
            ['title' => '常量', 'code' => <<<'CODE'
define('APP_NAME', 'QuickRef');

const VERSION = '1.0';          // 编译期，可用于类中

echo APP_NAME, PHP_EOL, VERSION;
CODE
, 'note' => '常量区分大小写，惯用全大写；const 不能用于条件分支内'],
            ['title' => '字符串输出与插值', 'code' => <<<'CODE'
$name = 'World';

echo "Hello, $name!";           // 双引号解析变量
echo "Result: {$obj->count}";  // 复杂表达式用 {}
echo 'Hello, $name';            // 单引号原样输出

printf("price: %.2f", 9.5);     // 格式化输出 9.50
CODE
, 'note' => '双引号内可解析变量与转义符 \\n \\t，单引号除 \\\' 与 \\\\ 外不转义'],
            ['title' => '条件判断', 'code' => <<<'CODE'
if ($age >= 18) {
    echo 'adult';
} elseif ($age >= 12) {
    echo 'teen';
} else {
    echo 'child';
}

switch ($op) {
    case '+': $r = $a + $b; break;
    case '-': $r = $a - $b; break;
    default:  $r = null;
}
CODE
, 'note' => 'switch 使用松散比较(==)；别忘了 break'],
            ['title' => 'match 表达式（PHP 8）', 'code' => <<<'CODE'
$result = match(true) {
    $score >= 90 => 'A',
    $score >= 60 => 'B',
    default      => 'C',
};

$code = match($status) {          // 严格比较 ===
    200, 201 => 'OK',
    404       => 'Not Found',
    default   => 'Unknown',
};
CODE
, 'note' => 'match 是表达式有返回值，且无匹配又无 default 时抛 ValueError'],
            ['title' => '循环', 'code' => <<<'CODE'
for ($i = 0; $i < 10; $i++) { echo $i; }

foreach ($list as $item) { }              // 遍历值
foreach ($list as $k => $v) { }           // 遍历键值
foreach ($list as &$item) { $item *= 2; } // 引用修改

while (($row = fget($fh)) !== false) { }
do { } while ($cond);

continue; break;          // 循环控制
CODE
, 'note' => '引用遍历后记得 unset($item)，避免后续误改'],
            ['title' => '函数与参数', 'code' => <<<'CODE'
function add(int $a, int $b = 0): int
{
    return $a + $b;
}

function sum(int ...$nums): int           // 可变参数
{
    return array_sum($nums);
}

$n = ...[$a, $b];                          // 展开调用 add(...$args)
CODE
, 'note' => '支持类型声明与返回类型；弱类型模式下标量自动转换'],
            ['title' => '箭头函数与闭包', 'code' => <<<'CODE'
$square = fn($x) => $x * $x;              // 箭头函数自动捕获外部变量

$factor = 10;
$mul = fn($x) => $x * $factor;

$callback = function ($v) use ($factor) { // 传统闭包需 use 捕获
    return $v * $factor;
};
CODE
, 'note' => 'fn() 只能单表达式，自动按值捕获外部变量'],
            ['title' => '三元与空合并运算符', 'code' => <<<'CODE'
echo $user ?: 'guest';        // 旧三元简写：真值返回本身

$page = $_GET['page'] ?? 1;   // 空合并：不存在或 null 时取 1

$name = $name ??= '默认名';   // 空合并赋值（PHP 7.4+）
CODE
, 'note' => '?? 只判断 null；?: 判断所有假值（0、空串也算假）'],
        ]],
        ['name' => '字符串处理', 'entries' => [
            ['title' => '常用字符串函数', 'code' => <<<'CODE'
strlen('hello');                 // 5 字节
mb_strlen('你好', 'UTF-8');      // 2（多字节用 mb_*）
strpos('a/b/c', '/');            // 1，找不到返回 false
str_replace('a', 'b', 'aaa');    // 'bbb'
substr('hello', 1, 3);           // 'ell'
mb_substr('你好世界', 0, 2);      // '你好'
trim('  hi  ');                  // 'hi'，ltrim/rtrim 单侧
strtolower('PHP'); strtoupper('php');
ucfirst('hello');                // 'Hello'
str_repeat('ab', 3);             // 'ababab'
str_pad('7', 3, '0', STR_PAD_LEFT); // '007'
CODE
, 'note' => '中文等多字节文本一律用 mb_* 系列并注意 UTF-8'],
            ['title' => '分割与拼接', 'code' => <<<'CODE'
$str = 'a,b,c';
$arr = explode(',', $str);          // ['a','b','c']
$csv = implode(',', $arr);          // 'a,b,c'
$line = str_split('abc');            // ['a','b','c']
sscanf('2024-01-02', '%d-%d-%d');    // [2024,1,2]
CODE
, 'note' => "explode 第二参可限制段数：explode(',', \$s, 2)"],
            ['title' => '格式化字符串', 'code' => <<<'CODE'
sprintf('%05.2f', 3.1);          // 03.10
sprintf('%s 有 %d 个', '书', 3);
number_format(1234567.89, 2, '.', ',');  // 1,234,567.89
CODE
, 'note' => '%05.2f = 补零至5位含小数点后2位'],
            ['title' => 'JSON 处理', 'code' => <<<'CODE'
$arr = ['name' => 'Tom', 'tags' => ['a', 'b']];

$json = json_encode($arr, JSON_UNESCAPED_UNICODE);
$data = json_decode($json, true);   // true 返回数组
$obj  = json_decode($json);         // 默认返回 stdClass

json_last_error() === JSON_ERROR_NONE;  // 校验解析结果
CODE
, 'note' => 'JSON_UNESCAPED_UNICODE 避免中文转 \\uXXXX'],
            ['title' => '正则表达式', 'code' => <<<'CODE'
preg_match('/^\d{3}-\d{4}$/', $zip, $m);   // 返回 1/0，$m 为匹配
preg_match_all('/\w+/', $str, $all);
preg_replace('/\s+/', ' ', $str);
preg_split('/[,.]/', $str);

if (preg_match('/(?<year>\d{4})-(?<mo>\d{2})/', $s, $m)) {
    echo $m['year'];                // 命名分组
}
CODE
, 'note' => '正则分隔符可用 / ~ # 等；preg_ 失败返回 false 并可查 preg_last_error()'],
            ['title' => 'HTML 与安全转义', 'code' => <<<'CODE'
htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); // 转义 & < > " '
strip_tags($html, '<p><b>');                  // 去标签保留白名单
nl2br($text);                                // 换行转 <br>

echo htmlspecialchars($userInput);           // 输出前必转义，防 XSS
CODE
, 'note' => '输出到 HTML 前转义；进数据库前不要先转义'],
        ]],
        ['name' => '数组', 'entries' => [
            ['title' => '数组创建与访问', 'code' => <<<'CODE'
$list = [1, 2, 3];                    // 索引数组
$map  = ['id' => 1, 'name' => 'Tom']; // 关联数组
$mixed = [1, 'a' => 2, 3];            // 混合

echo $list[0], $map['name'];
$list[] = 4;                          // 尾部追加
count($list);                         // 元素数
in_array(2, $list);                   // 值是否存在
array_key_exists('name', $map);       // 键是否存在
isset($map['name']);                   // 存在且非 null
CODE
, 'note' => 'in_array 第三个参数 true 启用严格比较'],
            ['title' => '遍历与解构', 'code' => <<<'CODE'
foreach ($users as $u) { }

foreach ($users as $i => $u) { }

foreach ($users as ['id' => $id, 'name' => $n]) {  // 列表解构
    echo "$id => $n";
}

[$a, $b] = [1, 2];        // 拆包
['x' => $x] = ['x' => 5]; // 关联解构
CODE
, 'note' => 'PHP 7.1+ 支持列表/关联数组解构语法'],
            ['title' => '数组函数（必备）', 'code' => <<<'CODE'
array_map(fn($x) => $x * 2, $list);         // 映射
array_filter($list, fn($x) => $x > 1);      // 过滤
array_reduce($list, fn($c, $x) => $c + $x); // 归约
array_merge($a, $b);                         // 合并（重数字索引）
$a + $b;                                    // 合并（保留左侧键）
array_slice($list, 1, 2);                   // 切片
array_splice($list, 1, 1);                  // 删除并替换
array_keys($map); array_values($map);
array_flip($map);                           // 键值互换
array_unique($list);                        // 去重
array_column($rows, 'name', 'id');           // 提取列，id 为键
array_combine($keys, $vals);                // 组装
array_search('Tom', $map);                  // 由值找键
CODE
, 'note' => 'array_column 常用于把数据库行集合转 id=>name 字典'],
            ['title' => '排序', 'code' => <<<'CODE'
sort($list);              // 升序（重排数字键）
rsort($list);             // 降序
asort($map);              // 按值升序，保留键
ksort($map);              // 按键升序
usort($list, fn($a, $b) => $a <=> $b);  // 自定义

usort($rows, fn($a, $b) => $a['age'] <=> $b['age']);  // 按字段
CODE
, 'note' => '带 u 前缀为自定义比较；<=> 太空船运算符返回 -1/0/1'],
            ['title' => '栈 / 队列操作', 'code' => <<<'CODE'
array_push($list, $v);     // 入栈，等价 $list[] = $v
array_pop($list);         // 出栈（尾部）
array_shift($list);       // 出队（头部）
array_unshift($list, $v); // 头部插入
CODE
, 'note' => '队列用 array_push + array_shift'],
        ]],
        ['name' => '面向对象', 'entries' => [
            ['title' => '类与对象基础', 'code' => <<<'CODE'
class User
{
    public string $name = '';        // 类型属性（PHP 8 允许非 null 默认）
    private int $age;
    public const ROLE = 'member';    // 类常量

    public function __construct(string $name, int $age)
    {
        $this->name = $name;
        $this->age  = $age;
    }

    public function getAge(): int
    {
        return $this->age;
    }
}

$u = new User('Tom', 20);
echo $u->name, User::ROLE;
CODE
, 'note' => '伪变量 $this 指当前对象；:: 访问静态与常量'],
            ['title' => '构造器属性提升（PHP 8）', 'code' => <<<'CODE'
class Point
{
    public function __construct(
        private float $x = 0.0,
        private float $y = 0.0,
    ) {}

    public function dist(): float
    {
        return sqrt($this->x ** 2 + $this->y ** 2);
    }
}
CODE
, 'note' => '构造参数自动声明并赋值同名属性，减少样板代码'],
            ['title' => '继承 / 抽象 / 接口', 'code' => <<<'CODE'
interface Repository
{
    public function find(int $id): ?array;
}

abstract class BaseRepo implements Repository
{
    abstract protected function table(): string;   // 强制子类实现

    public function find(int $id): ?array
    {
        // 公共查询逻辑，$this->table() 由子类提供
        return [];
    }
}

class UserRepo extends BaseRepo
{
    protected function table(): string { return 'users'; }
}
CODE
, 'note' => '接口只声明公共方法；抽象类可含实现；PHP 单继承 + 多接口'],
            ['title' => 'trait 与静态成员', 'code' => <<<'CODE'
trait Logger
{
    public static int $count = 0;          // 静态属性

    public function log(string $msg): void
    {
        static::$count++;                 // 静态延迟绑定 static::
        echo $msg;
    }
    public static function create(): static
    {
        return new static();
    }
}

class Service
{
    use Logger;                            // 组合进类
}
CODE
, 'note' => 'trait 解决横向复用；static:: 与 new static 支持子类晚期绑定'],
            ['title' => '魔术方法', 'code' => <<<'CODE'
class Magic
{
    public function __get($k) { return "缺 {$k}"; }   // 读不存在属性
    public function __set($k, $v) { }               // 写不存在属性
    public function __isset($k): bool { return false; }
    public function __call($name, $args) { }        // 调不存在方法
    public static function __callStatic($n, $a) { }
    public function __toString(): string { return 'obj'; } // echo $obj
    public function __invoke() { }                  // $obj() 时触发
}
CODE
, 'note' => '__toString/__invoke 常用于 DTO 与集合对象'],
            ['title' => '命名空间与 autoload', 'code' => <<<'CODE'
namespace App\Service;

use App\Model\User as UserModel;
use function App\Util\guid;
use const App\Util\VERSION;

class Mailer { }

$fqcn = Mailer::class;        // 'App\Service\Mailer'
CODE
, 'note' => 'composer 的 autoload 遵循 PSR-4：类名即文件路径'],
            ['title' => '枚举（PHP 8.1）', 'code' => <<<'CODE'
enum Status: string            // 回退类型枚举
{
    case Active   = 'active';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match($this) {
            Status::Active   => '启用',
            Status::Disabled => '禁用',
        };
    }
}

Status::from('active');      // 'active' -> Status::Active
Status::tryFrom('x');        // null
Status::cases();             // 全部枚举项
CODE
, 'note' => '纯枚举不带值；match 枚举必须穷尽所有 case'],
        ]],
        ['name' => '错误与异常', 'entries' => [
            ['title' => 'try / catch / finally', 'code' => <<<'CODE'
try {
    $conn = new PDO($dsn, $user, $pass);
} catch (PDOException $e) {              // 类型捕获
    error_log($e->getMessage());
    throw new RuntimeException('数据库连接失败', 0, $e);  // 链式异常
} finally {
    echo '总会执行';
}
CODE
, 'note' => 'PHP 8 起未捕获的 Error/PDOException 等都走 Exception 体系'],
            ['title' => '自定义异常', 'code' => <<<'CODE'
class ValidateException extends Exception { }

class PayException extends Exception
{
    public function __construct(private string $orderNo)
    {
        parent::__construct("订单 {$this->orderNo} 支付失败");
    }
}

try {
    throw new PayException('NO.100');
} catch (PayException $e) {
    echo $e->getMessage();        // 订单 NO.100 支付失败
}
CODE
, 'note' => 'getMessage/getCode/getFile/getLine/getPrevious 为常用 API'],
            ['title' => '错误处理配置', 'code' => <<<'CODE'
error_reporting(E_ALL);                     // 报告所有
ini_set('display_errors', '0');            // 线上关闭输出
ini_set('log_errors', '1');

set_error_handler(function ($no, $str, $file, $line) {
    throw new ErrorException($str, 0, $no, $file, $line);
});
CODE
, 'note' => '把错误转异常统一处理；生产环境务必 display_errors=0'],
        ]],
        ['name' => '日期与时间', 'entries' => [
            ['title' => 'DateTime 基础', 'code' => <<<'CODE'
$now = new DateTime('now');            // 或 new DateTime()
$date = new DateTime('2026-11-16 10:30:00');

echo $now->format('Y-m-d H:i:s');
echo $date->getTimestamp();

$tomorrow = (new DateTime())->modify('+1 day');
$diff = $now->diff($tomorrow);          // DateInterval
echo $diff->days;
CODE
, 'note' => 'DateTime 可变，注意 clone；不可变用 DateTimeImmutable'],
            ['title' => '常用格式字符', 'code' => <<<'CODE'
Y 四位年     m 两位月     d 两位日
H 24小时     i 分钟       s 秒
w 星期(0=周日) N 星期ISO(1=周一)
t 本月天数   L 闰年 1/0    U 时间戳

date('Y-m-d H:i:s', $ts);          // 时间戳格式化
strtotime('next Monday');          // 英文短语转时间戳
CODE
, 'note' => "strtotime 支持英文短语：+1 week 2 days、last day of month、next Monday 等"],
            ['title' => '时区处理', 'code' => <<<'CODE'
date_default_timezone_set('Asia/Shanghai');

$d = new DateTime('now', new DateTimeZone('UTC'));
$d->setTimezone(new DateTimeZone('Asia/Shanghai'));

(new DateTime('now'))->getTimezone()->getName();
CODE
, 'note' => '服务器默认常为 UTC，展示给用户前记得转本地时区'],
        ]],
        ['name' => '文件与目录', 'entries' => [
            ['title' => '读写文件', 'code' => <<<'CODE'
file_put_contents('log.txt', "line\n", FILE_APPEND | LOCK_EX);

$content = file_get_contents('data.json');

$lines = file('list.txt', FILE_IGNORE_NEW_LINES);  // 行数组

$fh = fopen('big.csv', 'r');
while (($row = fgetcsv($fh)) !== false) { }
fclose($fh);
CODE
, 'note' => 'file_get_contents 可读 URL（需 allow_url_fopen）'],
            ['title' => '目录与路径', 'code' => <<<'CODE'
scandir('/var/www');                 // 文件名数组
glob('src/*.php');                   // 模式匹配
is_dir('src'); is_file('a.php');
mkdir('a/b/c', 0775, true);          // 递归创建
rename('old', 'new'); unlink('a.php'); rmdir('dir');

basename('/x/y/a.php');              // a.php
dirname('/x/y/a.php');                // /x/y
pathinfo('/x/a.php');                 // dir,file,filename,extension
CODE
, 'note' => '删除目录需先清空内容（配合 RecursiveIteratorIterator）'],
            ['title' => '上传处理', 'code' => <<<'CODE'
if (($_FILES['f']['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_OK) {
    $tmp = $_FILES['f']['tmp_name'];
    $size = $_FILES['f']['size'];
    move_uploaded_file($tmp, __DIR__ . '/uploads/' . bin2hex(random_bytes(8)));
}
CODE
, 'note' => '校验 error/size/扩展名；文件名不可信，用随机名存储'],
        ]],
        ['name' => 'Web 相关', 'entries' => [
            ['title' => '超全局变量', 'code' => <<<'CODE'
$_GET['q']          // 查询串参数
$_POST['name']     // 表单 POST
$_REQUEST          // GET+POST+COOKIE 合并
$_COOKIE['k']
$_SESSION['uid']   // session_start() 后可用
$_SERVER['REQUEST_METHOD']    // GET/POST
$_SERVER['REQUEST_URI']      // /path?x=1
$_SERVER['REMOTE_ADDR']      // 客户端 IP
$_FILES['up']               // 文件上传
$_SERVER['HTTP_USER_AGENT'] // 浏览器标识
CODE
, 'note' => '使用前用 ?? 提供默认值，避免未定义索引告警'],
            ['title' => 'HTTP 头与响应', 'code' => <<<'CODE'
header('Content-Type: application/json; charset=utf-8');
header('Location: /login.php');            // 302 跳转
header('HTTP/1.1 404 Not Found');
http_response_code(404);                   // 设置状态码
setcookie('k', 'v', ['expires' => 0, 'httponly' => true, 'samesite' => 'Lax']);
CODE
, 'note' => 'header 必须在任何输出之前调用（注意 BOM/空行）'],
            ['title' => 'Session 会话', 'code' => <<<'CODE'
session_start();                  // 每个脚本开头
$_SESSION['user'] = ['id' => 1];

session_regenerate_id(true);      // 登录后换 id 防会话固定
session_unset();                  // 清空数据
session_destroy();                // 销毁会话
CODE
, 'note' => '跨页面共享登录态；cookie 由 PHPSESSID 承载'],
        ]],
        ['name' => 'PDO 数据库', 'entries' => [
            ['title' => '连接 MySQL', 'code' => <<<'CODE'
$pdo = new PDO(
    'mysql:host=127.0.0.1;port=3306;dbname=app;charset=utf8mb4',
    'root', 'secret',
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // 抛异常
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // 关联数组
    ]
);
CODE
, 'note' => 'charset 写进 DSN，避免中文乱码；MariaDB 完全兼容'],
            ['title' => '查询与取结果', 'code' => <<<'CODE'
$rows = $pdo->query('SELECT * FROM users')->fetchAll();  // 无参数直接 query

$st = $pdo->prepare('SELECT id, name FROM users WHERE age > ? LIMIT ?');
$st->execute([$age, 10]);
$rows = $st->fetchAll();
$one  = $st->fetch();          // 单行
$n    = $st->fetchColumn();     // 单值
CODE
, 'note' => '凡是含用户输入的 SQL 一律 prepare 绑定，杜绝注入'],
            ['title' => '增删改', 'code' => <<<'CODE'
$st = $pdo->prepare('INSERT INTO users (name, age) VALUES (?, ?)');
$st->execute(['Tom', 20]);

echo $pdo->lastInsertId();      // 新增主键

$st = $pdo->prepare('UPDATE users SET age = ? WHERE id = ?');
$st->execute([21, 1]);
echo $st->rowCount();           // 受影响行数

$pdo->prepare('DELETE FROM users WHERE id = ?')->execute([1]);
CODE
, 'note' => 'INSERT 多值批量：拼 VALUES(?,?),(?,?) 并展平参数'],
            ['title' => '命名参数与事务', 'code' => <<<'CODE'
$st = $pdo->prepare('INSERT INTO t (a, b) VALUES (:a, :b)');
$st->execute(['a' => 1, 'b' => 2]);

$pdo->beginTransaction();
try {
    $pdo->exec('UPDATE acc SET money = money - 100 WHERE id = 1');
    $pdo->exec('UPDATE acc SET money = money + 100 WHERE id = 2');
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
CODE
, 'note' => '同一 PDO 连接内才有效；InnoDB 引擎支持事务'],
            ['title' => 'UPSERT 写法', 'code' => <<<'CODE'
$pdo->prepare(
    'INSERT INTO counters (k, n) VALUES (?, 1)
     ON DUPLICATE KEY UPDATE n = n + 1'
)->execute(['visits']);
CODE
, 'note' => 'MySQL/MariaDB 专用 ON DUPLICATE KEY UPDATE，要求唯一键冲突'],
        ]],
        ['name' => 'Composer', 'entries' => [
            ['title' => '常用命令', 'code' => <<<'CODE'
composer init                       # 生成 composer.json
composer install                    # 按 lock 安装
composer require monolog/monolog    # 安装并写入
composer update                     # 更新依赖
composer dump-autoload -o          # 重建 autoload 映射
composer remove vendor/pkg
composer show                       # 列出已安装
CODE
, 'note' => '提交代码时保留 composer.lock 以锁定版本'],
            ['title' => 'composer.json 要点', 'code' => <<<'CODE'
{
  "autoload": { "psr-4": { "App\\": "src/" } },
  "require": { "php": ">=8.1", "ext-pdo": "*" },
  "scripts": { "test": "phpunit tests" }
}
CODE
, 'note' => 'src/App/Mailer.php 对应类 App\\Mailer；改后需 dump-autoload'],
            ['title' => '在代码中使用 autoload', 'code' => <<<'CODE'
require 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('app');
$log->pushHandler(new StreamHandler('app.log', Logger::WARNING));
$log->warning('watch out');
CODE
, 'note' => '入口文件只 require vendor/autoload.php 一个'],
        ]],
        ['name' => '常用技巧', 'entries' => [
            ['title' => '密码哈希', 'code' => <<<'CODE'
$hash = password_hash('pass123', PASSWORD_DEFAULT);   // 每次结果不同
$ok   = password_verify('pass123', $hash);             // true

password_needs_rehash($hash, PASSWORD_DEFAULT);       // 算法升级
CODE
, 'note' => '绝不存明文/MD5；PASSWORD_DEFAULT 自动跟进最强算法'],
            ['title' => '随机与安全值', 'code' => <<<'CODE'
$bytes = random_bytes(16);                 // 加密安全随机字节
$token = bin2hex(random_bytes(32));        // 64 位 hex token
$int   = random_int(1, 100);               // 安全整数

uniqid('', true);                          // 基于时间，非加密安全
CODE
, 'note' => 'token/盐值一律 random_bytes，不要 uniqid'],
            ['title' => '变量判断与过滤', 'code' => <<<'CODE'
isset($x) && $x !== '';       // 有效字符串
filter_var($email, FILTER_VALIDATE_EMAIL);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

var_dump($x);  print_r($arr);              // 调试输出
CODE
, 'note' => "empty(0)、empty('0')、empty('') 都视为空，判断数字 0 时注意"],
            ['title' => '数组转字符串输出（调试）', 'code' => <<<'CODE'
echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

print_r($arr, true);          // 返回字符串不输出
var_export($arr, true);       // 可直接 eval 的 PHP 语法
CODE
, 'note' => '日志记录数组优先 var_export(…, true)'],
        ]],
    ],
];
