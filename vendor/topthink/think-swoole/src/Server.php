<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\swoole;

use Swoole\Http\Server as HttpServer;
use Swoole\Server as SwooleServer;
use Swoole\Websocket\Server as Websocket;

/**
 * Swoole Server扩展类
 */
abstract class Server
{
    /**
     * Swoole对象
     * @var object
     */
    protected $swoole;

    /**
     * SwooleServer类型
     * @var string
     */
    protected $serverType = 'http';

    /**
     * Socket的类型
     * @var int
     */
    protected $sockType;

    /**
     * 运行模式
     * @var int
     */
    protected $mode;

    /**
     * 监听地址
     * @var string
     */
    protected $host = '0.0.0.0';

    /**
     * 监听端口
     * @var int
     */
    protected $port = 9501;

    /**
     * 配置
     * @var array
     */
    protected $option = [];

    /**
     * 支持的响应事件
     * @var array
     */
    protected $event = ['Start', 'Shutdown', 'WorkerStart', 'WorkerStop', 'WorkerExit', 'Connect', 'Receive', 'Packet', 'Close', 'BufferFull', 'BufferEmpty', 'Task', 'Finish', 'PipeMessage', 'WorkerError', 'ManagerStart', 'ManagerStop', 'Open', 'Message', 'HandShake', 'Request'];

    /**
     * 架构函数
     * @access public
     */
    public function __construct()
    {
        // 实例化 Swoole 服务
        switch ($this->serverType) {
            case 'socket':
                $this->swoole = new Websocket($this->host, $this->port);
                break;
            case 'http':
                $this->swoole = new HttpServer($this->host, $this->port);
                break;
            default:
                $this->swoole = new SwooleServer($this->host, $this->port, $this->mode, $this->sockType);
        }

        // 设置参数
        if (!empty($this->option)) {
            $this->swoole->set($this->option);
        }

        // 设置回调
        foreach ($this->event as $event) {
            if (method_exists($this, 'on' . $event)) {
                $this->swoole->on($event, [$this, 'on' . $event]);
            }
        }

        // 初始化
        $this->init();
    }

    protected function init()
    {
    }

    public function start()
    {
        // Run worker
        $this->swoole->start();
    }

    public function stop()
    {
        $this->swoole->stop();
    }

    /**
     * 魔术方法 有不存在的操作的时候执行
     * @access public
     * @param string $method 方法名
     * @param array $args 参数
     * @return mixed
     */
    public function __call($method, $args)
    {
        call_user_func_array([$this->swoole, $method], $args);
    }
}
