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

use think\App;
use think\facade\RedisClient;
use think\redisclient\Builder;
use think\console\Output;
use think\console\Input;
use Workerman\Worker;
use Workerman\Lib\Timer;

class RedisQueue
{
    /**
     * 存储键名前缀
     * @var array
     */
    const STORAGE_KEY = 'think:redisqueue:id:<id>:list';

    /**
     * 配置参数
     * @var array
     */
	protected $options = [
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
        'queue_list' => [],
	];

    /**
     * Worker实例
     * @var Worker
     */
    protected $worker;

    /**
     * Output实例
     * @var Output
     */
    protected $output;

    /**
     * 容器实例
     * @var App
     */
    protected $app;

    /**
	 * 存储器实例
	 * @var Builder
	 */
	protected $storageBuilder;

    /**
	 * 队列ID列表
	 * @var string[]
	 */
	protected $queueIds = [];

    /**
	 * 队列数据
	 * @var array
	 */
	protected $queueList = [];

    /**
	 * worker回调方法
	 * @var array
	 */
	protected $events = ['onWorkerStart'];

    /**
     * 架构函数
     * @access public
     * @param App $app 容器实例
     * @return void
     */
    public function __construct(App $app)
    {
        // 记录容器实例
        $this->app = $app;
        // 合并配置
		$this->options = array_merge($this->options, $this->app->config->get('redisqueue', []));
        // 初始化
        $this->init();
    }

    /**
     * 启动回调
     * @access protected
	 * @return void
     */
    protected function init()
    {
        // 如果队列为空
        if(empty($this->options['queue_list'])){
            // 抛出异常
            throw new \Exception('未指定有效的队列');
        }
        // 遍历配置中的队列
        foreach ($this->options['queue_list'] as $queue) {
            // 如果没有队列ID
            if(!isset($queue['id']) || empty($queue['id'])){
                // 抛出异常
                throw new \Exception('未指定有效的队列ID');
            }
            // 如果已存在队列ID
            if(in_array($queue['id'], $this->queueIds)){
                // 抛出异常
                throw new \Exception('存在重复队列ID');
            }
            // 如果没有指定进程数量或指定的进程数量为空
            if(!isset($queue['count']) || empty($queue['count'])){
                // 跳过
                continue;
            }
            // 如果没有指定执行器或指定的执行器为空
            if(!isset($queue['handler']) || empty($queue['handler'])){
                // 跳过
                continue;
            }
            // 记录队列ID到队列ID列表
            $this->queueIds[] = $queue['id'];
            // 遍历进程数量
            for ($i = 0; $i < $queue['count']; $i++) {
                // 记录到队列列表
                $this->queueList[] = $queue;
            }
        }
    }

    /**
     * 启动回调
     * @access public
	 * @param Worker $worker
	 * @return void
     */
    public function onWorkerStart(Worker $worker)
    {
        // 如果队列列表为空
        if(empty($this->queueList)){
            // 输出错误并返回
            return $this->output->writeln('<error>当前队列为空</error>');
        }

        // 获取当前进程对应的队列
        $queue = $this->queueList[$worker->id];
        // 实例化存储器
        $this->storageBuilder = RedisClient::connect($this->options['connection'])->key(static::STORAGE_KEY, $queue);
        // 获取执行器
        $handler = $queue['handler'];
        // 如果是类名则指定方法
        if (is_string($handler) && false === strpos($handler, '::')) {
            $handler = [$handler, 'handle'];
        }
        // 加入到定时器并返回
        return Timer::add(1, [$this, 'consume'], [$handler]);
    }

    /**
     * 消费队列
     * @access public
	 * @param mixed $handler
	 * @return void
     */
    public function consume($handler)
    {
        // 获取弹出的队列数据
        $queueDataPop = $this->storageBuilder->brPop(5);
        // 如果弹出的队列数据为空
        if(empty($queueDataPop) || !is_array($queueDataPop) || count($queueDataPop) < 2){
            // 返回
            return $this->output->writeln('<error>没有可执行的数据</error>');
        }
        // 解析json
        $queueData = json_decode($queueDataPop[1], true);
        // 如果解析队列数据失败
        if(!is_array($queueData)){
            // 返回
            return $this->output->writeln('<error>队列数据解析失败</error>');
        }
        // 如果是闭包
        if ($handler instanceof \Closure) {
            // 执行闭包
            return $this->app->invokeFunction($handler, [$queueData]);
        }
        // 执行类的方法
        return $this->app->invokeMethod($handler, [$queueData]);
    }

