# ThinkPHP Redis消息队列扩展

一个简单的ThinkPHP Redis消息队列扩展，定时功能基于Workerman4.1开发

支持多队列

支持多进程

## 安装

~~~
composer require axguowen/think-redisqueue
~~~

## 配置

首先配置config目录下的redisqueue.php配置文件。
配置项说明：

~~~php
return [
    // 队列Worker实例名称
    'name' => 'think-redisqueue',
    // Redis连接配置
    'connection' => 'localhost',
    // 是否以守护进程启动
    'daemonize' => false,
    // 内容输出文件路径
    'stdout_file' => '',
    // pid文件路径
    'pid_file' => '',
    // 日志文件路径
    'log_file' => '',
    // 队列列表
    'queue_list' => [
        [
            // 队列ID
            'id' => 1,
            // 队列名称
            'name' => '执行闭包函数',
            // 进程数量
            'count' => 1,
            // 执行器，支持闭包、类的动态方法、类的静态方法，支持参数依赖注入
            'handler' => function(array $data, \think\App $app){
                echo 'ThinkPHP v' . $app->version() . PHP_EOL;
            }
        ],
        [
            // 队列ID
            'id' => 2,
            // 队列名称
            'name' => '执行类的静态方法',
            // 进程数量
            'count' => 1,
            // 执行器，支持闭包、类的动态方法、类的静态方法，支持参数依赖注入
            'handler' => \app\redisqueue\Handler::class . '::staticMethod',
        ],
        [
            // 队列ID
            'id' => 3,
            // 队列名称
            'name' => '执行类的动态方法',
            // 进程数量
            'count' => 1,
            // 这里实例化Handler类后执行publicMethod方法
            'handler' => [\app\redisqueue\Handler::class, 'publicMethod'],
        ],
        [
            // 队列ID
            'id' => 4,
            // 队列名称
            'name' => '不指定动态方法则默认执行类的handle方法',
            // 进程数量
            'count' => 1,
            // 此时\app\redisqueue\Handler类中必须要有handle方法
            'handler' => \app\redisqueue\Handler::class,
        ],
    ],
];
~~~

## 启动停止

队列的启动停止均在命令行控制台操作，所以首先需要在控制台进入tp目录

### 启动命令

~~~
php think redisqueue start
~~~

要使用守护进程模式启动可以将配置项deamonize设置为true
或者在启动命令后面追加 -d 参数，如下：
~~~
php think redisqueue start -d
~~~

### 停止
~~~
php think redisqueue stop
~~~

### 查看进程状态
~~~
php think redisqueue status
~~~

## 注意
Windows下不支持多进程设置，也不支持守护进程方式运行，正式生产环境请用Linux