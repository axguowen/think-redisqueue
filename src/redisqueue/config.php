<?php
// +----------------------------------------------------------------------
// | ThinkPHP Redis Queue [Simple Redis Queue Extend For ThinkPHP]
// +----------------------------------------------------------------------
// | ThinkPHP Redis消息队列扩展
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: axguowen <axguowen@qq.com>
// +----------------------------------------------------------------------

return [
    // 队列Worker实例名称
    'name' => 'think-redisqueue',
    // Redis连接配置
    'connection' => 'localhost',
    // 是否以守护进程启动
    'daemonize' => false,
    // Redis键名存储有效期
    'storage_expire' => 0,
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
            },
        ],
    ],
];
