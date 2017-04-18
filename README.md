# laravel-entwechat

微信 SDK for Laravel 5 / Lumen， 基于 [freyo/entwechat](https://github.com/freyo/entwechat)

本项目仅适用于一个固定企业号，支持一个或多个权限组

## 安装

1. 安装包文件

  ```shell
  composer require "freyo/laravel-entwechat:dev-master"
  ```
  
  > 使用前请禁用 laravel-debugbar
  
## 配置

### Laravel 应用

1. 注册 `ServiceProvider`:

  ```php
  Freyo\LaravelEntWechat\ServiceProvider::class,
  ```

2. 创建配置文件：

  ```shell
  php artisan vendor:publish --provider="Freyo\LaravelEntWechat\ServiceProvider"
  ```

3. 请修改应用根目录下的 `config/entwechat.php` 中对应的项即可；

4. （可选）添加外观到 `config/app.php` 中的 `aliases` 部分:

  ```php
  'LaravelEntWechat' => Freyo\LaravelEntWechat\Facade::class,
  ```

### Lumen 应用

```php
WECHAT_APPID
WECHAT_SECRET
WECHAT_TOKEN
WECHAT_AES_KEY

WECHAT_LOG_LEVEL
WECHAT_LOG_FILE

WECHAT_OAUTH_SCOPES
WECHAT_OAUTH_CALLBACK

WECHAT_PAYMENT_MERCHANT_ID
WECHAT_PAYMENT_KEY
WECHAT_PAYMENT_CERT_PATH
WECHAT_PAYMENT_KEY_PATH
WECHAT_PAYMENT_DEVICE_INFO
WECHAT_PAYMENT_SUB_APP_ID
WECHAT_PAYMENT_SUB_MERCHANT_ID
WECHAT_ENABLE_MOCK
```

3. 如果你习惯使用 `config/wechat.php` 来配置的话，将 `vendor/freyo/laravel-entwechat/src/config.php` 拷贝到`app/config`目录下，并将文件名改成`entwechat.php`。

## 使用

### Laravel <= 5.1

1. Laravel 5 起默认启用了 CSRF 中间件，因为微信的消息是 POST 过来，所以会触发 CSRF 检查导致无法正确响应消息，所以请去除默认的 CSRF 中间件，改成路由中间件。可以参考：[overtrue gist:Kernel.php](https://gist.github.com/overtrue/ff6cd3a4e869fbaf6c01#file-kernel-php-L31)
2. 5.1 里的 CSRF 已经带了可忽略部分url的功能，你可以参考：http://laravel.com/docs/master/routing#csrf-protection

### Laravel 5.2+

Laravel 5.2 以后的版本默认启用了 web 中间件，意味着 CSRF 会默认打开，有两种方案：

1. 在 CSRF 中间件里排除微信相关的路由
2. 关掉 CSRF 中间件（极不推荐）


下面以接收普通消息为例写一个例子：

> 假设您的域名为 `example.org` 那么请登录微信公众平台企业号 “应用中心” 选择一个应用启用回调模式，并修改 “URL（服务器配置）” 为： `http://example.org/wechat`。

路由：

```php
Route::any('/wechat', 'WechatController@serve');
```

> 注意：一定是 `Route::any`, 因为微信服务端认证的时候是 `GET`, 接收用户消息时是 `POST` ！

然后创建控制器 `WechatController`：

```php
<?php

namespace App\Http\Controllers;

use Log;

class WechatController extends Controller
{

    /**
     * 处理微信的请求消息
     *
     * @return string
     */
    public function serve()
    {
        Log::info('request arrived.'); # 注意：Log 为 Laravel 组件，所以它记的日志去 Laravel 日志看，而不是 EntWeChat 日志

        $wechat = app('wechat');
        $wechat->server->setMessageHandler(function($message){
            return "欢迎关注 overtrue！";
        });

        Log::info('return response.');

        return $wechat->server->serve();
    }
}
```

> 上面例子里的 Log 是 Laravel 组件，所以它的日志不会写到 EntWeChat 里的，建议把 wechat 的日志配置到 Laravel 同一个日志文件，便于调试。

### 我们有以下方式获取 SDK 的服务实例

##### 使用容器的自动注入

```php
<?php

namespace App\Http\Controllers;

use EntWeChat\Foundation\Application;

class WechatController extends Controller
{

    public function demo(Application $wechat)
    {
        // $wechat 则为容器中 EntWeChat\Foundation\Application 的实例
    }
}
```

##### 使用外观

在 `config/app.php` 中 `alias` 部分添加外观别名：

```php
'LaravelEntWechat' => Freyo\LaravelEntWechat\Facade::class,
```

然后就可以在任何地方使用外观方式调用 SDK 对应的服务了：

```php
  $wechatServer = LaravelEntWechat::server(); // 服务端
  $wechatUser = LaravelEntWechat::user(); // 用户服务
  // ... 其它同理
```


## OAuth 中间件

使用中间件的情况下 `app/config/wechat.php` 中的 `oauth.callback` 就随便填写吧(因为用不着了 :smile:)。

1. 在 `app/Http/Kernel.php` 中添加路由中间件：

```php
protected $routeMiddleware = [
    // ...
    'entwechat.oauth' => \Freyo\LaravelEntWechat\Middleware\OAuthAuthenticate::class,
];
```

2. 在路由中添加中间件：

以 5.2 为例：

```php
//...
Route::group(['middleware' => ['web', 'entwechat.oauth']], function () {
    Route::get('/user', function () {
        $user = session('wechat.oauth_user'); // 拿到授权用户资料

        dd($user);
    });
});
```
_如果你在用 5.1 上面没有 'web' 中间件_

当然，你也可以在中间件参数指定当前的 `account`:

```php
Route::group(['middleware' => ['web', 'entwechat.oauth:default']], function () {
  // ...
});
```

上面的路由定义了 `/user` 是需要微信授权的，那么在这条路由的**回调 或 控制器对应的方法里**， 你就可以从 `session('wechat.oauth_user')` 拿到已经授权的用户信息了。

## License

MIT
