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

namespace think\redisqueue;

class Service extends \think\Service
{
    /**
     * 注册服务
     * @access public
     * @return void
     */
    public function register()
    {
        // 设置命令
        $this->commands([
            'redisqueue' => Command::class,
        ]);
    }
}
