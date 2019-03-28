![Laravel开发API助手](https://s2.ax1x.com/2019/03/27/Aajlgx.md.png)


![Software license](https://img.shields.io/badge/license-MIT-green.svg)
![Build](https://img.shields.io/badge/build-passing-0.svg)
![dependencies](https://img.shields.io/badge/dependencies-up%20to%20date-brightgreen.svg)
![stars](https://img.shields.io/badge/stars-%E2%98%85%E2%98%85%E2%98%85%E2%98%85%E2%98%85-brightgreen.svg)
![php](https://img.shields.io/badge/php-%3E%3D%207.1-blueviolet.svg)
![mysql](https://img.shields.io/badge/mysql-%3E%3D%205.5-informational.svg)
![laravel](https://img.shields.io/badge/laravel-%3E%3D%205.5-red.svg)
[![Latest Version](http://img.shields.io/packagist/v/cyd622/laravel-api.svg)](https://packagist.org/packages/cyd622/laravel-api)
[![Monthly Downloads](https://img.shields.io/packagist/dm/cyd622/laravel-api.svg)](https://packagist.org/packages/cyd622/laravel-api)



# Laravel开发API助手
*[将Laravel框架进行一些配置处理，让其在开发API时更得心应手]*

------

## 背景

随着前后端完全分离，`PHP`也基本告别了`view`模板嵌套开发，转而专门写资源接口。`Laravel`是PHP框架中`最优雅的框架`，国内也越来越多人告别`ThinkPHP`选择了`Laravel`。Laravel框架本身对`API`有支持，但是感觉再工作中还是需要再做一些处理。`Lumen`用起来不顺手，有些包不能很好地支持。所以，将Laravel框架进行一些配置处理，让其在开发API时更得心应手。

---

## 环境和程序要求

| 程序 | 版本 |
| -------- | -------- |
| PHP| `>= 7.1` |
| MySQL| `>= 5.5` |
| laravel/laravel| `>= 5.5` |
| tymon/jwt-auth| `1.0.0-rc.4.*` |


----


## 功能

> - [x] 统一Response响应处理
> - [x] Laravel Api-Resource资源 分页返回统一响应
> - [x] jwt-auth用户认证与无感知自动刷新
> - [x] jwt-auth多角色认证不串号
> - [x] 单一设备登陆

-----

## 安装
* 通过composer，这是推荐的方式，可以使用composer.json 声明依赖，或者直接运行下面的命令。

```shell
 composer require cyd622/laravel-api

```
 
* 放入composer.json文件中

```json
"require": {
    "cyd622/laravel-api": "*"
}
```    
 然后运行
```shell
composer update
```

----

## 使用

添加服务提供商
```
'providers' => [
    ...
    Cyd622\LaravelApi\ApiServiceProvider::class,
]
```
2.发布配置文件
```shell
php artisan vendor:publish --provider="Cyd622\LaravelApi\ApiServiceProvider"
```
> 此命令会在 config 目录下生成一个 `laravel_api.php` 配置文件，你可以在此进行自定义配置。
> * `response` 是配置资源响应格式
> * `exception` 是配置需要拦截的异常

### 1.统一Response响应处理
> 所有请求都返回`json`格式,返回字段格式也是统一标准化
> 格式如下
```
{
  "message": "string",
  "code": xxx, 
  "data": [] // 数组或对象
}
```
> 默认`success`返回的`http`状态码是`200`，`error`返回的状态码是`400`

1. 新建Api控制器基类 或者 继承Api控制器基类
2. `use ApiResponse;`
3. 代码使用
```php
// 成功返回 第一个参数可接受item和resource
return $this->success($user,$meta);
// 只返回信息无内容
return $this->setHttpCode(201)->setStatusCode(1002001)->message('用户创建成功...');
// 错误返回
return $this->error('用户登录失败',401,10001);
```


4.返回
```
// 成功完整返回
{
    "message": "Success",
    "code": 200,
    "data": [
        {
            "id": 1,
            "name": "jack"
        },
        {
            "id": 2,
            "name": "tony"
        }
    ],
    "meta": {
        "page_info": {
            "current_page": 1,
            "last_page": 20,
            "per_page": 15,
            "total": 500
        }
    }
}

// 错误返回
{
  "message": "Error",
  "code": 104041,
  "data": []
}
```

### 2.Api-Resource资源 分页返回统一响应
1. 在Resource资源文件中引入
2. `use PaginatedCollection;`
示例代码
```php
namespace App\Http\Resources;

use App\Traits\PaginatedCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class Company extends JsonResource
{
    use PaginatedCollection;

}
```

3.使用示例
```php
 return \App\Http\Resources\Company::collection($company);
```
4.返回同上成功返回示例


> 关于分页返回的字段，你可以在配置文件中指定：`config('laravel_api.response.page_info')`
默认是`current_page`、`last_page`、`per_page`、`total` 4个

### 3.异常自定义处理
1.修改 `app/Exceptions` 目录下的 `Handler.php` 文件
```php
use LaravelApi\Response\ExceptionReport;

public function render($request, Exception $exception)
{
    // 将方法拦截到自己的ExceptionReport
    $reporter = ExceptionReport::make($exception);

    if ($reporter->shouldReturn()) {
        return $reporter->report();
    }

    return parent::render($request, $exception);
}
```
2.可自定义错误的异常设置
配置文件`laravel_api.php`，在`exception.do_report`加入需要拦截的异常，示例：
```
'exception' => [
    'do_report'=>[
        UnauthorizedHttpException::class => [
            'msg' => '未授权或Token签名失效', // message显示的消息
            'http_code' => 401 // http状态码，不填则获取异常类的状态码，如果获取不到则500
        ],
        AuthenticationException::class => [
            'msg' => '未授权或Token签名失效',
            'status_code' => 104013 // 响应体中code代码,可用于业务标识
        ]
    ]
]
```

### 4.jwt-auth
> jwt-auth的详细介绍分析可以看 [JWT超详细分析](https://learnku.com/articles/17883) 这篇文章，具体使用可以看 [JWT完整使用详解](https://learnku.com/articles/10885/full-use-of-jwt) 这篇文章。


1.打开 config 目录下的 app.php文件，添加服务提供者
```
'providers' => [
    ...
    Tymon\JWTAuth\Providers\LaravelServiceProvider::class,
]
```
2.发布配置文件
```shell
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
```
> 此命令会在 config 目录下生成一个 `jwt.php` 配置文件，你可以在此进行自定义配置。

3.生成密钥
```shell
php artisan jwt:secret
```
> 此命令会在你的 `.env` 文件中新增一行 `JWT_SECRET=secret`。以此来作为加密时使用的秘钥。

4.配置 Auth guard. 打开 `config` 目录下的 `auth.php`文件，修改api的驱动为`jwt`。这样，我们就能让api的用户认证变成使用jwt。
```
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

5.更改 User Model
如果需要使用`jwt-auth`作为用户认证，我们需要对我们的 User模型进行一点小小的改变，实现一个接口，变更后的`User`模型如下
```php
class User extends Authenticatable implements JWTSubject
{
    ...
    
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }
}
```

### 5.自动刷新用户认证 && 多看守器不串号 && 单一设备登陆
现在我想用户登录后，为了保证安全性，每个小时该用户的token都会自动刷新为全新的，用旧的token请求不会通过。我们知道，用户如果token不对，就会退到当前界面重新登录来获得新的token，我同时希望虽然刷新了token，但是能否不要重新登录，就算重新登录也是一周甚至一个月之后呢？给用户一种无感知的体验。

1.增加中间件别名
打开 app/Http 目录下的 Kernel.php 文件，添加如下一行
```
protected $routeMiddleware = [
    ......
    'api.refresh'=>\Cyd622\LaravelApi\Middleware\RefreshTokenMiddleware::class,
];
```
2.路由器修改
```
Route::middleware('api.refresh')->group(function () {
    // jwt认证路由以及无感刷新路由
    ...
});
```
3.登录控制器引入`LoginActionTrait`
```
class LoginController extends Controller
{
    use LoginActionTrait;
    ...
}
```
4.原理

* 自动刷新用户认证
> * 捕获到了 token 过期所抛出的 TokenExpiredException异常
> * 刷新用户的 token `$token = $this->auth->refresh();`
> * 使用一次性登录以保证此次请求的成功
> * `Auth::guard('api')->onceUsingId($this->auth->manager()->getPayloadFactory()->buildClaimsCollection()->toPlainArray()['sub']);`
> * 在响应头中返回新的 token `$this->setAuthenticationHeader($next($request), $token);`

* 多看守器不串号
> * 我们通过Auth::claims() 添加自定义参数，在中间件时候进行解析，拿到我们的载荷，就可以进行判断是否是属于当前`guard`的token了。

* 单一设备登陆
> * 我们将`token`都存到**缓存中**。在登陆接口，获取到`last_token`里的值，将其加入黑名单。
> * 这样，只要我们无论在哪里登陆，之前的`token`一定会被拉黑失效，必须重新登陆，我们的目的也就达到了。