    /**
     * 向队列发送消息
     * @access public
     * @param string $queueId 队列ID
	 * @param array $data
	 * @return int
     */
    public function send($queueId, array $data = [])
    {
        // 实例化存储器
        $storageBuilder = RedisClient::connect($this->options['connection'])->key(static::STORAGE_KEY, [
            'id' => $queueId,
        ]);
        // 将数据推送到队列
        $lPushResult = $storageBuilder->lPush(json_encode($data, JSON_UNESCAPED_UNICODE));
        // 如果设置了有效期
        if($this->options['storage_expire'] > 0){
            // 设置有效期
            $storageBuilder->expire($this->options['storage_expire']);
        }
        // 返回
        return $lPushResult;
    }

    /**
     * 启动
     * @access public
     * @param Input $input 输入
     * @param Output $output 输出
	 * @return void
     */
	public function start(Input $input, Output $output)
	{
        // 不是控制台模式
        if (!$this->app->runningInConsole()) {
            // 抛出异常
            throw new \Exception('仅支持在控制台模式下运行');
        }
        // 保存输出实例
        $this->output = $output;
        // 如果是守护进程模式
        if ($input->hasOption('daemon')) {
            // 修改配置为守护进程模式
            $this->options['daemonize'] = true;
        }

        // 进程名称为空
		if(empty($this->options['name'])){
            $this->options['name'] = 'think-webworker';
        }

        // 构造新的运行时目录
		$runtimePath = $this->app->getRuntimePath() . $this->options['name'] . DIRECTORY_SEPARATOR;
        // 设置runtime路径
        $this->app->setRuntimePath($runtimePath);

        // 主进程reload
		Worker::$onMasterReload = function () {
			// 清理opcache
            if (function_exists('opcache_get_status')) {
                if ($status = opcache_get_status()) {
                    if (isset($status['scripts']) && $scripts = $status['scripts']) {
                        foreach (array_keys($scripts) as $file) {
                            opcache_invalidate($file, true);
                        }
                    }
                }
            }
        };

		// 内容输出文件路径
		if(!empty($this->options['stdout_file'])){
			// 目录不存在则自动创建
			$stdout_dir = dirname($this->options['stdout_file']);
			if (!is_dir($stdout_dir)){
				mkdir($stdout_dir, 0755, true);
			}
			// 指定stdout文件路径
			Worker::$stdoutFile = $this->options['stdout_file'];
		}
		// pid文件路径
		if(empty($this->options['pid_file'])){
			$this->options['pid_file'] = $runtimePath . 'worker' . DIRECTORY_SEPARATOR . $this->options['name'] . '.pid';
		}

		// 目录不存在则自动创建
		$pid_dir = dirname($this->options['pid_file']);
		if (!is_dir($pid_dir)){
			mkdir($pid_dir, 0755, true);
		}
		// 指定pid文件路径
		Worker::$pidFile = $this->options['pid_file'];
		
		// 日志文件路径
		if(empty($this->options['log_file'])){
			$this->options['log_file'] = $runtimePath . 'worker' . DIRECTORY_SEPARATOR . $this->options['name'] . '.log';
		}
		// 目录不存在则自动创建
		$log_dir = dirname($this->options['log_file']);
		if (!is_dir($log_dir)){
			mkdir($log_dir, 0755, true);
		}
		// 指定日志文件路径
		Worker::$logFile = $this->options['log_file'];

        // 如果指定以守护进程方式运行
        if (true === $this->options['daemonize']) {
            Worker::$daemonize = true;
        }

        // 实例化worker
        $this->worker = new Worker();
        // 设置进程名称
        $this->worker->name = $this->options['name'];
        // 设置进程数
        $this->worker->count = count($this->queueList);

		// 设置回调
        foreach ($this->events as $event) {
            if (method_exists($this, $event)) {
                $this->worker->$event = [$this, $event];
            }
        }

        // 启动
		Worker::runAll();
	}

    /**
     * 停止
     * @access public
     * @return void
     */
    public function stop()
    {
        Worker::stopAll();
    }
}
