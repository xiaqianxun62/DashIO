<?php
/* 面试必背八股 种子 - 覆盖 C#/.NET、Java、MySQL、Redis、计算机网络、前端、场景设计 */
return [
    'slug' => 'interview',
    'name' => '面试必背八股',
    'category' => '面试准备',
    'icon' => '🎯',
    'color' => '#e11d48',
    'description' => '后端 / 前端 / 桌面端高频面试题的标准答法，按技术域分组，可直接背诵',
    'sort' => 15,
    'categories' => [

        ['name' => 'C# / .NET 必背', 'entries' => [
            ['title' => '值类型与引用类型', 'code' => <<<'CODE'
① 值类型：int/double/bool/struct/enum，变量直接存数据，默认在栈（作为字段时随宿主对象在堆）
② 引用类型：class/interface/array/string/delegate，变量存堆对象的引用
③ 装箱：值类型 → object，堆分配 + 复制；拆箱：object → 值类型，类型必须匹配，有开销
④ struct 是值类型：赋值即拷贝，不能继承，不能有自定义无参构造
⑤ 判断：typeof(x).IsValueType 或 x.GetType().IsValueType
CODE
            , 'note' => '值类型的优势是「无堆分配、无 GC 压力」，但大 struct 拷贝成本高'],
            ['title' => 'string 为什么不可变', 'code' => <<<'CODE'
① 任何「修改」都会新建对象：拼接 N 次 = 分配 N 个临时字符串
② 字面量存在字符串驻留池（intern pool），相同字面量引用相同，可安全共享
③ 频繁拼接用 StringBuilder（内部 char 数组，容量不足按倍数扩容）
④ == 已被重写为值比较；但 null 与 "" 不同，判空用 IsNullOrEmpty / IsNullOrWhiteSpace
⑤ 大量拼接也可用 string.Join / 插值（内部已优化）
CODE
            , 'note' => '不可变换来线程安全和可作字典 key，代价是分配'],
            ['title' => '== 、Equals 与 GetHashCode', 'code' => <<<'CODE'
① == 是运算符，可被重载；引用类型默认比引用（string 被重载成值比较）
② Equals 是虚方法，可重写为值语义；object.Equals(a,b) 会先判 null
③ 重写 Equals 必须同时重写 GetHashCode，否则 Dictionary / HashSet 找不到
④ 值类型默认 Equals 逐字段比较（走反射，慢）
⑤ 最佳实践：实现 IEquatable<T> + 重写 GetHashCode
CODE
            , 'note' => '哈希容器先比 hash 再比 Equals，两者必须一致'],
            ['title' => '接口与抽象类怎么选', 'code' => <<<'CODE'
① 抽象类：有实现、字段、构造函数；只能单继承；适合「模板 + 部分实现」
② 接口：定义契约（C#8 起可有默认实现）；可多实现；适合「能力 / 契约」
③ 抽象类未实现的方法必须 abstract，派生类必须 override
④ 接口不能有实例字段
⑤ 判据：需要状态或共享实现 → 抽象类；需要能力组合、跨实现解耦 → 接口
CODE
            , 'note' => '框架里常见：抽象基类做骨架，接口做扩展点'],
            ['title' => '委托与事件', 'code' => <<<'CODE'
① 委托是类型安全的函数指针：Action<T>（无返回）、Func<T,R>（有返回）、Predicate<T>
② 多播委托：+= 追加、-= 移除，调用时依次执行
③ 事件是「受限委托」：外部只能 += / -=，不能直接 Invoke，保证封装
④ 发布者持有订阅者引用 → 不取消订阅就内存泄漏
⑤ 典型用途：观察者、回调、事件总线、WPF 命令
CODE
            , 'note' => 'WinForm/WPF 长生命周期控件订阅短生命周期对象时要记得解绑'],
            ['title' => 'async / await 原理', 'code' => <<<'CODE'
① async 方法被编译成状态机（MoveNext），await 处挂起、完成后继续
② await 不阻塞线程，只挂起当前方法，底层靠 Task 与线程池调度
③ 不要 .Result / .Wait()（同步阻塞易死锁）；不要 async void（事件处理器除外）
④ CPU 密集用 Task.Run；IO 用真异步 API，避免占线程
⑤ 类库中用 ConfigureAwait(false) 避免切回 UI 线程；异常通过 Task 传播
CODE
            , 'note' => 'async 不等于多线程，它省的是线程等待'],
            ['title' => '.NET GC 分代回收', 'code' => <<<'CODE'
① 三代：Gen0 新对象频繁回收、Gen1 过渡、Gen2 长寿对象（最贵）
② 大对象堆 LOH（≥ 85000 字节）直接进 Gen2，容易碎片
③ 流程：标记 → 清除/压缩；分配预算不足时触发
④ using / IDisposable 负责非托管资源，GC 管不了 Dispose
⑤ 诊断：dotnet-counters、dotnet-gcdump；减少分配比调 GC 参数更有效
CODE
            , 'note' => '面试常问：为什么 Gen0 回收快 → 存活对象少、复制成本低'],
            ['title' => 'DI 生命周期与中间件管道', 'code' => <<<'CODE'
① 三种生命周期：AddSingleton（进程唯一）、AddScoped（每请求一个）、AddTransient（每次新建）
② 构造函数注入优于服务定位器；Singleton 里注入 Scoped 会捕获根容器（内存泄漏）
③ 管道顺序即执行顺序：异常 → HTTPS → 静态文件 → 路由 → 认证 → 授权 → 端点
④ next() 之前是请求阶段，之后是响应阶段；不调 next 即短路
⑤ 自定义中间件用 RequestDelegate 组合，注册为 Scoped
CODE
            , 'note' => '中间件顺序写错，常见表现是 401 在 CORS 之前被拦掉'],
        ]],

        ['name' => 'Java 必背', 'entries' => [
            ['title' => '== 与 equals、hashCode 契约', 'code' => <<<'CODE'
① == 比栈上的值或引用地址；equals 默认比地址，String / 包装类重写为值比较
② 包装类缓存：Integer 缓存 -128~127，超出范围 == 为 false，必须用 equals
③ 重写 equals 必须重写 hashCode；相等对象 hashCode 必须相等
④ BigDecimal.equals 比 scale（1.0 != 1.00），比大小用 compareTo
⑤ 判空安全用 Objects.equals(a, b)
CODE
            , 'note' => 'Integer a=128; Integer b=128; a==b 为 false —— 高频送分题'],
            ['title' => 'String 与常量池', 'code' => <<<'CODE'
① String 不可变，底层 final char[]（Java 9 起 byte[] + coder）
② 编译期常量会合并；循环内 + 拼接每次 new StringBuilder，应显式用 StringBuilder
③ new String("a") 会在堆里再建一个对象，与常量池里的不是同一个
④ intern()：把字符串放进常量池并返回池中引用
⑤ 不可变 ⇒ 线程安全、可作 key、hashCode 可缓存
CODE
            , 'note' => '问「创建了几个对象」时先分清字面量还是 new'],
            ['title' => 'ArrayList / LinkedList / HashMap 底层', 'code' => <<<'CODE'
① ArrayList：Object[]，默认容量 10，扩容 1.5 倍；随机访问 O(1)，中间插入 O(n)
② LinkedList：双向链表，头尾 O(1)，随机访问 O(n)，每个节点额外开销
③ HashMap：数组 + 链表 + 红黑树；链表长度 ≥ 8 且容量 ≥ 64 才转树，默认 16、负载因子 0.75
④ 扩容翻倍，节点要么留在原索引，要么去「原索引 + 旧容量」
⑤ 线程安全：Hashtable / synchronizedMap / ConcurrentHashMap（推荐）
CODE
            , 'note' => '为什么是 8：泊松分布下链表长度到 8 的概率极低，兼顾空间与查询'],
            ['title' => 'ConcurrentHashMap 怎么保证并发安全', 'code' => <<<'CODE'
① JDK8：CAS + synchronized 只锁桶头结点，读不加锁（volatile 保证可见）
② 不允许 null key / value：无法区分「不存在」和「值为空」
③ size() 是估算（baseCount + CounterCell），弱一致
④ 复合操作要用 computeIfAbsent / merge / putIfAbsent 保证原子
⑤ JDK7 是分段锁 Segment，默认并发度 16
CODE
            , 'note' => '常见追问：为什么 get 不用加锁 → 节点 value 用 volatile 修饰'],
            ['title' => '线程池七大参数与拒绝策略', 'code' => <<<'CODE'
① 参数：corePoolSize、maxPoolSize、keepAlive、unit、workQueue、threadFactory、handler
② 提交顺序：核心没满建核心线程 → 入队 → 队列满且未到 max 建非核心 → 再满触发拒绝
③ 拒绝策略：AbortPolicy（抛异常，默认）、CallerRunsPolicy、DiscardPolicy、DiscardOldestPolicy
④ 无界队列（LinkedBlockingQueue 默认）会让 maxPoolSize 失效，易 OOM
⑤ 关闭：shutdown 优雅、shutdownNow 中断；自定义线程名便于排查
CODE
            , 'note' => '阿里规约不推荐 Executors 快捷方法，就是因为它隐藏了无界队列'],
            ['title' => 'synchronized、volatile 与 ReentrantLock', 'code' => <<<'CODE'
① synchronized：可重入、非公平，锁对象可为 this / Class / 指定对象；保证原子 + 可见 + 有序
② volatile：只保证可见性和禁止重排序，不保证原子性（i++ 仍不安全）
③ ReentrantLock：可中断、可超时、可公平、支持 Condition 精准唤醒，必须 finally 解锁
④ JMM happens-before：程序顺序、监视器锁、volatile、线程启动/终止、传递性
⑤ 无锁：AtomicInteger（CAS）、LongAdder（分段累加，高并发更优）
CODE
            , 'note' => '双重检查锁单例里 volatile 是为了禁止「分配内存 + 赋值 + 初始化」重排'],
            ['title' => 'JVM 内存区域与 GC', 'code' => <<<'CODE'
① 区域：堆、方法区(元空间)、虚拟机栈、本地方法栈、程序计数器；堆和方法区线程共享
② 创建对象：TLAB 分配，逃逸分析可栈上分配，大对象直接进老年代
③ 分代：Eden : S0 : S1 = 8 : 1 : 1；对象经历 15 次 MinorGC 晋升老年代
④ 收集器：Serial、ParNew、Parallel、CMS、G1（默认，Region 化、可预测停顿）、ZGC
⑤ 排查：jps、jstat -gcutil、jmap -histo、jstack（死锁）、arthas
CODE
            , 'note' => '问「怎么排查内存溢出」就答：jmap dump → MAT 看支配树 → 定位大对象'],
            ['title' => 'Spring IOC / AOP 与事务失效', 'code' => <<<'CODE'
① IOC：BeanDefinition 注册 → 实例化 → 属性填充 → 初始化 → 使用 → 销毁
② AOP：有接口用 JDK 动态代理，无接口用 CGLIB；@Transactional 本质也是 AOP
③ 事务失效：同类内部调用（不走代理）、方法非 public、异常被吞、rollbackFor 没配、跨线程
④ 作用域：singleton（默认）、prototype、request；循环依赖可用 @Lazy 或改构造注入
⑤ 传播：REQUIRED（默认）、REQUIRES_NEW、NESTED、SUPPORTS、NOT_SUPPORTED、MANDATORY、NEVER
CODE
            , 'note' => '自调用事务失效是最高频的追问点，答「this 调用没经过代理对象」'],
        ]],

        ['name' => 'MySQL 必背', 'entries' => [
            ['title' => '为什么用 B+ 树做索引', 'code' => <<<'CODE'
① B+ 树非叶子只存键和指针，叶子存数据并用双向链表相连 → 树更矮，磁盘 IO 更少
② 范围查询沿叶子链表走，天然有序，适合 order by
③ 不用 B 树：数据分散在非叶子，同样高度存更少行，范围查询要中序遍历
④ 不用哈希：不支持范围和排序，也无法最左前缀
⑤ 一页 16KB，三层 B+ 树约可存两千万行
CODE
            , 'note' => 'InnoDB 的自适应哈希索引是内部优化，不能替代 B+ 树'],
            ['title' => '聚簇索引、回表与覆盖索引', 'code' => <<<'CODE'
① 聚簇索引 = 主键索引，叶子存整行数据，一张表只有一个
② 二级索引叶子存「索引列 + 主键」，查别的列要回表再走一次聚簇索引
③ 覆盖索引：查询列全在索引中，免回表（explain Extra: Using index）
④ 最左前缀：(a,b,c) 支持 a / a,b / a,b,c；范围查询之后的列不再走索引
⑤ 索引失效：列上用函数或运算、隐式类型转换、前导模糊 like %x、or 中有无索引列
CODE
            , 'note' => '回表是随机 IO，这就是「为什么要覆盖索引」的全部理由'],
            ['title' => '事务 ACID 与 MVCC', 'code' => <<<'CODE'
① ACID：原子性(undo)、一致性、隔离性(锁 + MVCC)、持久性(redo)
② 隔离级别：读未提交、读已提交、可重复读(InnoDB 默认)、串行化
③ 异常：脏读、不可重复读、幻读（RR 下用 MVCC + 间隙锁基本解决）
④ MVCC：隐藏列 trx_id / roll_pointer + undo 版本链 + ReadView；RC 每条语句生成，RR 事务内生成
⑤ 快照读走 MVCC，当前读（for update / lock in share mode）走锁
CODE
            , 'note' => '面试必答：RR 不是完全无幻读，快照读靠 MVCC，当前读靠 Next-Key Lock'],
            ['title' => 'InnoDB 锁机制与死锁', 'code' => <<<'CODE'
① 行锁锁的是索引；where 没命中索引会退化成表锁
② 三种行锁：记录锁、间隙锁 Gap（RR 防幻读）、临键锁 Next-Key = 记录 + 前面间隙
③ 意向锁是表级锁，用来说明表内已有行锁，加快加表锁判断
④ 死锁：InnoDB 有检测，回滚代价小的事务；按固定顺序加锁可避免
⑤ 排查：show engine innodb status、information_schema.innodb_trx
CODE
            , 'note' => '间隙锁只在 RR 下生效，改成 RC 就没有 Gap Lock'],
            ['title' => 'explain 怎么看', 'code' => <<<'CODE'
① type：system > const > eq_ref > ref > range > index > ALL，生产至少要到 range
② key 实际使用的索引；key_len 用了索引的多少字节，可推断联合索引用到第几列
③ rows 预估扫描行数；filtered 过滤后剩余比例
④ Extra：Using index（覆盖索引）、Using filesort（要优化）、Using temporary（要优化）
⑤ 优化顺序：改索引 → 重写 SQL → 拆查询 → 加缓存 / 汇总表
CODE
            , 'note' => 'ALL + Using filesort 是最典型的「必须优化」组合'],
            ['title' => '慢查询排查与深分页', 'code' => <<<'CODE'
① 打开 slow_query_log，long_query_time=1，用 pt-query-digest 聚合分析
② 看执行计划：是否走索引、回表次数、排序与临时表
③ 手段：覆盖索引、去掉 select *、别在索引列上用函数、批量代替逐行
④ 深分页：limit 100000,20 → 改成 where id > 上一页最大 id，或子查询取 id 再 join
⑤ count(*) 在 InnoDB 要扫索引，可维护计数表或业务侧缓存
CODE
            , 'note' => '深分页本质是「扫了 10 万行却只返回 20 行」'],
            ['title' => '主从复制与分库分表', 'code' => <<<'CODE'
① 复制：主库 binlog → 从库 IO 线程写 relay log → SQL 线程回放
② 延迟原因：大事务、从库压力大、单线程回放（可开并行复制）
③ 读写分离要处理写后立即读（强制走主库）与延迟敏感业务
④ 分片：范围（易热点）、哈希（均匀但扩容难）、一致性哈希
⑤ 分片后问题：跨片 join、分布式事务、全局唯一 ID（雪花 / 号段 / Redis）
CODE
            , 'note' => 'Seconds_Behind_Master 只是参考值，真实延迟要用心跳表测'],
        ]],

        ['name' => 'Redis 必背', 'entries' => [
            ['title' => '数据类型与底层结构', 'code' => <<<'CODE'
① string：SDS，可存 JSON、计数、分布式锁
② hash：小数据用 listpack/ziplist，大转 hashtable
③ list：quicklist（链表 + 压缩块），可做队列
④ set：intset / hashtable，去重与交并差
⑤ zset：skiplist + dict，排行榜、延时队列；另有 bitmap、HyperLogLog、GEO、Stream
CODE
            , 'note' => 'Redis 快的原因：纯内存 + 单线程免锁 + IO 多路复用 + 高效数据结构'],
            ['title' => '持久化 RDB 与 AOF', 'code' => <<<'CODE'
① RDB：bgsave fork 子进程写快照再替换，恢复快但可能丢最后一次之后的数据
② AOF：追加写命令，appendfsync always / everysec / no；文件大但丢失窗口小
③ 4.0 混合持久化：AOF 文件前半为 RDB、后半为增量命令，兼顾速度与安全
④ 生产建议：AOF everysec + 定期 RDB 备份 + 主从
⑤ fork 时 Copy-On-Write，可能内存翻倍，要预留
CODE
            , 'note' => '问「宕机丢多少数据」→ RDB 看快照间隔，AOF everysec 最多丢 1 秒'],
            ['title' => '缓存穿透 / 击穿 / 雪崩', 'code' => <<<'CODE'
① 穿透：查不存在的数据反复打 DB → 缓存空值 + 布隆过滤器 + 参数校验
② 击穿：热点 key 过期瞬间高并发打 DB → 互斥锁重建 / 逻辑过期不删 key
③ 雪崩：大量 key 同时过期或 Redis 挂 → 过期时间加随机、多级缓存、限流降级、集群
④ 一致性：先更新 DB 再删缓存（Cache Aside）+ 延迟双删 + binlog 订阅补偿
⑤ 热点 key：本地缓存、key 拆分、读写分离
CODE
            , 'note' => '穿透是「数据不存在」，击穿是「单个热点失效」，雪崩是「一片同时失效」'],
            ['title' => '分布式锁的正确写法', 'code' => <<<'CODE'
① 加锁：SET key uuid NX PX 30000，一条命令保证原子
② 释放：Lua 脚本比对 uuid 再 DEL，防止误删别人的锁
③ 超时：业务没跑完锁已释放 → 守护线程续期（Redisson 看门狗）
④ Redlock 有时钟漂移争议，强一致场景慎用
⑤ 更稳：DB 唯一索引、ZooKeeper 临时顺序节点
CODE
            , 'note' => '面试官最爱追问「锁超时了业务还没执行完怎么办」'],
            ['title' => '过期策略与内存淘汰', 'code' => <<<'CODE'
① 过期：惰性删除（访问时判断）+ 定期删除（每 100ms 抽样）
② 淘汰：noeviction、allkeys-lru、allkeys-lfu、volatile-lru、volatile-ttl、random
③ 配置 maxmemory + maxmemory-policy
④ 大 key 删除用 UNLINK 异步，避免阻塞主线程
⑤ 分析：redis-cli --bigkeys、MEMORY USAGE key
CODE
            , 'note' => 'LRU 是近似实现（采样 24 个 key），不是精确链表'],
        ]],

        ['name' => '网络必背', 'entries' => [
            ['title' => '三次握手与四次挥手', 'code' => <<<'CODE'
① 握手：SYN(seq=x) → SYN+ACK(seq=y, ack=x+1) → ACK(ack=y+1)
② 为什么三次：两次只能确认单向，双方都需要确认「我能发且你能收」
③ 挥手：FIN → ACK → FIN → ACK
④ TIME_WAIT 等 2MSL：保证最后 ACK 到达，并让旧报文在网络中消散
⑤ 为什么四次：全双工，两个方向要各自关闭
CODE
            , 'note' => '追问「服务器大量 TIME_WAIT / CLOSE_WAIT 怎么办」：前者主动关闭多，后者是代码没 close'],
            ['title' => 'HTTP/1.1、HTTP/2、HTTP/3', 'code' => <<<'CODE'
① HTTP/1.1：keep-alive 长连接、管道化（浏览器基本不用）、队头阻塞
② HTTP/2：二进制分帧、多路复用、HPACK 头压缩、服务端推送；仍有 TCP 层队头阻塞
③ HTTP/3：跑在 QUIC(UDP) 上，0-RTT、无队头阻塞、连接迁移
④ 常见码：301/302、304、400/401/403/404/405、429、500/502/503/504
⑤ 缓存：Cache-Control、ETag + If-None-Match → 304
CODE
            , 'note' => '502 是网关拿到坏响应，504 是网关等后端超时'],
            ['title' => 'HTTPS 握手与加密', 'code' => <<<'CODE'
① 混合加密：非对称协商出会话密钥，之后用对称加密传数据
② 流程：ClientHello → 服务端证书 → 校验证书链与域名 → 协商密钥 → 对称通信
③ 校验证书：CA 签名、有效期、域名匹配、吊销状态（CRL/OCSP）
④ TLS1.3 精简为 1-RTT（支持 0-RTT），取消 RSA 密钥交换，支持前向安全
⑤ 为什么不上来就对称：密钥没法安全地先约定
CODE
            , 'note' => '前向安全 = 私钥泄露也解不开历史流量'],
            ['title' => '从输入 URL 到页面显示', 'code' => <<<'CODE'
① DNS 解析（浏览器 → 系统 → 路由器 → 递归查询）
② TCP 三次握手；HTTPS 再加 TLS 握手
③ 发请求，经 CDN/负载均衡到后端，返回响应
④ 解析 HTML 建 DOM、CSS 建 CSSOM，合成渲染树 → 布局 → 绘制 → 合成
⑤ JS 会阻塞解析（defer/async 缓解），首屏用关键 CSS 与预加载
CODE
            , 'note' => '这条题能把 DNS/TCP/TLS/HTTP/浏览器渲染串起来，适合按阶段讲'],
            ['title' => '跨域与 CORS', 'code' => <<<'CODE'
① 同源 = 协议 + 域名 + 端口全一致
② CORS：请求带 Origin，响应带 Access-Control-Allow-Origin；带 cookie 需 Allow-Credentials 且不能用 *
③ 预检 OPTIONS：自定义头、PUT/DELETE、JSON 这类先预检，用 Max-Age 缓存
④ 其他方案：JSONP（仅 GET）、反向代理、postMessage
⑤ 同源策略限制的是「读响应」，请求可能已经发出 → 这就是 CSRF 的土壤
CODE
            , 'note' => '「跨域报错但后端收到请求了」是正常的，拦截发生在浏览器'],
        ]],

        ['name' => 'TCP 与 UDP 必背', 'entries' => [
            ['title' => 'TCP 与 UDP 的本质区别', 'code' => <<<'CODE'
① TCP：面向连接、可靠、字节流、一对一；头部 20 字节起，有流量控制和拥塞控制
② UDP：无连接、不可靠、数据报（保留消息边界）、可一对一/一对多/多对多；头部 8 字节
③ TCP 是字节流，应用层要自己划消息边界（粘包问题）；UDP 天然按报文交付，没有粘包
④ 代价：TCP 要握手、重传、排队 → 时延大；UDP 不重传 → 丢包要应用层自己扛
CODE
            , 'note' => '一句话答题：TCP 用可靠性换时延，UDP 用简单换时延'],
            ['title' => 'TCP 报文头关键字段', 'code' => <<<'CODE'
① 固定 20 字节：源/目的端口、seq、ack、数据偏移、标志位、窗口、校验和、紧急指针
② 标志位：SYN 建连、ACK 确认、FIN 关闭、RST 重置、PSH 催上送、URG 紧急
③ seq 按「字节」编号不是按包；ack 是累计确认 = 期望收到的下一个字节序号
④ 窗口字段 16 位，最多 65535 字节，靠窗口缩放选项（WS）扩到 GB 级
CODE
            , 'note' => 'ack=100 表示 100 之前的字节都收到了，这叫累计确认'],
            ['title' => '三次握手，为什么不能是两次', 'code' => <<<'CODE'
① 流程：SYN(seq=x) → SYN+ACK(seq=y, ack=x+1) → ACK(ack=y+1)
② 两次只确认了「客户端能发、服务端能收」，服务端无法确认客户端能收
③ 更要紧的是防历史重复 SYN：旧连接残留的 SYN 到了，两次会建出错误连接
④ 第三次 ACK 可以携带数据，前两次不行（避免被用来放大攻击）
CODE
            , 'note' => '追问「为什么不是四次」：SYN+ACK 把两个方向的确认合并成一次'],
            ['title' => '四次挥手、TIME_WAIT 与 CLOSE_WAIT', 'code' => <<<'CODE'
① 流程：FIN → ACK → FIN → ACK，全双工，两个方向要各自关闭，所以四次
② 主动关闭方进 TIME_WAIT，等 2MSL（Linux 上约 60 秒）
③ 为什么要等：最后那个 ACK 可能丢，留时间让对方重发 FIN；同时让旧报文在网络里消散，不串到新连接
④ 大量 TIME_WAIT：短连接太多 → 改长连接/连接池、少让本机主动关闭；tcp_tw_reuse 可复用出向端口
⑤ 大量 CLOSE_WAIT：对方已关，我方进程没 close → 是代码/连接池泄漏，不是内核参数问题
CODE
            , 'note' => 'TIME_WAIT 是正常状态不是故障；CLOSE_WAIT 堆积一定是应用没释放'],
            ['title' => 'TCP 靠什么做到可靠传输', 'code' => <<<'CODE'
① 四件套：序号 + 累计确认 ACK + 超时重传 + 校验和
② RTO 不是固定值，由 RTT 采样动态估算（Karn 算法：重传样本不参与计算）
③ 快重传：连续收到 3 个重复 ACK 就立刻重传，不等超时
④ SACK（选择确认）：只重传真正丢的那段，不必把后面的全重发
⑤ 乱序靠接收端缓存重排，重复靠序号去重
CODE
            , 'note' => '必背数字：3 个重复 ACK 触发快重传，这是「丢包恢复快」的关键'],
            ['title' => '流量控制与滑动窗口', 'code' => <<<'CODE'
① 接收方用窗口字段告诉发送方「我还能收多少」（rwnd），这就是流量控制
② 实际发送窗口 = min(拥塞窗口 cwnd, 接收窗口 rwnd)
③ 窗口通告为 0 时发送方停发，靠零窗口探测（persist timer）周期性试探，防止死锁
④ 糊涂窗口综合征：小窗口、小报文太碎 → 接收端等窗口够大再通告，发送端用 Nagle 合并
CODE
            , 'note' => '流量控制是端到端（为接收方减负），拥塞控制是看网络（为链路减负）'],
            ['title' => '拥塞控制四件套与 BBR', 'code' => <<<'CODE'
① 慢启动：cwnd 从 1 个 MSS 起，每个 RTT 翻倍，到 ssthresh 转拥塞避免
② 拥塞避免：每 RTT 只加 1 个 MSS，线性增长
③ 快重传 + 快恢复：3 个重复 ACK 时 ssthresh 减半、cwnd 回到 ssthresh（不回到 1）
④ 超时判定拥塞：cwnd 归 1、ssthresh 减半，重新慢启动（这就是 AIMD：加性增、乘性减）
⑤ BBR 不看丢包，测带宽和最小 RTT 来定发送速率，长肥管道和弱网下吞吐更好
CODE
            , 'note' => 'Reno / CUBIC / BBR 是算法演进，AIMD 是回答「拥塞控制怎么工作」的主线'],
            ['title' => '粘包、拆包与 MTU / MSS', 'code' => <<<'CODE'
① TCP 是字节流，一次 recv 可能拿到半个包或两个半包 → 消息边界要应用层自己定
② 三种定界法：长度前缀（最常用）、特殊分隔符（\n、\0）、定长报文
③ 以太网 MTU=1500，MSS = 1500 - IP 头 20 - TCP 头 20 = 1460，一个 TCP 段最多 1460 字节
④ UDP 超过 MTU 会被 IP 分片，任何一片丢失整包重传丢弃，所以负载建议 ≤1472（1500-20-8）
⑤ Nagle 算法 + 延迟确认会凑出 40ms 级时延，实时交互要设 TCP_NODELAY
CODE
            , 'note' => '必背：MSS 1460；UDP 单包负载 ≤1472 可避免 IP 分片'],
            ['title' => 'SYN 队列与 SYN Flood', 'code' => <<<'CODE'
① listen 有两个队列：SYN 队列（半连接，收到 SYN 未完成握手）与 accept 队列（已完成握手待取走）
② accept 队列满时新连接被丢弃或忽略，现象是「连不上，但服务端程序看着没问题」
③ SYN Flood：伪造源 IP 狂发 SYN，半连接队列被占满，正常握手进不来
④ 防护：SYN cookies、调大 somaxconn 与 tcp_max_syn_backlog、限速/清洗，云上有 WAF
⑤ 排查：ss -lnt 看 Send-Q（backlog 上限）与 Recv-Q（当前排队），Recv-Q 贴近上限就是排队
CODE
            , 'note' => '「服务端没报错但客户端连不上」优先查 accept 队列和 backlog'],
            ['title' => 'UDP 什么时候用、怎么补可靠性', 'code' => <<<'CODE'
① 典型场景：DNS、DHCP、SNMP、音视频通话、游戏、日志上报、QUIC(HTTP/3)
② 优势：不用握手起步快、没有 TCP 层队头阻塞、支持广播/组播、内核开销小
③ 要可靠就在应用层自己做：序号 + 超时重传 + FEC 冗余 + 应用层拥塞控制（QUIC 就是这么干的）
④ UDP 也能 connect：之后内核会过滤非对端报文，还能收到 ICMP 端口不可达错误
⑤ UDP 没有流控，发太快会丢在接收缓冲区 → 需要应用层限速
CODE
            , 'note' => '「HTTP/3 为什么改用 UDP」的答案：绕开 TCP 队头阻塞，重传只在丢的那个流上做'],
            ['title' => 'keepalive、应用层心跳与长连接', 'code' => <<<'CODE'
① TCP keepalive 默认 2 小时才探一次，太慢，业务上不能依赖
② 应用层心跳（10~30 秒级）+ 超时踢除，才是判断「连接是否可用」的正确手段
③ NAT / 负载均衡会静默断开空闲连接，心跳能及时发现并触发重连
④ 心跳要带业务序号，避免半开连接上「发了但对方没收到」
⑤ 长连接必备：重连退避（指数 + 抖动）、连接数上限、消息去重、优雅下线
CODE
            , 'note' => 'SO_KEEPALIVE 只探链路，探不到进程假死（事件循环卡住），所以要应用层心跳'],
        ]],

        ['name' => '前端必背', 'entries' => [
            ['title' => '事件循环与宏微任务', 'code' => <<<'CODE'
① 顺序：同步代码 → 清空微任务队列 → 取一个宏任务 → 再清微任务 → 渲染
② 宏任务：script 本身、setTimeout、setInterval、I/O、UI 渲染
③ 微任务：Promise.then、queueMicrotask、MutationObserver
④ async/await 中 await 之后的部分是微任务
⑤ 渲染发生在微任务清空之后、下一个宏任务之前
CODE
            , 'note' => '面试手写输出顺序题，先标出微任务，再按队列排'],
            ['title' => '原型链、this、闭包', 'code' => <<<'CODE'
① 原型链：obj.__proto__ → 构造函数.prototype → … → null，属性查找沿链向上
② new 做四件事：建对象、绑原型、执行构造函数、返回对象
③ this 优先级：new > call/apply/bind > obj.fn() > 默认（严格模式为 undefined）
④ 闭包 = 函数 + 外层变量引用，可做私有变量，滥用会内存不释放
⑤ 箭头函数没有自己的 this / arguments，不能 new
CODE
            , 'note' => '「this 指向」题先判断调用方式，再看是否 bind'],
            ['title' => 'var / let / const 与暂时性死区', 'code' => <<<'CODE'
① var：函数作用域、提升为 undefined、可重复声明、挂 window
② let/const：块级作用域、声明前访问报错（TDZ）、不可重复声明
③ const 只保证绑定不变，对象内部仍可变
④ for(var i) 的回调都读到最后一个值；for(let i) 每次迭代新绑定
⑤ 实践：默认 const，需要重赋值用 let，不用 var
CODE
            , 'note' => 'TDZ 是「语法的提升但未初始化」，不是 undefined'],
            ['title' => 'Promise 与 async/await', 'code' => <<<'CODE'
① 三态：pending → fulfilled / rejected，不可逆
② then 返回新 Promise，可链式；catch 捕获链上任意异常；finally 必执行
③ all 全部成功、allSettled 等全部结束、race 第一个落定、any 第一个成功
④ async 返回 Promise，await 等价 then，异常用 try/catch
⑤ 未处理的 rejection 会告警，务必 catch
CODE
            , 'note' => 'all 有一个失败就整体 reject；需要都跑完用 allSettled'],
            ['title' => 'Vue3 响应式与 diff', 'code' => <<<'CODE'
① 响应式：Vue3 用 Proxy 拦截 get/set（可监听新增属性），Vue2 用 defineProperty 递归劫持
② 依赖收集：每个组件渲染是一个 effect，track 收集、trigger 触发
③ diff：同层比较 + key 复用；Vue3 编译期有静态提升、PatchFlag、事件缓存
④ 组合式 API：setup 中用 ref/reactive/computed/watch/onMounted，逻辑可抽 hook
⑤ 其他：Fragment 多根、Teleport、更好的 tree-shaking
CODE
            , 'note' => 'Proxy 的优势是能监听属性新增与数组索引变化'],
        ]],

        ['name' => 'WPF 必背', 'entries' => [
            ['title' => '依赖属性为什么存在', 'code' => <<<'CODE'
① 依赖属性 = DependencyObject 上的属性系统：Register 注册 + CLR 属性包装（GetValue / SetValue）
② 好处：默认值与属性继承、样式/触发器驱动、数据绑定、动画、变更通知、内存省（只存被赋过值的）
③ 取值优先级：本地值 > 动画 > 触发器 > 样式 Setter > 主题样式 > 继承 > 默认值
④ 注册：Register(name, type, ownerType, new PropertyMetadata(默认值, 变更回调))；只读用 RegisterReadOnly
⑤ 附加属性 RegisterAttached，典型是 Grid.Row、Canvas.Left 这类布局属性
CODE
            , 'note' => '高频追问「依赖属性比普通属性好在哪」→ 答绑定 + 样式 + 动画 + 省内存'],
            ['title' => '路由事件与命令', 'code' => <<<'CODE'
① 三种策略：Direct（直接）、Bubble（冒泡，子到父）、Tunnel（隧道，父到子，名字常带 Preview）
② RoutedEventArgs：Source 原始触发者、OriginalSource 最内层元素、Handled 为 true 则终止冒泡
③ 所以父容器也能挂 Button.Click；XAML 里要写 Button.Click 这种带限定的事件名
④ 命令 ICommand：Execute + CanExecute，配合 CommandBinding / InputBinding 快捷键
⑤ 相比事件，命令更利于 MVVM：View 只绑命令，业务在 ViewModel
CODE
            , 'note' => '隧道事件用来「提前拦截」，比如 PreviewKeyDown 抢回车'],
            ['title' => '数据绑定全套要点', 'code' => <<<'CODE'
① 源：Source / ElementName / RelativeSource(Self、FindAncestor、TemplatedParent) / DataContext 继承
② 模式：OneWay、TwoWay、OneTime、OneWayToSource、Default；写回时机看 UpdateSourceTrigger
③ 变更通知：属性用 INotifyPropertyChanged，集合用 ObservableCollection（实现 INotifyCollectionChanged）
④ 转换：IValueConverter、StringFormat、MultiBinding + IMultiValueConverter
⑤ 校验：IDataErrorInfo 或 INotifyDataErrorInfo，配合 Validation.ErrorTemplate 显示红框
⑥ 绑定不生效：看 Output 窗口的 binding error，或 PresentationTraceSources.TraceLevel=High
CODE
            , 'note' => 'TextBox 默认 LostFocus 才写回，要实时就设 UpdateSourceTrigger=PropertyChanged'],
            ['title' => 'MVVM 怎么落地', 'code' => <<<'CODE'
① 分工：Model 数据、ViewModel 状态与命令、View 只负责展示（尽量零代码后置）
② 命令用 RelayCommand 实现 ICommand；CanExecuteChanged 常挂 CommandManager.RequerySuggested
③ ViewModel 之间用消息机制（Messenger / EventAggregator）通信，避免 View 互相引用
④ 主流库：CommunityToolkit.Mvvm（[ObservableProperty]、[RelayCommand] 源生成器）、Prism、Caliburn.Micro
⑤ 收益：ViewModel 可单元测试，改 UI 不动业务
CODE
            , 'note' => '面试常问「MVVM 的优点」→ 可测试、可替换 UI、逻辑与视图解耦'],
            ['title' => '模板、样式与资源', 'code' => <<<'CODE'
① ControlTemplate 决定控件长什么样；DataTemplate 决定数据怎么显示
② ContentPresenter 是内容占位；ItemsControl 用 ItemTemplate + ItemContainerStyle
③ Style 由 Setter / Trigger 组成；资源放 ResourceDictionary，跨文件用 MergedDictionaries
④ StaticResource 一次查找、DynamicResource 支持运行时替换（换肤用后者）
⑤ TemplateBinding 是 ControlTemplate 内的单向简化绑定；复杂场景用 RelativeSource TemplatedParent
CODE
            , 'note' => '自定义控件用 DefaultStyleKey + Themes/Generic.xaml（可换肤），UserControl 是组合封装'],
            ['title' => 'UI 线程与 Dispatcher', 'code' => <<<'CODE'
① WPF UI 是单线程 STA + Dispatcher 消息循环；非 UI 线程直接改控件会抛 InvalidOperationException
② Dispatcher.Invoke（同步，易死锁）/ BeginInvoke（异步）/ InvokeAsync + DispatcherPriority
③ async/await 靠 SynchronizationContext 自动回到原 Dispatcher，await 之后可直接更新 UI
④ 后台计算放 Task.Run；后台任务要取消就传 CancellationToken
⑤ 大批量循环刷新要分批或限速，否则消息队列堆积导致界面卡顿
CODE
            , 'note' => 'Invoke 里再等主线程 → 死锁，这是 WPF 最经典的坑'],
            ['title' => '布局与渲染机制', 'code' => <<<'CODE'
① 两阶段布局：Measure 算 DesiredSize → Arrange 定 ActualWidth/Height；改布局属性会触发重新布局
② 面板：Grid（* 与 Auto 混合最常用）、StackPanel、DockPanel、WrapPanel、Canvas（绝对定位）
③ Logical Tree 是 XAML 结构，Visual Tree 是实际渲染结构；模板会展开出更多节点
④ RenderTransform 不影响布局（快），LayoutTransform 参与布局（慢）
⑤ 性能：冻结 Freeze 画笔/几何、长列表开 VirtualizingStackPanel、少用嵌套面板、必要时开 BitmapCache
CODE
            , 'note' => '「WPF 卡顿怎么优化」→ 先看可视化树层数与虚拟化，再看绑定与重绘频率'],
        ]],

        ['name' => 'WinForm 必背', 'entries' => [
            ['title' => '消息循环与控件本质', 'code' => <<<'CODE'
① Application.Run(Form) 启动消息泵：GetMessage → TranslateMessage → DispatchMessage
② 每个控件都是 HWND 的包装（Control.Handle），Handle 创建前设置某些属性会延迟生效
③ WndProc 处理 Windows 消息，可重写或 NativeWindow 拦截；自定义消息用 WM_USER + n
④ Application.DoEvents 会重入消息循环，容易产生重入 bug，不推荐
⑤ 消息循环被阻塞 = 界面假死，长任务必须放后台线程或 async
CODE
            , 'note' => 'WinForm 的「界面无响应」本质是消息泵没机会跑'],
            ['title' => '跨线程更新 UI', 'code' => <<<'CODE'
① 控件只能由创建它的线程访问，先判 InvokeRequired
② Invoke 同步（阻塞到执行完）、BeginInvoke 异步投递；可以封装成 SafeInvoke 扩展方法
③ 老方案 BackgroundWorker：DoWork / ProgressChanged / RunWorkerCompleted
④ 现代方案 async + await：await Task.Run 之后自动回到 UI 线程
⑤ 高频刷新要合并或节流，避免每毫秒 BeginInvoke 撑爆消息队列
CODE
            , 'note' => '跨线程直接改控件会抛 InvalidOperationException，先判断再切换线程'],
            ['title' => '窗体生命周期与模态', 'code' => <<<'CODE'
① Show 非模态、ShowDialog 模态（阻塞调用方并返回 DialogResult）
② 生命周期：Load → Shown / Activated → FormClosing → FormClosed → Dispose
③ FormClosing 里 e.Cancel = true 可拦截关闭，常用于「是否保存」提示
④ Owner（随主窗体最小化）、TopMost、MdiParent；对话框设 AcceptButton / CancelButton
⑤ 只 Dispose 自己创建的资源；订阅的外部事件记得解绑，否则窗体无法回收
CODE
            , 'note' => 'ShowDialog 关闭后不会自动 Dispose，需要 using 或手动释放'],
            ['title' => '布局：Dock、Anchor 与容器', 'code' => <<<'CODE'
① Dock 让控件贴边（Top/Bottom/Left/Right/Fill），Anchor 让控件随父容器缩放（默认 Top+Left）
② 同一控件 Dock 与 Anchor 只生效一个；Fill 控件要靠 ZOrder 决定剩余区域
③ TableLayoutPanel 做栅格（行列百分比），FlowLayoutPanel 做流式排列
④ 间距用 Margin（控件外）与 Padding（容器内）；嵌套太深同样拖慢布局
⑤ 批量增删控件务必 SuspendLayout / ResumeLayout(false, true) 包起来
CODE
            , 'note' => '先 Dock 边缘控件、最后 Dock Fill，否则 Fill 会抢占空间'],
            ['title' => '数据绑定与 DataGridView', 'code' => <<<'CODE'
① 数据源用 BindingList<T> 或 DataTable；类型实现 INotifyPropertyChanged 才会同步刷新
② 大数据量开虚拟模式 VirtualMode，用 CellValueNeeded 只给可见行数据
③ BindingSource 作中间层，支持排序、筛选、导航、EndEdit 提交
④ ComboBox/ListBox：DataSource + DisplayMember + ValueMember
⑤ 格式化用 CellFormatting，编辑提交用 CellEndEdit / EndEdit，避免频繁提交
CODE
            , 'note' => 'DataGridView 卡的原因通常是逐格赋值，改成绑定数据源后一次性赋值'],
            ['title' => '绘制与性能优化', 'code' => <<<'CODE'
① 重写 OnPaint 拿 e.Graphics；Invalidate(区域) 标记失效，Refresh 立即重绘
② 双缓冲：DoubleBuffered = true，或 SetStyle(OptimizedDoubleBuffer | AllPaintingInWmPaint, true)
③ 自定义外观用 OwnerDraw（DrawItem / DrawSubItem / DrawColumnHeader）
④ 不要在 Paint 里 new Pen/Brush/Font，用缓存字段；控件数量多时优先减少句柄
⑤ GDI 对象必须 using 释放，句柄泄漏会导致整个进程绘制异常
CODE
            , 'note' => '「界面闪烁」先开双缓冲 + SuspendLayout，再看是否频繁 Invalidate'],
            ['title' => 'WinForm 与 WPF 怎么选、怎么混用', 'code' => <<<'CODE'
① WinForm：句柄 + GDI+ 绘制，上手快、第三方控件多，适合工具类与工业上位机
② WPF：XAML 声明式、DirectX 渲染、样式模板与数据驱动，适合复杂 UI 与动画
③ 混用：WPF 里用 WindowsFormsHost 嵌 WinForm 控件；WinForm 里用 ElementHost 嵌 WPF
④ 高 DPI：WinForm 需 app.manifest + AutoScaleMode.Dpi；WPF 支持 PerMonitorV2
⑤ 老项目迁移常见做法：WinForm 外壳 + 逐步用 WPF 重写关键视图
CODE
            , 'note' => '混合宿主有「空气泡」问题（层级与变换限制），别用来做全屏动画'],
        ]],

        ['name' => '场景与设计必背', 'entries' => [
            ['title' => '接口幂等怎么设计', 'code' => <<<'CODE'
① 定义：同一请求执行多次，结果一致
② 手段：唯一索引兜底、幂等 token（先领后提交）、状态机、乐观锁版本号
③ 下单支付：订单号做唯一键，重复插入失败即返回已成功
④ 消息消费：消费记录表 + 唯一键，或按业务主键 upsert
⑤ 分布式锁只能减少并发，不能替代幂等
CODE
            , 'note' => '答这题一定要给「数据库兜底」这一层，只靠 Redis 不可信'],
            ['title' => '分布式事务方案', 'code' => <<<'CODE'
① 2PC：准备 → 提交；强一致但阻塞、协调者单点
② TCC：Try 预留 / Confirm 确认 / Cancel 释放；业务侵入大
③ 本地消息表 + 定时补偿：最常用，最终一致
④ 事务消息：半消息 → 本地事务 → 提交 / 回查
⑤ Saga：长事务拆成多个本地事务 + 补偿；要求幂等、可补偿、可重试
CODE
            , 'note' => '面试更看重你能说清「不一致时怎么补偿」'],
            ['title' => '消息队列常见问题', 'code' => <<<'CODE'
① 重复消费：至少一次语义 → 消费端幂等
② 顺序：同一业务 key 路由到同一分区，单消费者串行处理
③ 堆积：扩分区扩消费者、临时跳过非核心消息、死信队列兜底
④ 延时消息解决超时关单；事务消息解决分布式事务
⑤ 选型：Kafka 吞吐与日志、RocketMQ 事务与顺序、RabbitMQ 灵活路由
CODE
            , 'note' => '「怎么保证不丢」：生产确认 + 持久化 + 消费手动 ACK'],
            ['title' => '限流与秒杀设计', 'code' => <<<'CODE'
① 限流算法：计数器（有临界问题）、滑动窗口、漏桶（恒定速率）、令牌桶（允许突发）
② 单机 Semaphore / RateLimiter；分布式用 Redis + Lua 原子计数
③ 秒杀：前端限流 + 库存预热到 Redis + Lua 扣减 + MQ 异步下单 + 结果轮询
④ 防超卖：Redis 原子扣减兜底，DB 唯一索引或乐观锁二次校验
⑤ 降级：排队页、验证码、热点隔离
CODE
            , 'note' => '核心思路永远是「把流量挡在 DB 之前」'],
            ['title' => '常用设计模式', 'code' => <<<'CODE'
① 单例：双重检查 + volatile；或静态内部类、枚举（最安全）
② 工厂：简单工厂 / 工厂方法 / 抽象工厂；DI 容器本质是工厂 + 单例
③ 策略：把 if-else 分支抽成策略，用 map 分发
④ 观察者 / 发布订阅：事件总线、MQ；注意取消订阅防泄漏
⑤ 装饰器与代理：AOP、日志、权限、缓存切面
CODE
            , 'note' => '答题套路：先说解决什么问题，再说项目里怎么用的'],
        ]],
    ],
];
