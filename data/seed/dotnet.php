<?php
/* .NET (ASP.NET Core) 速查种子 */
return [
    'slug' => 'dotnet',
    'name' => '.NET / ASP.NET Core',
    'category' => '.NET 生态',
    'icon' => '🟣',
    'color' => '#512BD4',
    'description' => '.NET 体系、Web API、依赖注入、EF Core 与 CLI 速查',
    'sort' => 41,
    'categories' => [

        ['name' => '.NET 体系', 'entries' => [
            ['title' => '版本与平台', 'code' => <<<'CODE'
.NET Framework 4.x     -- 仅 Windows，维护模式
.NET Core 3.1         -- 已 EOL
.NET 5/6/7/8/9        -- 统一平台（Windows/Linux/macOS）
.NET Standard 2.0     -- 库的兼容契约（新库直接 net8.0）

运行时组件：CLR + BCL(基础库) + ASP.NET Core(WPF/WinForm/MAUI)
CODE
, 'note' => '新项目选 net8.0（LTS）；.NET Framework 仅旧系统维护'],
            ['title' => 'dotnet CLI', 'code' => <<<'CODE'
dotnet new webapi -n MyApp -o .
dotnet new console -n Tool
dotnet build                          # 编译
dotnet run                            # 直接运行
dotnet publish -c Release -o out      # 发布
dotnet test                           # 跑测试
dotnet watch run                      # 热重载开发
dotnet format                         # 格式化

dotnet add package Newtonsoft.Json   # 加包
dotnet tool install -g dotnet-ef      # 全局工具
dotnet ef migrations add Init         # EF 迁移
dotnet ef database update
CODE
, 'note' => 'dotnet watch 是日常开发主力命令'],
            ['title' => 'csproj 要点', 'code' => <<<'CODE'
<Project Sdk="Microsoft.NET.Sdk.Web">
  <PropertyGroup>
    <TargetFramework>net8.0</TargetFramework>
    <Nullable>enable</Nullable>
    <ImplicitUsings>enable</ImplicitUsings>
    <InvariantGlobalization>true</InvariantGlobalization>
  </PropertyGroup>
  <ItemGroup>
    <PackageReference Include="Serilog.AspNetCore" Version="8.0.1" />
  </ItemGroup>
</Project>
CODE
, 'note' => 'SDK 工程隐式包含所有 .cs，不用手动列文件'],
        ]],

        ['name' => 'Minimal API', 'entries' => [
            ['title' => '最小服务骨架', 'code' => <<<'CODE'
var builder = WebApplication.CreateBuilder(args);

builder.Services.AddScoped<IUserService, UserService>();

var app = builder.Build();

app.MapGet("/hello", () => "world");
app.MapGet("/users/{id:int}", (int id) => GetUser(id));
app.MapPost("/users", (UserDto dto) => Results.Created($"/users/{dto.Id}", dto));
app.MapPut("/users/{id}", (int id, UserDto dto) => Results.NoContent());
app.MapDelete("/users/{id}", (int id) => Results.Ok());

app.Run();
CODE
, 'note' => '小服务用 Minimal API，六行起一个 Web 服务'],
            ['title' => '路由参数与查询', 'code' => <<<'CODE'
app.MapGet("/users/{id:int}/{name?}", (int id, string? name) => $"id={id},name={name}");

app.MapGet("/search", (string? q, int page = 1, int size = 20) =>
    $"q={q}, page={page}/{size}");

app.MapGet("/file/{*path}", (string path) => path);   // 通配
CODE
, 'note' => '{id:int} 约束路由；? 查询串参数自动绑定'],
            ['title' => '结果类型', 'code' => <<<'CODE'
Results.Ok(data);
Results.Created($"/users/{id}", data);
Results.NoContent();               // 204
Results.BadRequest(errors);
Results.NotFound();
Results.Conflict();
Results.StatusCode(418);
Results.Json(obj);                 // 直接对象也行
Results.Problem(statusCode: 500, title: "服务器错误");
CODE
, 'note' => '统一 Results.* 保持响应规范'],
        ]],

        ['name' => '控制器 Web API', 'entries' => [
            ['title' => 'Controller 模板', 'code' => <<<'CODE'
[ApiController]
[Route("api/[controller]")]
public class UsersController : ControllerBase
{
    private readonly IUserService _svc;
    public UsersController(IUserService svc) => _svc = svc;

    [HttpGet]
    public async Task<List<User>> List() => await _svc.ListAsync();

    [HttpGet("{id}")]
    public async Task<IActionResult> Get(int id)
    {
        var u = await _svc.FindAsync(id);
        return u is null ? NotFound() : Ok(u);
    }

    [HttpPost]
    public async Task<IActionResult> Create(UserDto dto)
        => CreatedAtAction(nameof(Get), new { id = dto.Id }, dto);

    [HttpDelete("{id}")]
    public async Task<IActionResult> Delete(int id)
        => await _svc.DeleteAsync(id) ? NoContent() : NotFound();
}
CODE
, 'note' => '[controller] 占位符自动取类名去掉 Controller 后缀'],
            ['title' => '模型绑定来源', 'code' => <<<'CODE'
public void Post(
    [FromRoute] int id,           // 路由
    [FromQuery] string? q,        // 查询串
    [FromHeader(Name="X-Token")] string token,
    [FromBody] UserDto dto,       // 请求体 JSON
    [FromForm] IFormFile file)    // 表单/文件
{ }
CODE
, 'note' => '复杂类型默认 FromBody，简单类型默认 FromRoute/Query'],
            ['title' => '验证与特性', 'code' => <<<'CODE'
public class UserDto
{
    [Required] public string Name { get; set; } = "";
    [Range(1, 120)] public int Age { get; set; }
    [EmailAddress] public string? Email { get; set; }
    [MaxLength(50)] public string? Nick { get; set; }
}

// ControllerBase 中：
if (!ModelState.IsValid)
    return BadRequest(ModelState);
CODE
, 'note' => '[ApiController] 会自动校验 400 返回错误详情'],
            ['title' => 'DTO 与映射', 'code' => <<<'CODE'
// 手写映射
public static UserDto ToDto(this User u)
    => new() { Id = u.Id, Name = u.Name };

var dtos = users.Select(u => u.ToDto());

// AutoMapper（包）
cfg.CreateMap<User, UserDto>();
var dto = mapper.Map<UserDto>(user);
CODE
, 'note' => '简单项目手写扩展方法更透明可控'],
        ]],

        ['name' => '中间件与配置', 'entries' => [
            ['title' => '中间件管道', 'code' => <<<'CODE'
var app = builder.Build();

app.UseExceptionHandler("/error");   // 顺序即执行顺序
app.UseHttpsRedirection();
app.UseStaticFiles();
app.UseCors("policy");
app.UseAuthentication();
app.UseAuthorization();
app.MapControllers();

// 自定义中间件
app.Use(async (ctx, next) =>
{
    var sw = Stopwatch.StartNew();
    await next();
    Console.WriteLine($"{ctx.Request.Path} {sw.ElapsedMilliseconds}ms");
});
CODE
, 'note' => 'UseAuthentication 必须在 UseAuthorization 之前'],
            ['title' => 'appsettings.json', 'code' => <<<'CODE'
{
  "ConnectionStrings": {
    "Default": "server=127.0.0.1;database=app;user=root;pwd=x"
  },
  "Logging": { "LogLevel": { "Default": "Information" } },
  "Jwt": { "Key": "...", "ExpireDays": 7 },
  "AllowedHosts": "*"
}

// 读取
var cs = builder.Configuration.GetConnectionString("Default");
var days = builder.Configuration.GetValue<int>("Jwt:ExpireDays");
CODE
, 'note' => 'appsettings.Development.json 会覆盖同名键'],
            ['title' => '环境与 Options', 'code' => <<<'CODE'
var env = builder.Environment;   // Development/Staging/Production

builder.Services.Configure<JwtOptions>(
    builder.Configuration.GetSection("Jwt"));

public class Svc
{
    private readonly JwtOptions _opt;
    public Svc(IOptions<JwtOptions> opt) => _opt = opt.Value;
}
CODE
, 'note' => '强类型配置绑定：IOptions<T>，热更新用 IOptionsSnapshot'],
        ]],

        ['name' => '依赖注入', 'entries' => [
            ['title' => '三种生命周期', 'code' => <<<'CODE'
builder.Services.AddTransient<IEmail, Email>();   // 每次注入都新建
builder.Services.AddScoped<DbContext, AppDb>();   // 一个请求一个
builder.Services.AddSingleton<ICache, MemoryCache>(); // 全局唯一

// 泛型注册
builder.Services.AddScoped<IRepo<User>, UserRepo>();
builder.Services.AddScoped(typeof(IRepo<>), typeof(Repo<>));
CODE
, 'note' => 'Scoped 不能注入 Singleton；EF DbContext 必须 Scoped'],
            ['title' => '注入与释放', 'code' => <<<'CODE'
public class OrderService
{
    public OrderService(
        ILogger<OrderService> logger,
        IOptions<AppOptions> opt,
        IEnumerable<IHandler> handlers) { }
}

builder.Services.AddHostedService<TimedService>();   // 后台服务
public class TimedService : BackgroundService
{
    protected override async Task ExecuteAsync(CancellationToken ct)
        => await DoLoop(ct);
}
CODE
, 'note' => '实现 IDisposable 的服务容器自动释放'],
        ]],

        ['name' => 'EF Core', 'entries' => [
            ['title' => '实体与上下文', 'code' => <<<'CODE'
public class User
{
    public int Id { get; set; }
    public string Name { get; set; } = "";
    public DateTime CreatedAt { get; set; }
    public List<Order> Orders { get; set; } = new();
}

public class AppDb : DbContext
{
    public DbSet<User> Users => Set<User>();
    public DbSet<Order> Orders => Set<Order>();

    protected override void OnConfiguring(DbContextOptionsBuilder o)
        => o.UseMySQL("server=127.0.0.1;database=app;user=root;pwd=x");
}
CODE
, 'note' => 'Web 项目把连接串放配置文件并注入 DbContextOptions'],
            ['title' => '增删改', 'code' => <<<'CODE'
db.Users.Add(new User { Name = "Tom" });
await db.SaveChangesAsync();          // 统一保存

db.Users.Update(u);
db.Users.Remove(u);

var n = await db.Users.Where(u => u.Age > 18).ExecuteDeleteAsync();  // 批量删
CODE
, 'note' => '先查再删会两次往返；ExecuteDelete 直接 SQL 批删'],
            ['title' => '查询', 'code' => <<<'CODE'
var list = await db.Users
    .Where(u => u.Age > 18)
    .OrderByDescending(u => u.CreatedAt)
    .Skip(10).Take(20)
    .ToListAsync();

var first = await db.Users.FirstOrDefaultAsync(u => u.Id == 1);
var exists = await db.Users.AnyAsync(u => u.Name == "Tom");
var count = await db.Users.CountAsync();

var page = await db.Users.FromSqlRaw("SELECT * FROM users LIMIT 10").ToListAsync();
CODE
, 'note' => 'EF 查询必须 Async 后缀方法，别同步阻塞线程池'],
            ['title' => '关联与加载', 'code' => <<<'CODE'
var orders = await db.Users
    .Include(u => u.Orders)                       // 预加载
    .ThenInclude(o => o.Items)
    .ToListAsync();

db.Entry(user).Collection(u => u.Orders).Load();  // 显式加载

// 投影自动联表
var names = await db.Users
    .Where(u => u.Orders.Any())
    .Select(u => new { u.Name, OrderCount = u.Orders.Count })
    .ToListAsync();
CODE
, 'note' => 'Select 投影不需要 Include，性能更优'],
            ['title' => '迁移命令', 'code' => <<<'CODE'
dotnet ef migrations add AddUserTable
dotnet ef migrations remove
dotnet ef database update
dotnet ef migrations script -o out.sql
dotnet ef dbcontext scaffold "conn-str" Pomelo.EntityFrameworkCore.MySql -o Models
CODE
, 'note' => 'scaffold 从现有库反向生成实体（数据库优先）'],
        ]],

        ['name' => '日志与部署', 'entries' => [
            ['title' => 'ILogger 使用', 'code' => <<<'CODE'
public class Svc
{
    private readonly ILogger<Svc> _log;
    public Svc(ILogger<Svc> log) => _log = log;

    _log.LogInformation("用户 {UserId} 下单 {Count} 件", id, n);
    _log.LogWarning("缓存未命中 {Key}", key);
    _log.LogError(ex, "处理 {Path} 失败", path);
}
CODE
, 'note' => '结构化占位符 {Key}，Serilog 可直接入索引检索'],
            ['title' => '发布与部署', 'code' => <<<'CODE'
dotnet publish -c Release -r linux-x64 --self-contained false -o ./out

# 环境变量
ASPNETCORE_ENVIRONMENT=Production
ASPNETCORE_URLS=http://+:5000

# systemd 示例
[Service]
WorkingDirectory=/app
ExecStart=/usr/bin/dotnet /app/MyApp.dll
Restart=always
CODE
, 'note' => 'self-contained true 可免装运行时，体积大；Docker 用官方 mcr 镜像'],
            ['title' => '健康检查与监控', 'code' => <<<'CODE'
builder.Services.AddHealthChecks()
    .AddDbContextCheck<AppDb>()
    .AddCheck("disk", () => HealthCheckResult.Healthy());

app.MapHealthChecks("/health");
CODE
, 'note' => '负载均衡器探活用 /health；可扩展自定义检查项'],
        ]],

    ],
];
