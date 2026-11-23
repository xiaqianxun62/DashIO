<?php
/* WinForm 速查种子 */
return [
    'slug' => 'winform',
    'name' => 'WinForm',
    'category' => '.NET 生态',
    'icon' => '🖥️',
    'color' => '#009688',
    'description' => '窗体、控件、布局、数据绑定与 GDI+ 绘图速查',
    'sort' => 43,
    'categories' => [

        ['name' => '程序基础', 'entries' => [
            ['title' => '入口与窗体', 'code' => <<<'CODE'
// Program.cs
static void Main()
{
    Application.EnableVisualStyles();
    Application.SetCompatibleTextRenderingDefault(false);
    Application.Run(new MainForm());      // 消息循环
}

public partial class MainForm : Form
{
    public MainForm()
    {
        InitializeComponent();
    }
}
CODE
, 'note' => 'Application.Run 开启消息循环直到主窗体关闭'],
            ['title' => '窗体常用属性', 'code' => <<<'CODE'
this.Text = "管理系统";
this.ClientSize = new Size(1200, 720);
this.StartPosition = FormStartPosition.CenterScreen;
this.FormBorderStyle = FormBorderStyle.Sizable;
this.MaximizeBox = false;
this.Icon = new Icon("app.ico");
this.MinimumSize = new Size(800, 600);
this.ShowInTaskbar = true;
this.TopMost = false;
CODE
, 'note' => 'ClientSize 不含标题栏边框；Size 含'],
            ['title' => '窗体生命周期事件', 'code' => <<<'CODE'
Load += (s, e) => { };            // 显示前
Shown += (s, e) => { };          // 首次可见
FormClosing += (s, e) => e.Cancel = true;   // 可取消关闭
FormClosed += (s, e) => { };       // 已关闭
Activated / Deactivate;            // 激活/失活
Resize += (s, e) => { };
CODE
, 'note' => '拦截关闭时 e.Cancel=true 常用于未保存提醒'],
        ]],

        ['name' => '常用控件', 'entries' => [
            ['title' => '按钮与输入', 'code' => <<<'CODE'
var btn = new Button { Text = "保存", Dock = DockStyle.Top };
btn.Click += (s, e) => MessageBox.Show("clicked");

var txt = new TextBox { Multiline = false, MaxLength = 50 };
txt.TextChanged += (s, e) => { };

var rich = new RichTextBox { Dock = DockStyle.Fill };
rich.LoadFile("doc.rtf");

var lbl = new Label { Text = "用户名", AutoSize = true };
var link = new LinkLabel { Text = "忘记密码" };
link.LinkClicked += (s, e) => Process.Start(new ProcessStartInfo("https://x.com") { UseShellExecute = true });
CODE
, 'note' => '控件属性初始化器一行配齐，代码更整洁'],
            ['title' => '列表与选择', 'code' => <<<'CODE'
var combo = new ComboBox { DropDownStyle = ComboBoxStyle.DropDownList };
combo.Items.AddRange(new object[] { "北京", "上海" });
combo.SelectedIndex = 0;
combo.SelectedIndexChanged += (s, e) => { };

var lst = new ListBox { SelectionMode = SelectionMode.MultiExtended };
var chk = new CheckedListBox();
var radio = new RadioButton { Text = "男", Checked = true };
var check = new CheckBox { Text = "记住我", ThreeState = false };

var num = new NumericUpDown { Minimum = 0, Maximum = 100, Increment = 1 };
var date = new DateTimePicker { Format = DateTimePickerFormat.Short };
CODE
, 'note' => 'Items 可直接绑数据源并 DisplayMember'],
            ['title' => 'DataGridView（核心）', 'code' => <<<'CODE'
var grid = new DataGridView
{
    Dock = DockStyle.Fill,
    AutoGenerateColumns = false,
    AllowUserToAddRows = false,
    RowHeadersVisible = false,
    SelectionMode = DataGridViewSelectionMode.FullRowSelect,
    ReadOnly = true,
};
grid.Columns.Add("Id", "编号");          // 文本列
grid.Columns.Add(new DataGridViewTextBoxColumn
    { DataPropertyName = "Name", HeaderText = "名称", AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill });

grid.DataError += (s, e) => { };         // 免弹类型错误框
grid.CellDoubleClick += (s, e) => Edit(e.RowIndex);
CODE
, 'note' => 'DataError 事件吞掉转换异常弹窗'],
            ['title' => 'DataGridView 绑定与取值', 'code' => <<<'CODE'
var bs = new BindingSource { DataSource = users };
grid.DataSource = bs;
grid.Columns["Id"].Visible = false;

// 手动加行
grid.Rows.Add(1, "Tom", 20);

// 取值
var id = (int)grid.Rows[i].Cells["Id"].Value;
var cur = grid.CurrentRow?.DataBoundObject as User;

// 刷新
bs.ResetBindings(false);
CODE
, 'note' => 'BindingSource 是中间层，改 List 后 ResetBindings 刷新'],
            ['title' => '容器与菜单', 'code' => <<<'CODE'
var menu = new MenuStrip();
var file = new ToolStripMenuItem("文件(&F)");
file.DropDownItems.Add(new ToolStripMenuItem("退出", null, (s, e) => Close()));
menu.Items.Add(file);
this.MainMenuStrip = menu;
this.Controls.Add(menu);

var strip = new StatusStrip();
var status = new ToolStripStatusLabel("就绪");
strip.Items.Add(status);

var tab = new TabControl();
tab.TabPages.Add(new TabPage("常规") { Dock = DockStyle.Fill });

var panel = new SplitContainer { Dock = DockStyle.Fill, SplitterDistance = 200 };
CODE
, 'note' => "用 & 加字母为菜单提供 Alt 快捷键"],
        ]],

        ['name' => '布局', 'entries' => [
            ['title' => 'Dock 与 Anchor', 'code' => <<<'CODE'
sidePanel.Dock = DockStyle.Left;     // 贴边填充
btn.Dock = DockStyle.Bottom;

lbl.Anchor = AnchorStyles.Top | AnchorStyles.Left;    // 随窗口角缩放
txt.Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right;

btn.Anchor = AnchorStyles.None;       // 居中悬浮（配合 Location 手算）
CODE
, 'note' => 'Anchor 默认 Top|Left 即位置不动'],
            ['title' => '布局面板', 'code' => <<<'CODE'
// 流式
var flow = new FlowLayoutPanel { Dock = DockStyle.Fill, FlowDirection = FlowDirection.LeftToRight };

// 表格式（推荐）
var table = new TableLayoutPanel { Dock = DockStyle.Fill, ColumnCount = 2 };
table.ColumnStyles.Add(new ColumnStyle(SizeType.Absolute, 120));
table.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100));
table.Controls.Add(lbl, 0, 0);
table.Controls.Add(txt, 1, 0);
table.SetColumnSpan(btn, 2);

table.RowStyles.Add(new RowStyle(SizeType.AutoSize));
CODE
, 'note' => 'TableLayoutPanel 的 Percent 列实现类似 CSS 的自适应'],
            ['title' => '表单填充与边距', 'code' => <<<'CODE'
var pad = new Padding(12);
btn.Padding = new Padding(8, 4, 8, 4);      // 左上右下
grid.Padding = pad;

// 表单缩进布局惯例
var table = new TableLayoutPanel { Padding = new Padding(16) };
CODE
, 'note' => '大间距统一 8/12/16 三档，界面更规整'],
        ]],

        ['name' => '数据绑定', 'entries' => [
            ['title' => '简单绑定', 'code' => <<<'CODE'
txtName.DataBindings.Add(new Binding("Text", user, "Name"));
chkVip.DataBindings.Add("Checked", user, "IsVip");

var bs = new BindingSource { DataSource = user };
txtName.DataBindings.Add("Text", bs, "Name");
bs.EndEdit();          // 提交编辑
bs.CancelEdit();       // 撤销
CODE
, 'note' => '离开控件即回写；EndEdit 强制提交'],
            ['title' => 'INotifyPropertyChanged 支持', 'code' => <<<'CODE'
public class User : INotifyPropertyChanged
{
    private string? _name;
    public string? Name
    {
        get => _name;
        set { _name = value; PropertyChanged?.Invoke(this,
            new PropertyChangedEventArgs(nameof(Name))); }
    }
    public event PropertyChangedEventHandler? PropertyChanged;
}

// 实现后两个控件绑定同一对象自动联动
txt.DataBindings.Add("Text", user, "Name");
lbl.DataBindings.Add("Text", user, "Name");
CODE
, 'note' => '不实现 INPC 时 UI 不会感知对象属性变化'],
            ['title' => '列表绑定', 'code' => <<<'CODE'
public class UserList : BindingList<User>   // 增删自动刷新
{ }

var users = new BindingList<User> { new User("Tom") };
var bs = new BindingSource { DataSource = users };
grid.DataSource = bs;
combo.DataSource = bs;
combo.DisplayMember = "Name";

bs.Add(new User("Jerry"));
bs.RemoveCurrent();
bs.Position = 0;             // 选中索引
bs.MoveNext(); bs.MoveFirst();
CODE
, 'note' => 'BindingList = List + 增删通知；普通 List 绑定后不刷新'],
        ]],

        ['name' => '对话框与交互', 'entries' => [
            ['title' => 'MessageBox', 'code' => <<<'CODE'
MessageBox.Show("确认删除？", "提示",
    MessageBoxButtons.YesNoCancel,
    MessageBoxIcon.Question,
    MessageBoxDefaultButton.Button2);

var r = MessageBox.Show(text, caption, MessageBoxButtons.YesNo);
if (r == DialogResult.Yes) { Delete(); }
CODE
, 'note' => '返回 DialogResult.Yes/No/Cancel/OK'],
            ['title' => '文件与颜色字体对话框', 'code' => <<<'CODE'
using var open = new OpenFileDialog { Filter = "图片|*.png;*.jpg|全部|*.*" };
if (open.ShowDialog() == DialogResult.OK)
    pictureBox.Image = Image.FromFile(open.FileName);

var save = new SaveFileDialog { Filter = "文本|*.txt", FileName = "out.txt" };
var folder = new FolderBrowserDialog();     // .NET Core 3+ 可用
var color = new ColorDialog { Color = Color.Red };
var font = new FontDialog { ShowColor = true };
CODE
, 'note' => 'ShowDialog 模态返回 OK/Cancel，用完 Dispose 大对象'],
            ['title' => '自绘对话框与联动', 'code' => <<<'CODE'
using (var dlg = new EditForm { StartPosition = FormStartPosition.CenterParent })
{
    dlg.Owner = this;
    if (dlg.ShowDialog(this) == DialogResult.OK)
        ResultLabel.Text = dlg.InputValue;      // 公开属性回传
}

// 子窗体事件回传（配合公开事件）
public event Action<string>? OnSaved;
CODE
, 'note' => '两种传值方式：公开属性 或 公开事件'],
        ]],

        ['name' => 'GDI+ 绘图', 'entries' => [
            ['title' => 'Paint 基础', 'code' => <<<'CODE'
panel.Paint += (s, e) =>
{
    var g = e.Graphics;
    g.SmoothingMode = SmoothingMode.AntiAlias;

    using var pen = new Pen(Color.DodgerBlue, 2);
    g.DrawRectangle(pen, 10, 10, 120, 60);
    g.DrawLine(pen, 0, 0, 100, 100);
    g.DrawEllipse(pen, 10, 10, 80, 80);

    using var brush = new SolidBrush(Color.FromArgb(80, 79, 110, 247));
    g.FillRectangle(brush, 100, 100, 200, 100);
};

// 触发重绘
panel.Invalidate();
CODE
, 'note' => '只响应 Paint 重绘，不要长期持有 Graphics'],
            ['title' => '文字与格式', 'code' => <<<'CODE'
using var font = new Font("微软雅黑", 14, FontStyle.Bold);
g.DrawString("计费: 128.50", font, Brushes.Black, 10, 10);

var fmt = new StringFormat
{
    Alignment = StringAlignment.Center,
    LineAlignment = StringAlignment.Center,
};
g.DrawString("居中", font, brush, rect, fmt);
CODE
, 'note' => '中文界面统一微软雅黑，别依赖默认字体'],
            ['title' => '双缓冲防闪烁', 'code' => <<<'CODE'
// 面板级
panel.DoubleBuffered = true;   // 保护属性，需反射或派生

// 派生写法
class SmoothPanel : Panel
{
    public SmoothPanel() { DoubleBuffered = true; }
}

// 窗体级 API（.NET 6+）
this.SetStyle(ControlStyles.OptimizedDoubleBuffer, true);
CODE
, 'note' => 'DoubleBuffered 是 protected，反射设置见下一屏'],
            ['title' => '图像处理', 'code' => <<<'CODE'
var bmp = new Bitmap(200, 100);
using (var g = Graphics.FromImage(bmp))
{ g.Clear(Color.White); g.DrawLine(Pens.Black, 0, 0, 199, 99); }
bmp.Save("out.png", ImageFormat.Png);

// 缩放高质量
var thumb = new Bitmap(bmp, 100, 50);
using var g2 = Graphics.FromImage(thumb);
g2.InterpolationMode = InterpolationMode.HighQualityBicubic;
g2.DrawImage(bmp, 0, 0, 100, 50);

var px = bmp.GetPixel(10, 10);        // 单像素
bmp.MakeTransparent(Color.White);    // 透明化
CODE
, 'note' => 'Bitmap/Image 是 IDisposable，及时释放句柄'],
        ]],

        ['name' => '线程与后台任务', 'entries' => [
            ['title' => 'Invoke 安全更新 UI', 'code' => <<<'CODE'
void OnData(List<Item> items)
{
    if (InvokeRequired)
        BeginInvoke(new Action(() => BindGrid(items)));   // 丢回 UI 线程
    else
        BindGrid(items);
}

// 检查句柄避免已释放
if (IsHandleCreated) BeginInvoke(...);
if (IsDisposed) return;
CODE
, 'note' => '后台线程直接碰控件必抛 InvalidOperationException'],
            ['title' => 'async/await（现代写法）', 'code' => <<<'CODE'
private async void BtnLoad_Click(object? s, EventArgs e)
{
    btnLoad.Enabled = false;
    try
    {
        var users = await _svc.LoadUsersAsync();   // 自动回 UI 线程
        grid.DataSource = users;
    }
    finally { btnLoad.Enabled = true; }
}

private async Task LoadAllAsync()
    => await Task.WhenAll(LoadUsers(), LoadOrders());
CODE
, 'note' => 'async void 仅事件处理器使用；其余一律 async Task'],
            ['title' => 'BackgroundWorker（旧式）', 'code' => <<<'CODE'
var bg = new BackgroundWorker { WorkerReportsProgress = true };
bg.DoWork += (s, e) => { e.Result = Heavy(); };
bg.ProgressChanged += (s, e) => progressBar.Value = e.ProgressPercentage;
bg.RunWorkerCompleted += (s, e) => Result.Text = e.Result?.ToString();
bg.RunWorkerAsync();
bg.CancelAsync();     // 配 WorkerSupportsCancellation
CODE
, 'note' => '老项目常见；新代码用 Task + async 事件处理器'],
            ['title' => '定时器', 'code' => <<<'CODE'
var tm = new System.Windows.Forms.Timer { Interval = 1000 };   // UI 线程
tm.Tick += (s, e) => clockLabel.Text = DateTime.Now.ToString("HH:mm:ss");
tm.Start(); tm.Stop();

// 非界面用
var sys = new System.Threading.Timer(cb, null, 0, 1000);
var t = new System.Timers.Timer { Interval = 5000 };
t.Elapsed += (s, e) => { };  t.Start();
CODE
, 'note' => '三种 Timer 别混：WinForms.Timer 才在 UI 线程触发'],
        ]],

        ['name' => '打包与技巧', 'entries' => [
            ['title' => '发布单文件', 'code' => <<<'CODE'
dotnet publish -c Release -r win-x64
    /p:PublishSingleFile=true
    /p:SelfContained=true
    /p:IncludeNativeLibrariesForSelfExtract=true
    /p:EnableCompressionInSingleFile=true
CODE
, 'note' => '单文件含运行时免安装；加 trimming 体积更小但反射慎用'],
            ['title' => '全局热键（P/Invoke）', 'code' => <<<'CODE'
[DllImport("user32.dll")]
static extern bool RegisterHotKey(IntPtr h, int id, uint mod, uint vk);

protected override void WndProc(ref Message m)
{
    if (m.Msg == 0x0312)       // WM_HOTKEY
    {
        var key = (Keys)((int)m.LParam >> 16);
        HandleHotKey(key);
    }
    base.WndProc(ref m);
}
RegisterHotKey(Handle, 1, 0x0001 /*MOD_ALT*/, (uint)Keys.F9);
CODE
, 'note' => '记得在关闭时 UnregisterHotKey 释放'],
            ['title' => '设置 DoubleBuffered（反射）', 'code' => <<<'CODE'
typeof(Control).GetProperty("DoubleBuffered",
    BindingFlags.Instance | BindingFlags.NonPublic)
    ?.Invoke(grid, new object[] { true });
CODE
, 'note' => 'DataGridView 高频刷新必备，肉眼可见的卡顿消失'],
            ['title' => '配置与日志简法', 'code' => <<<'CODE'
// 简易配置（正式项目用 Microsoft.Extensions.Configuration）
var ini = File.ReadAllLines("app.ini");
var dict = ini.Select(l => l.Split('='))
             .Where(p => p.Length == 2)
             .ToDictionary(p => p[0].Trim(), p => p[1].Trim());

File.AppendAllText("run.log",
    $"{DateTime.Now:HH:mm:ss} {msg}{Environment.NewLine}");
CODE
, 'note' => 'Environment.NewLine 跨平台换行符，别硬编码 \r\n'],
        ]],

    ],
];
