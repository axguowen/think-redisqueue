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

namespace think;

// 命令行入口文件
// 加载基础文件
require dirname(__DIR__, 4) . '/autoload.php';

// 如果命令不是 redisqueue 则退出
if ($argc < 2 || $argv[1] != 'redisqueue') {
    exit('Not Support Command: ' . $argv[1] . PHP_EOL);
}
// 应用初始化
(new App())->console->run();
