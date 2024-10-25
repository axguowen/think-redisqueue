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

namespace think\facade;

use think\Facade;

/**
 * @see \think\RedisQueue
 * @mixin \think\RedisQueue
 */
class RedisQueue extends Facade
{
    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'think\RedisQueue';
    }
}
