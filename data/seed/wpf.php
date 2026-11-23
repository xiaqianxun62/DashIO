<?php
/* WPF 速查种子 */
return [
    'slug' => 'wpf',
    'name' => 'WPF',
    'category' => '.NET 生态',
    'icon' => '🪟',
    'color' => '#0078D4',
    'description' => 'XAML、布局、数据绑定、MVVM 与样式模板速查',
    'sort' => 42,
    'categories' => [

        ['name' => '应用结构', 'entries' => [
            ['title' => 'App.xaml 与入口', 'code' => <<<'CODE'
<!-- App.xaml -->
<Application x:Class="MyApp.App"
             xmlns="http://schemas.microsoft.com/winfx/2006/xaml/presentation"
             xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml"
             StartupUri="MainWindow.xaml">
</Application>
CODE
, 'note' => 'StartupUri 指定首窗体；代码启动改为重写 OnStartup'],
            ['title' => 'Window 窗体', 'code' => <<<'CODE'
<Window x:Class="MyApp.MainWindow"
        xmlns="http://schemas.microsoft.com/winfx/2006/xaml/presentation"
        xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml"
        Title="主窗口" Height="480" Width="720"
        WindowStartupLocation="CenterScreen"
        WindowStyle="ToolWindow">
    <Grid></Grid>
</Window>
CODE
, 'note' => 'Window 单根元素约束，内容放 Grid 里再展开'],
            ['title' => 'x: 前缀命名', 'code' => <<<'CODE'
xmlns:local="clr-namespace:MyApp.ViewModels"
xmlns:sys="clr-namespace:System;assembly=mscorlib"

<x:String x:Key="AppName">速查</x:String>
<local:MainViewModel x:Key="Vm" />
<StaticResource x:Key="PrimaryBrush" />
CODE
, 'note' => '自定义类必须先 xmlns 引入命名空间'],
        ]],

        ['name' => 'XAML 基础', 'entries' => [
            ['title' => '属性写法', 'code' => <<<'CODE'
<!-- 特性语法（最常用） -->
<Button Content="确定" Width="80" />

<!-- 属性元素语法（复杂值） -->
<Button>
    <Button.Content>
        <StackPanel Orientation="Horizontal">
            <Image Source="ok.png" />
        </StackPanel>
    </Button.Content>
</Button>

<!-- 标记扩展 -->
<TextBlock Text="{Binding Name}" />
<TextBlock Text="{StaticResource AppName}" />
CODE
, 'note' => '{} 是空标记扩展，显示字面 { } 时使用'],
            ['title' => 'Grid 布局（核心）', 'code' => <<<'CODE'
<Grid>
    <Grid.RowDefinitions>
        <RowDefinition Height="Auto" />   <!-- 按内容 -->
        <RowDefinition Height="*" />      <!-- 占剩余 -->
        <RowDefinition Height="2*" />     <!-- 2 倍 -->
        <RowDefinition Height="40" />    <!-- 固定 -->
    </Grid.RowDefinitions>
    <Grid.ColumnDefinitions>
        <ColumnDefinition Width="200" />
        <ColumnDefinition Width="Auto" SharedSizeGroup="c1" />
    </Grid.ColumnDefinitions>

    <Button Grid.Row="1" Grid.Column="0"
            Grid.ColumnSpan="2" Grid.RowSpan="1" />
</Grid>
CODE
, 'note' => 'Grid 共享尺寸用 Grid.IsSharedSizeScope+SharedSizeGroup'],
            ['title' => '其他布局面板', 'code' => <<<'CODE'
<StackPanel Orientation="Vertical" />   <!-- 纵向依次排 -->

<DockPanel LastChildFill="True">
    <Menu DockPanel.Dock="Top" />
    <StatusBar DockPanel.Dock="Bottom" />
    <Grid />       <!-- 填充剩余 -->
</DockPanel>

<WrapPanel Orientation="Horizontal" />  <!-- 换行流式 -->

<Canvas>                              <!-- 绝对定位 -->
    <Button Canvas.Left="10" Canvas.Top="20" />
</Canvas>

<UniformGrid Rows="2" />              <!-- 均分格子 -->
CODE
, 'note' => 'DockPanel 默认最后一个填充；Canvas 不自适应慎用'],
            ['title' => '对齐与边距', 'code' => <<<'CODE'
<Button Margin="10,5,10,5"   <!-- 左,上,右,下 -->
        HorizontalAlignment="Stretch"
        VerticalAlignment="Center"
        Padding="12,6" />
CODE
, 'note' => 'Margin 控件外间距；Padding 控件内填充'],
        ]],

        ['name' => '常用控件', 'entries' => [
            ['title' => '文本与输入', 'code' => <<<'CODE'
<Label Content="用户名" Target="{Binding ElementName=tb}" />
<TextBox x:Name="tb" Text="{Binding Name, UpdateSourceTrigger=PropertyChanged}" />
<PasswordBox PasswordChanged="OnPwd" />
<RichTextBox />
<TextBlock Text="只读文本" TextWrapping="Wrap"
           TextTrimming="CharacterEllipsis" />
CODE
, 'note' => 'PasswordBox 的密码属性不是依赖属性，需事件桥接绑定'],
            ['title' => '选择与列表', 'code' => <<<'CODE'
<ComboBox ItemsSource="{Binding Users}"
          SelectedItem="{Binding Current}"
          DisplayMemberPath="Name"
          IsEditable="True" />

<ListBox ItemsSource="{Binding Items}" SelectedIndex="0">
    <ListBox.ItemTemplate>
        <DataTemplate>
            <TextBlock Text="{Binding Name}" />
        </DataTemplate>
    </ListBox.ItemTemplate>
</ListBox>
CODE
, 'note' => '列表控件必配 DataTemplate 才能显示对象'],
            ['title' => 'DataGrid 表格', 'code' => <<<'CODE'
<DataGrid ItemsSource="{Binding Rows}"
          AutoGenerateColumns="False"
          CanUserAddRows="False"
          IsReadOnly="True"
          SelectedItem="{Binding Cur}">
    <DataGrid.Columns>
        <DataGridTextColumn Header="名称" Binding="{Binding Name}" Width="*" />
        <DataGridTextColumn Header="年龄" Binding="{Binding Age}" Width="80" />
        <DataGridTemplateColumn Header="操作">
            <DataGridTemplateColumn.CellTemplate>
                <DataTemplate>
                    <Button Content="编辑" Command="{Binding EditCmd}" />
                </DataTemplate>
            </DataGridTemplateColumn.CellTemplate>
        </DataGridTemplateColumn>
    </DataGrid.Columns>
</DataGrid>
CODE
, 'note' => 'CanUserAddRows=False 屏蔽底部空行'],
            ['title' => '其他常用控件', 'code' => <<<'CODE'
<Button Content="保存" Command="{Binding SaveCmd}" IsDefault="True" />
<ToggleButton IsChecked="{Binding IsVip}" />
<CheckBox Content="记住我" IsChecked="{Binding Remember, Mode=TwoWay}" />
<RadioButton Content="选项一" GroupName="g1" />
<Slider Minimum="0" Maximum="100" Value="{Binding Volume}" />
<ProgressBar Value="70" Maximum="100" />
<DatePicker SelectedDate="{Binding Birth}" />
<Image Source="/imgs/a.png" Stretch="Uniform" />
<Separator Margin="0,8" />
<GroupBox Header="高级">
<TabControl>
    <TabItem Header="常规"><Grid /></TabItem>
</TabControl>
<Expander Header="更多设置" IsExpanded="False">
CODE
, 'note' => 'IsDefault=True 回车触发；IsCancel 属性 Esc 触发'],
        ]],

        ['name' => '数据绑定', 'entries' => [
            ['title' => '绑定模式', 'code' => <<<'CODE'
{Binding Name}                        <!-- 默认（可写控件为 TwoWay） -->
{Binding Name, Mode=OneWay}            <!-- 源→界面 -->
{Binding Name, Mode=TwoWay}            <!-- 双向 -->
{Binding Name, Mode=OneTime}           <!-- 仅初始一次 -->
{Binding Name, Mode=OneWayToSource}   <!-- 界面→源 -->
CODE
, 'note' => 'TextBox 默认失焦才回写，加 UpdateSourceTrigger=PropertyChanged 实时'],
            ['title' => '绑定转换器', 'code' => <<<'CODE'
public class BoolToVis : IValueConverter
{
    public object Convert(object v, Type t, object p, CultureInfo c)
        => (bool)v ? Visibility.Visible : Visibility.Collapsed;
    public object ConvertBack(object v, Type t, object p, CultureInfo c)
        => throw new NotSupportedException();
}

<!-- XAML -->
<Window.Resources>
    <local:BoolToVis x:Key="B2V" />
</Window.Resources>
<TextBlock Text="{Binding Msg}" Visibility="{Binding HasMsg, Converter={StaticResource B2V}}" />
CODE
, 'note' => '轻量场景可用内置 StringFormat/TargetNullValue 代替转换器'],
            ['title' => 'INotifyPropertyChanged', 'code' => <<<'CODE'
public class MainViewModel : INotifyPropertyChanged
{
    public event PropertyChangedEventHandler? PropertyChanged;

    private string? _name;
    public string? Name
    {
        get => _name;
        set { _name = value; OnPropertyChanged(nameof(Name)); }
    }

    protected void OnPropertyChanged(string prop)
        => PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(prop));
}
CODE
, 'note' => 'set 里字段比较后再通知，避免无谓刷新'],
            ['title' => 'ObservableCollection', 'code' => <<<'CODE'
public ObservableCollection<User> Users { get; } = new();

Users.Add(new User("Tom"));       // 界面自动刷新
Users.RemoveAt(0);
Users[0].Name = "改";             // 项内属性变化需项自身 INPC

<!-- 列表空刷新 -->
ICollectionView view = CollectionViewSource.GetDefaultView(Users);
view.Refresh();
CODE
, 'note' => 'List 换成 ObservableCollection 才有增删通知'],
            ['title' => '集合视图过滤排序', 'code' => <<<'CODE'
var view = CollectionViewSource.GetDefaultView(Users);
view.Filter = o => (o as User)!.Name.Contains(kw);
view.SortDescriptions.Add(
    new SortDescription(nameof(User.Age), ListSortDirection.Descending));
view.GroupDescriptions.Add(new PropertyGroupDescription("Dept"));
CODE
, 'note' => '过滤回调在 UI 线程跑，重逻辑要节流'],
        ]],

        ['name' => 'MVVM 与命令', 'entries' => [
            ['title' => 'RelayCommand 实现', 'code' => <<<'CODE'
public class RelayCommand : ICommand
{
    private readonly Action<object?> _exec;
    private readonly Func<object?, bool>? _can;

    public RelayCommand(Action<object?> exec, Func<object?, bool>? can = null)
    { _exec = exec; _can = can; }

    public bool CanExecute(object? p) => _can?.Invoke(p) ?? true;
    public void Execute(object? p) => _exec(p);
    public event EventHandler? CanExecuteChanged
    {
        add => CommandManager.RequerySuggested += value;
        remove => CommandManager.RequerySuggested -= value;
    }
}
CODE
, 'note' => 'RequerySuggested 让 CanExecute 随焦点变化自动重查'],
            ['title' => 'ViewModel 组装', 'code' => <<<'CODE'
public class MainViewModel
{
    public ObservableCollection<User> Users { get; } = new();
    public string? Keyword { get; set; }

    public ICommand SearchCmd { get; }
    public ICommand? Selected { get; private set; }

    public MainViewModel()
    {
        SearchCmd = new RelayCommand(_ => Search(), _ => true);
    }

    private void Search() { /* 过滤逻辑 */ }
}

<!-- View -->
<Window.DataContext>
    <local:MainViewModel />
</Window.DataContext>
<Button Command="{Binding SearchCmd}"
        CommandParameter="{Binding Keyword}" Content="搜索" />
CODE
, 'note' => 'CommandParameter 可把输入传给命令'],
            ['title' => '异步命令（社区库）', 'code' => <<<'CODE'
// CommunityToolkit.Mvvm
public partial class MainViewModel : ObservableObject
{
    [ObservableProperty]
    private string? _name;                // 自动生成 Name 属性

    [RelayCommand]
    private async Task LoadAsync(CancellationToken ct)
        => await _svc.LoadAsync(ct);
}

<!-- XAML: {Binding Name} / {Binding LoadCommand} -->
CODE
, 'note' => 'CommunityToolkit.Mvvm 免手写样板，现代 WPF 首选'],
            ['title' => '窗口与对话框', 'code' => <<<'CODE'
var dlg = new EditWindow { Owner = this, DataContext = vm };
if (dlg.ShowDialog() == true)      // 模态，返回 bool
{ }

new DetailWindow().Show();         // 非模态

this.DialogResult = true;          // 关闭并返回结果

MessageBox.Show("确认删除？", "提示",
    MessageBoxButton.YesNo, MessageBoxImage.Question);
CODE
, 'note' => 'ShowDialog 模态阻塞；Owner 保证最小化联动'],
        ]],

        ['name' => '样式与模板', 'entries' => [
            ['title' => 'Style 基础', 'code' => <<<'CODE'
<Window.Resources>
    <Style TargetType="Button">
        <Setter Property="Background" Value="#4f6ef7" />
        <Setter Property="Foreground" Value="White" />
        <Setter Property="Padding" Value="16,8" />
        <Style.Triggers>
            <Trigger Property="IsMouseOver" Value="True">
                <Setter Property="Opacity" Value="0.85" />
            </Trigger>
            <Trigger Property="IsEnabled" Value="False">
                <Setter Property="Opacity" Value="0.4" />
            </Trigger>
        </Style.Triggers>
    </Style>
</Window.Resources>
CODE
, 'note' => '无 x:Key 的样式自动应用于同类型全部控件'],
            ['title' => 'ControlTemplate', 'code' => <<<'CODE'
<Style TargetType="Button">
    <Setter Property="Template">
        <Setter.Value>
            <ControlTemplate TargetType="Button">
                <Border x:Name="bd" CornerRadius="8"
                        Background="{TemplateBinding Background}">
                    <ContentPresenter Margin="{TemplateBinding Padding}"
                                      HorizontalAlignment="Center" />
                </Border>
                <ControlTemplate.Triggers>
                    <Trigger Property="IsPressed" Value="True">
                        <Setter TargetName="bd" Property="Opacity" Value="0.7" />
                    </Trigger>
                </ControlTemplate.Triggers>
            </ControlTemplate>
        </Setter.Value>
    </Setter>
</Style>
CODE
, 'note' => 'TemplateBinding 连接模板与控件属性'],
            ['title' => 'DataTemplate 与触发器类型', 'code' => <<<'CODE'
<DataTemplate DataType="{x:Type local:User}">
    <TextBlock Text="{Binding Name}" />
</DataTemplate>

<!-- Trigger 家族 -->
<Trigger Property="IsMouseOver" Value="True" />
<DataTrigger Binding="{Binding Level}" Value="VIP">
    <Setter Property="Foreground" Value="Gold" />
</DataTrigger>
<EventTrigger RoutedEvent="MouseEnter">
    <BeginStoryboard>
        <Storyboard>
            <DoubleAnimation Storyboard.TargetProperty="Opacity"
                              To="1" Duration="0:0:0.2" />
        </Storyboard>
    </BeginStoryboard>
</EventTrigger>
CODE
, 'note' => 'DataTrigger 按数据值变化改变样式'],
            ['title' => '资源引用', 'code' => <<<'CODE'
<Window.Resources>
    <SolidColorBrush x:Key="Pri" Color="#4f6ef7" />
</Window.Resources>

<Button Background="{StaticResource Pri}" />   <!-- 解析一次 -->
<Button Background="{DynamicResource Pri}" />  <!-- 运行时可变 -->

<!-- 合并全局资源字典 -->
<Application.Resources>
    <ResourceDictionary>
        <ResourceDictionary.MergedDictionaries>
            <ResourceDictionary Source="Themes/Dark.xaml" />
        </ResourceDictionary.MergedDictionaries>
    </ResourceDictionary>
</Application.Resources>
CODE
, 'note' => '运行时切主题：替换 MergedDictionaries 的 Source'],
            ['title' => '动画', 'code' => <<<'CODE'
<Storyboard x:Key="fadeIn">
    <DoubleAnimation Storyboard.TargetName="card"
                    Storyboard.TargetProperty="Opacity"
                    From="0" To="1" Duration="0:0:0.3"
                    EasingFunction="{StaticResource easeOut}" />
    <ThicknessAnimation Storyboard.TargetProperty="Margin"
                        From="0,20,0,0" To="0" Duration="0:0:0.3" />
</Storyboard>
CODE
, 'note' => 'Storyboard 是时间线容器，可并行多个动画'],
        ]],

        ['name' => '线程与实用技巧', 'entries' => [
            ['title' => 'Dispatcher 跨线程更新 UI', 'code' => <<<'CODE'
// 后台线程更新 UI 必须调度
await Task.Run(() => Heavy());

Dispatcher.Invoke(() => Result.Text = "完成");      // 同步
await Dispatcher.InvokeAsync(() => Bind());        // 异步推荐

Result.Dispatcher.BeginInvoke(new Action(() => { }));
CODE
, 'note' => 'await 后默认回 UI 线程；Task.Run 内碰控件需调度'],
            ['title' => '事件绑定与命令选择', 'code' => <<<'CODE'
<!-- 简单交互用事件 -->
<Button Click="OnSave" />

<!-- MVVM 用命令 -->
<Button Command="{Binding SaveCmd}" />

<!-- 传参 -->
<TextBox x:Name="tb" />
<Button Command="{Binding GoCmd}"
        CommandParameter="{Binding Text, ElementName=tb}" />
CODE
, 'note' => '小型工具事件更快；正式项目走命令可测试'],
            ['title' => '异常与调试', 'code' => <<<'CODE'
// 全局异常兜底
DispatcherUnhandledException += (s, e) =>
{
    MessageBox.Show(e.Exception.Message);
    e.Handled = true;
};

AppDomain.CurrentDomain.UnhandledException += (s, e) => Log(e.ExceptionObject);
TaskScheduler.UnobservedTaskException += (s, e) => e.SetObserved();
CODE
, 'note' => '三处兜底防闪退：UI/后台线程/任务未观察异常'],
            ['title' => '绑定验证', 'code' => <<<'CODE'
<TextBox>
    <Binding Path="Age" UpdateSourceTrigger="PropertyChanged">
        <Binding.ValidationRules>
            <local:RangeRule Min="1" Max="120" />
        </Binding.ValidationRules>
    </Binding>
</TextBox>

public class RangeRule : ValidationRule
{
    public override ValidationResult Validate(object v, CultureInfo c)
        => int.TryParse(v as string, out var n) && n >= Min && n <= Max
           ? ValidationResult.ValidResult
           : new ValidationResult(false, "范围 1-120");
}
CODE
, 'note' => '验证器返回 false 时控件红框提示'],
        ]],

    ],
];
