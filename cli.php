<?php

// 定义应用目录
define('APP_PATH', __DIR__ . '/application/');

// 加载基础文件
require __DIR__ . '/thinkphp/base.php';

// 绑定当前入口文件到 index 模块
\think\Route::bind('api');

// 关闭路由
\think\App::route(false);

// 执行应用
\think\App::run()->send();


//余额宝收益
//php /www/wwwroot/www.1.com/cli.php share/shouyi

//余额宝确认用户份额
//php /www/wwwroot/www.1.com/cli.php share/confirm_share