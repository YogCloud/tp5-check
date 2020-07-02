# TpCheck ThinkPHP 扩展验证类

    为了方便写代码我们将封装统一验证类以快速开发
    
## 快速开始

```js
composer require h6play/tp5-check
```
    
## 过滤器类型

* `普通验证器` 和普通的 ThinkPHP 验证类没多大区别
* `复杂验证器` 这个是相当于验证器内嵌套验证器了，类似于 `验证多维数组` 的结构
* `过滤验证器` 和普通的 ThinkPHP 验证类没多大区别，只是有很多验证的方法

#### 普通验证器

```php
namespace app\validate;
use app\server\Validate;
class UserValidate extends Validate
{
    protected $rule = [
        'name' =>  'min:0',
        'age'  => 'min:0',
    ];
}

// 目前增加的验证
// [] 括号括起来的表示可不传
// 所有规则都是按定义顺序执行的
// 1. validate:[scene,type],class  # 验证类验证 scene=场景, type=(map|array)数据类型, class=验证类名
// 2. empty                        # 空验证没什么用为了没有验证规则时候不报错
// 3. array:[type]                 # 验证数组类型 type=数据类型（int|float|number|bool）
// 4. isset                        # 验证参数必须存在
// 5. mobile                       # 手机号码验证具体号码段看具体实现类
```

#### 复杂验证器

```php
namespace app\validate;
use app\server\Validate;
class UserValidate extends Validate
{
    // validate 有三种调用方式
    // 第一种 validate:<验证类名>
    // 第二种 validate:<类型:map|array>,<验证类名>
    // 第三种 validate:<场景>,<类型:map|array>,<验证类名>
    protected $rule = [
        'name' =>  'min:0',
        'age'  => 'min:0',
        'book' =>  'isset|validate:logo,map,' . UserBookValidate::class,
    ];
}
```

```php
namespace app\validate;
use app\server\Validate;
class UserBookValidate extends Validate
{
    protected $rule = [
        'title' => 'min:0',
        'logo' => 'min:0',
    ];
    protected $scene = [
        "logo" => ["logo"],
    ];
}

```

#### 验证器过滤

```php
namespace app\validate;
use app\server\Validate;
class UserBookValidate extends Validate
{
    protected $rule = [
        'title' => 'min:0|f_trim', // f_trim 实现字符串去除空格处理，更多请查看实现类
        'logo' => 'min:0',
    ];
    protected $scene = [
        "logo" => ["logo"],
    ];
}

// 目前增加的验证
// [] 括号括起来的表示可不传
// 所有规则都是按定义顺序执行的
// 1. f_trim              # 字符串去除空格
// 2. f_int               # 转换成整数
// 3. f_float             # 转换成浮点数
// 4. f_bool              # 转换成布尔值
// 5. f_array:[type]      # 转换成数组 type=类型（int|float|bool）
// 6. f_explode:char      # 字符串分割成数组 char=分割字符
// 7. f_implode           # 数组转换成字符串 char=分割字符
// 8. f_json_e            # 数组序列化
// 9. f_json_d            # 字符串序列化成数组
// 9. f_json_o            # 字符串序列化成对象
```

## 验证器获取参数


* 如果验证失败会抛异常
* 如果不传递参数则获取 `Request::param()`  参数

```php
// V() VM() VO() VMA() 方法的参数说明
// p_1 场景值（具体看教程说的场景）
// p_2 是否数组 true|false （一般来说一般会存在两种结构 array|map 也就是一个商品指向多个属性的说法）
// p_3 参数 null|array （如果不传递一般使用 Request::param() 否则 ...）
// 关于数组和非数组的说明
// Map数组
$arr = [
    "key" => "value",
  // TODO ...
];
// 多维数组
$arr = [
    "key" => [
        ["k1" => "v1", "k2" => "v2"],
    // TODO ...
  ]
];
// 模拟参数
$params = [
    "name" => "张全蛋",
    "book" => [
        "title" => "人类简史 <我是空格将要被格式化>   ",
    //    "logo" => "我不想传递过去.png" 
    ]
];
// 1. 验证
UserValidate::V(); // 不传递参数会自动获取 Request::param() 参数
UserValidate::V("", false, $params);
// 2. 验证并返回参数 有四种
// 2.1 返回有传递的参数
$vars = UserValidate::VO();
$vars = UserValidate::VO("", false, $params);
// 2.2 返回规则定义的所有参数（不管你有没有传递）
$vars = UserValidate::VM();
$vars = UserValidate::VM("", false, $params);
// 2.3 返回规则定义的所有参数（不管你有没有传递）（主节点以数组的方式返回）
[$name, $book] = UserValidate::VMA();
[$name, $book] = UserValidate::VMA("", false, $params);
// 2.4 返回规则定义的所有参数（不管你有没有传递）（子验证器返回有传递的参数）（主节点以数组的方式返回）
[$name, $book] = UserValidate::VMOA();
[$name, $book] = UserValidate::VMOA("", false, $params);
```

## 验证器模型

* 很多时候我们为了验证某个参数，查询数据库或得到的记录等等。。。
* 但是，我们又在下面还有用到
* 但是，我们又想写成通用验证方法
* 但是，我们还想能全局访问
* 那么 。。。请打死产品经理吧！！！
* 产品祭天，法力无边！！！

```php
// 设置模型变量
Validate::M("key", "value");
// 获取模型变量
$var = Validate::M("key");
```

* 下面看演示

```php
class UserBookValidate extends Validate
{
    protected $rule = [
        // TODO ...
    ];
    protected $scene = [
        // TODO ...
    ];

    protected function checkBook($value, $rule, $data = [], $field = "") {
        $book = BookModel::where("id", $value)->find();
        if(!$book) {
            return "{$field}书籍并不存在"
        }
        // 设置
        Validate::M("book", $book);
        return true;
    }
}


// !!!! Main 方法 !!!

$book = Validate::M("book");
dump($book);
```
