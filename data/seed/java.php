<?php
/* Java 速查种子 */
return [
    'slug' => 'java',
    'name' => 'Java',
    'category' => '后端开发',
    'icon' => '☕',
    'color' => '#E76F00',
    'description' => 'Java 基础语法、集合、多线程与 Stream 速查',
    'sort' => 21,
    'categories' => [

        ['name' => '基础语法', 'entries' => [
            ['title' => '程序入口', 'code' => <<<'CODE'
public class Main {
    public static void main(String[] args) {
        System.out.println("Hello, Java!");
        for (String a : args) System.out.println(a);
    }
}
CODE
, 'note' => '文件名须与 public 类名一致（一个文件一个 public 类）'],
            ['title' => '数据类型', 'code' => <<<'CODE'
int i = 42;            long l = 42_000_000L;
double d = 3.14;       float f = 1.5f;
char c = 'A';          boolean ok = true;
String s = "str";      // 引用类型

Integer boxed = 42;    // 包装类（自动装箱）
var list = new ArrayList<String>();  // 10+ 类型推断
CODE
, 'note' => '基本类型栈上直接存值；包装类是对象，比较用 equals'],
            ['title' => '类型转换', 'code' => <<<'CODE'
int x = 100;
long big = x;                    // 自动拓宽
int back = (int) bigL;          // 强制窄化

String s = String.valueOf(42);
int n = Integer.parseInt("42");
double d = Double.parseDouble("3.14");
String hex = Integer.toHexString(255);
CODE
, 'note' => 'parseXxx 遇非数字抛 NumberFormatException，需处理'],
            ['title' => '运算符要点', 'code' => <<<'CODE'
== vs equals();        // 引用 vs 内容
"abc".equals(other);   // 防空指针写法
Objects.equals(a, b);  // null 安全

10 / 3 == 3;           // 整数除法
10 % 3 == 1;
2 << 3 == 16;           // 位运算
a ? x : y;              // 三元
CODE
, 'note' => '字符串比较永远用 equals；字面量 == 偶然成立是缓存陷阱'],
            ['title' => '流程控制', 'code' => <<<'CODE'
if (score > 90) { }
else if (score > 60) { }
else { }

switch (day) {
    case 1, 7 -> "weekend";     // Java 14+
    default -> "workday";
}

// 传统 switch
switch (level) { case 1: break; default: break; }
CODE
, 'note' => 'switch 表达式（->）无需 break，直接返回值'],
            ['title' => '循环与标签', 'code' => <<<'CODE'
for (int i = 0; i < 10; i++) { }
for (String s : list) { }
while (cond) { }
do { } while (cond);

outer:
for (int i = 0; i < 3; i++)
  for (int j = 0; j < 3; j++)
    if (j == 1) continue outer;
CODE
, 'note' => '标签跳出多层循环，但优先改写方法结构'],
        ]],

        ['name' => '字符串', 'entries' => [
            ['title' => 'String 常用方法', 'code' => <<<'CODE'
s.length();
s.charAt(0); s.indexOf("ab"); s.contains("x");
s.substring(1, 3); s.replace('a', 'b');
s.split(",\\s*");
s.trim() / s.strip();          // strip 处理全角空白
s.toUpperCase() / s.toLowerCase();
s.startsWith("/api") / s.endsWith(".php");
s.isBlank(); s.isEmpty();
String.join("-", "a", "b");
"a".repeat(3);                 // aaa
CODE
, 'note' => 'String 不可变，所有方法都返回新对象'],
            ['title' => '格式化输出', 'code' => <<<'CODE'
String.format("%s 得 %.2f 分", name, score);
System.out.printf("%-10d %05.2f%n", 42, 3.1);

// Java 15+ 文本块
String tpl = """
    Hello %s
    """.formatted(name);
CODE
, 'note' => '%n 平台换行；文本块里可用 %s 再 formatted'],
            ['title' => 'StringBuilder', 'code' => <<<'CODE'
StringBuilder sb = new StringBuilder();
sb.append("a").append(1).append(true);
sb.insert(0, "x");
sb.reverse();
String out = sb.toString();

// 循环拼接必须用它，不要 + 拼接
for (var s : items) sb.append(s).append(",");
CODE
, 'note' => '循环里 + 拼接每次都新建对象，性能差十倍以上'],
            ['title' => '比较与转义', 'code' => <<<'CODE'
"a".compareTo("b");      // -1（字典序）
s.equals(t) / s.equalsIgnoreCase(t);
s.compareToIgnoreCase(t);

"说\"引号\"" + "路径\\src" + "换行\n";
Pattern.quote(s);        // 转义成正则字面量
CODE
, 'note' => 'compareTo 返回差值符号，常用于排序器'],
        ]],

        ['name' => '数组与集合', 'entries' => [
            ['title' => '数组', 'code' => <<<'CODE'
int[] a = {1, 2, 3};
int[] b = new int[5];            // 默认 0
int[][] grid = new int[3][4];

a.length;                         // 属性
Arrays.toString(a);               // [1, 2, 3]
Arrays.sort(a);
Arrays.copyOf(a, 10);
int pos = Arrays.binarySearch(a, 2);
CODE
, 'note' => '数组是 length 属性；集合是 size() 方法'],
            ['title' => 'List', 'code' => <<<'CODE'
List<String> list = new ArrayList<>();
list.add("a"); list.add(0, "b");
list.get(0); list.set(0, "x");
list.remove("a"); list.remove(0);
list.size(); list.contains("a");
list.isEmpty(); list.clear();

List<String> fixed = List.of("a", "b");  // 不可变
List<Integer> copy = new ArrayList<>(fixed);
CODE
, 'note' => 'List.of 快捷常量但不可变；需要增删先包 ArrayList'],
            ['title' => 'Map', 'code' => <<<'CODE'
Map<String, Integer> m = new HashMap<>();
m.put("a", 1);
m.get("a");                        // null 若无
m.getOrDefault("a", 0);
m.putIfAbsent("b", 2);
m.computeIfAbsent("k", k -> load(k));
m.merge("cnt", 1, Integer::sum);  // 计数利器
m.containsKey("a"); m.remove("a");

for (var e : m.entrySet()) { e.getKey(); e.getValue(); }
CODE
, 'note' => 'LinkedHashMap 保插入序；TreeMap 排序；并发用 ConcurrentHashMap'],
            ['title' => 'Set 与 Queue', 'code' => <<<'CODE'
Set<String> set = new HashSet<>();
set.add("a"); set.contains("a"); set.remove("a");

Set<Integer> tree = new TreeSet<>();       // 排序
Set<Integer> linked = new LinkedHashSet<>(); // 保序

Queue<Integer> q = new LinkedList<>();
q.offer(1); q.poll(); q.peek();            // 失败返 null
Deque<Integer> stack = new ArrayDeque<>();
stack.push(1); stack.pop(); stack.peek();
CODE
, 'note' => 'ArrayDeque 做栈比 LinkedList 快且省内存'],
            ['title' => '遍历与工具类', 'code' => <<<'CODE'
list.forEach(x -> System.out.println(x));
m.forEach((k, v) -> System.out.println(k + "=" + v));

Collections.sort(list);
Collections.reverse(list);
Collections.shuffle(list);
Collections.max(list);  Collections.min(list);
Collections.unmodifiableList(list);

List<Integer> synced = Collections.synchronizedList(list);
CODE
, 'note' => '不可变包装仍有运行时修改异常，尽早失败'],
        ]],

        ['name' => '面向对象', 'entries' => [
            ['title' => '类定义', 'code' => <<<'CODE'
public class Person {
    private final String name;      // final 字段
    private int age;

    public Person(String name, int age) {   // 构造器
        this.name = name;
        this.age = age;
    }

    public String getName() { return name; }

    @Override
    public String toString() { return name + ":" + age; }
}
CODE
, 'note' => '字段私有 + getter/setter 是 POJO 基本规范'],
            ['title' => '继承与多态', 'code' => <<<'CODE'
class Animal { void speak() { } }
class Dog extends Animal {
    @Override void speak() { System.out.println("汪"); }
}

Animal a = new Dog();   // 父类引用指向子类对象
a.speak();              // 动态绑定调 Dog 版本

a instanceof Dog;        // 类型判断
if (a instanceof Dog d) { d.speak(); }   // 16+ 模式变量
CODE
, 'note' => 'instanceof 模式匹配一步完成判断+转换'],
            ['title' => '抽象类与接口', 'code' => <<<'CODE'
abstract class Shape {
    abstract double area();
    double perimeter() { return 0; }   // 可有实现
}

interface Flyable {
    void fly();
    default void land() { }            // 默认方法
    static Flyable of() { return null; }
}

class Bird extends Shape implements Flyable { }
CODE
, 'note' => '接口表达能力(can-do)，抽象类抽公共状态(is-a)'],
            ['title' => 'record 记录类（Java 16）', 'code' => <<<'CODE'
public record Point(int x, int y) {
    double dist() { return Math.hypot(x, y); }
}

Point p = new Point(3, 4);
p.x();  p.y();               // 访问器（非 getX）
p.equals(new Point(3, 4));   // 自动实现
Objects.toString(p);         // Point[x=3, y=4]
CODE
, 'note' => 'record 自动生成构造器/equals/hashCode/toString，不可变'],
            ['title' => '枚举', 'code' => <<<'CODE'
enum Status {
    ACTIVE("启用"), DISABLED("禁用");

    private final String label;
    Status(String label) { this.label = label; }

    public String label() { return label; }
}

Status s = Status.valueOf("ACTIVE");
Status[] all = Status.values();
CODE
, 'note' => 'valueOf 名称不存在会抛异常，注意输入校验'],
            ['title' => '内部类速览', 'code' => <<<'CODE'
class Outer {
    class Inner { }                 // 成员内部类
    static class Nested { }         // 静态嵌套类
    Runnable r = new Runnable() {   // 匿名类
        public void run() { }
    };
}
Outer.Inner i = new Outer().new Inner();
CODE
, 'note' => 'Lambda 时代匿名类仅用于旧接口实现'],
        ]],

        ['name' => '异常处理', 'entries' => [
            ['title' => 'try-catch-finally', 'code' => <<<'CODE'
try {
    int n = Integer.parseInt(s);
} catch (NumberFormatException | NullPointerException e) {
    log.error("解析失败", e);          // 多类型合并捕获
} catch (Exception e) {
    throw new RuntimeException("包装", e);  // 保留原因链
} finally {
    cleanup();                        // 必执行
}
CODE
, 'note' => 'finally 里别 return，会吞掉异常'],
            ['title' => 'try-with-resources', 'code' => <<<'CODE'
try (var in = new FileInputStream("a.txt");
     var out = new FileOutputStream("b.txt")) {
    in.transferTo(out);
}   // 自动 close，顺序与声明相反

class Res implements AutoCloseable {
    public void close() { }
}
CODE
, 'note' => '任何资源对象实现 AutoCloseable 即可自动关闭'],
            ['title' => '受检 vs 非受检', 'code' => <<<'CODE'
class BizException extends Exception { }      // 受检：必须捕获/声明
class AppException extends RuntimeException { } // 非受检：随意抛

void f() throws BizException { }               // 声明抛出

throw new AppException("参数错误");
CODE
, 'note' => '受检异常强制调用方处理；业务错误常用非受检'],
        ]],

        ['name' => '泛型与反射（要点）', 'entries' => [
            ['title' => '泛型基础', 'code' => <<<'CODE'
class Box<T> {
    private T item;
    public T get() { return item; }
    public void set(T item) { this.item = item; }
}

class Pair<K, V> { K key; V val; }

List<? extends Number> nums;   // 上界：只读
List<? super Integer> sink;   // 下界：只写
<T extends Comparable<T>> T max(List<T> l) { return null; }
CODE
, 'note' => 'PECS 原则：生产者 extends、消费者 super'],
            ['title' => '注解与自定义注解', 'code' => <<<'CODE'
@Override @Deprecated @SuppressWarnings("unchecked")
@FunctionalInterface
@SafeVarargs

// 自定义注解
@Retention(RetentionPolicy.RUNTIME)
@Target(ElementType.METHOD)
@interface Log {
    String value() default "";
}
CODE
, 'note' => 'RUNTIME 保留才能运行时反射读取'],
            ['title' => '反射基本操作', 'code' => <<<'CODE'
Class<?> c = obj.getClass();         // 或 Foo.class / Class.forName("com.Foo")

c.getName(); c.getSimpleName();
var m = c.getMethod("speak");
m.invoke(obj);                        // 调用方法

c.getDeclaredField("name").setAccessible(true);
c.getDeclaredConstructor().newInstance();   // 反射实例化
CODE
, 'note' => '反射慢且绕过封装，框架内部才大规模使用'],
        ]],

        ['name' => 'Lambda 与 Stream', 'entries' => [
            ['title' => 'Lambda 与函数接口', 'code' => <<<'CODE'
Runnable r = () -> System.out.println("go");
Comparator<String> cmp = (a, b) -> a.length() - b.length();

list.sort(Comparator.comparing(String::length));
list.forEach(System.out::println);       // 方法引用

Supplier<Integer> sup = () -> 42;
Function<String, Integer> len = String::length;
Predicate<String> notBlank = s -> !s.isBlank();
BiFunction<Integer, Integer, Integer> add = (a, b) -> a + b;
CODE
, 'note' => '常用函数接口：Supplier/Function/Consumer/Predicate'],
            ['title' => 'Stream 创建', 'code' => <<<'CODE'
Stream.of(1, 2, 3);
Arrays.stream(arr);
list.stream();
Stream.iterate(1, n -> n * 2).limit(5);
Stream.generate(Math::random).limit(3);
IntStream.range(0, 10);
IntStream.rangeClosed(1, 5);
"abc".chars();
Files.lines(Path.of("a.txt"));        // 行流
CODE
, 'note' => 'Stream 惰性求值，无终端操作不执行'],
            ['title' => '中间操作', 'code' => <<<'CODE'
list.stream()
    .filter(x -> x > 10)               // 过滤
    .map(x -> x * 2)                   // 映射
    .mapToInt(Integer::intValue)       // 数值流
    .flatMap(x -> Stream.of(x, x))     // 展平
    .distinct()
    .sorted()
    .sorted(Comparator.reverseOrder())
    .limit(10)
    .skip(5)
    .peek(System.out::println);        // 调试窥视
CODE
, 'note' => 'peek 只用于调试日志，别夹带业务逻辑'],
            ['title' => '终端操作', 'code' => <<<'CODE'
.collect(Collectors.toList());
.collect(Collectors.toMap(u -> u.id, u -> u.name));
.collect(Collectors.groupingBy(u -> u.dept));
.collect(Collectors.joining(","));
.collect(Collectors.counting());

.count(); .findFirst(); .findAny();
.anyMatch(x -> x > 1); .allMatch(...); .noneMatch(...)
.forEach(x -> log(x));
.reduce(0, Integer::sum);
.max(Comparator.comparingInt(x -> x));
.sum() / .average() / .summaryStatistics()
CODE
, 'note' => 'collect 是归宿；groupingBy 是 SQL GROUP BY 的翻版'],
        ]],

        ['name' => '线程与并发', 'entries' => [
            ['title' => '创建线程', 'code' => <<<'CODE'
Thread t = new Thread(() -> System.out.println("run"));
t.start();                 // 别调 run()
t.join(1000);              // 等待结束
Thread.sleep(500);
Thread.currentThread().getName();
Thread.yield();
CODE
, 'note' => 'start 才新线程执行；直接 run 只是普通调用'],
            ['title' => '线程池', 'code' => <<<'CODE'
ExecutorService pool = Executors.newFixedThreadPool(4);
ExecutorService cached = Executors.newCachedThreadPool();
ScheduledExecutorService sched =
        Executors.newScheduledThreadPool(2);

pool.submit(() -> work());            // 返回 Future
Future<Integer> f = pool.submit(() -> calc());
f.get(3, TimeUnit.SECONDS);           // 等结果
sched.scheduleAtFixedRate(task, 0, 1, TimeUnit.SECONDS);

pool.shutdown(); pool.shutdownNow();
CODE
, 'note' => '生产环境用 ThreadPoolExecutor 手动参数；submit 返回值用 Future'],
            ['title' => '锁与同步', 'code' => <<<'CODE'
synchronized (lock) { critical(); }      // 对象锁

synchronized void method() { }          // 锁 this

private final Object lock = new Object();
ReentrantLock rl = new ReentrantLock();
rl.lock(); try { } finally { rl.unlock(); }

private volatile boolean running;      // 可见性
CODE
, 'note' => 'volatile 只保证可见性与有序性，不保证原子性'],
            ['title' => '原子类与并发容器', 'code' => <<<'CODE'
AtomicInteger count = new AtomicInteger();
count.incrementAndGet();
count.compareAndSet(1, 2);
LongAdder sum = new LongAdder();     // 高并发计数更快

ConcurrentHashMap<String, Integer> cm;
cm.computeIfAbsent("k", k -> load(k));

BlockingQueue<Task> queue = new LinkedBlockingQueue<>(100);
queue.put(t); queue.take();          // 阻塞收发
CODE
, 'note' => '并发计数优先 LongAdder，热键竞争少一半'],
            ['title' => 'CompletableFuture', 'code' => <<<'CODE'
CompletableFuture.supplyAsync(() -> fetch())
    .thenApply(data -> parse(data))
    .thenAccept(r -> save(r))
    .exceptionally(e -> fallback());

CompletableFuture.allOf(f1, f2).join();
CompletableFuture.anyOf(f1, f2);
supplyAsync(() -> a(), pool);         // 指定线程池
CODE
, 'note' => '默认用 ForkJoinPool；IO 密集任务请传自定义池'],
        ]],

        ['name' => '日期与常用类', 'entries' => [
            ['title' => '现代日期 API', 'code' => <<<'CODE'
LocalDate date = LocalDate.of(2026, 11, 16);
LocalDate now = LocalDate.now();
LocalDateTime dt = LocalDateTime.now();
LocalTime t = LocalTime.of(10, 30);

date.plusDays(1); date.minusMonths(2);
date.isBefore(other); date.isAfter(other);
Period.between(a, b).getDays();
date.format(DateTimeFormatter.ofPattern("yyyy-MM-dd"));
LocalDate.parse("2026-11-16");

ZonedDateTime zdt = ZonedDateTime.now(ZoneId.of("Asia/Shanghai"));
Instant.now().toEpochMilli();
Duration.between(start, end).toMinutes();
CODE
, 'note' => '都用 java.time，别再用旧 Date/Calendar'],
            ['title' => 'Objects 与比较', 'code' => <<<'CODE'
Objects.equals(a, b);
Objects.requireNonNull(arg, "arg 必填");
Objects.isNull(x); Objects.nonNull(x);
Objects.hash(a, b); Objects.toString(x, "默认");

Comparator.comparing(Person::getName)
          .thenComparing(Person::getAge)
          .reversed();
CODE
, 'note' => 'Comparator 链式多字段排序标准写法'],
            ['title' => 'BigDecimal（金额）', 'code' => <<<'CODE'
BigDecimal a = new BigDecimal("0.1");      // 字符串构造！
a.add(b); a.subtract(b);
a.multiply(b); a.divide(b, 2, RoundingMode.HALF_UP);

a.compareTo(b) > 0;                        // 比较大小
a.equals(new BigDecimal("1.0"));           // false！
a.stripTrailingZeros().toPlainString();
CODE
, 'note' => 'new BigDecimal(0.1) 有精度陷阱，必须字符串构造'],
            ['title' => '文件与 IO（NIO）', 'code' => <<<'CODE'
Path p = Path.of("data/a.txt");
Files.writeString(p, "hello\n", StandardCharsets.UTF_8);
String s = Files.readString(p);
List<String> lines = Files.readAllLines(p);
Files.exists(p); Files.createDirectories(Path.of("a/b"));
Files.copy(src, dst); Files.delete(p);

try (var walk = Files.walk(Path.of("."))) {
    walk.filter(f -> f.toString().endsWith(".java")).forEach(...);
}
CODE
, 'note' => '小文件直接 readString/writeString；大文件用流'],
        ]],

    ],
];
