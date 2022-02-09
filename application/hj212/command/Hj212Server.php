<?php
/**
 * Created by PhpStorm
 * USER: Zhaoys
 * Date: 2022/2/8 19:09
 */

namespace app\hj212\command;

use Swoole\Server as SwooleServer;
use app\hj212\T212Parser;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Hj212Server extends Command
{
    // Server 实例
    protected $server;

    protected $t212Parser;

//    public function __construct($name = null)
//    {
//        parent::__construct($name);
//
//    }

    protected function configure()
    {
        $this->setName('hj212:start')->setDescription('Start Web Socket Server!');
    }

    protected function execute(Input $input, Output $output)
    {
        // 监听所有地址，监听端口
        $this->server = new SwooleServer('192.168.100.230', 9503);
        $this->t212Parser = new T212Parser();
        $this->server->set( array(
            'worker_num'=> 5,
            'task_worker_num' => 2,   //必须设置 on task
            'max_request' => 10000,
            'daemonize' => 0,
            'log_file' => "/runtime/log/swoole.log",
            'dispatch_mode' => 2,
        ));

        $this->server->on('start', array($this, 'onStart'));
        // Task 回调的2个必须函数
        $this->server->on('connect', array($this, 'onConnect'));
        $this->server->on('receive', array($this, 'onReceive'));
        // $this->serv->taskwait($data); 触发同步
        $this->server->on('task', array($this, 'onSyncTask'));
        // $this->serv->task($data); 触发异步
//        $this->serv->on('task', array($this, 'onAsynTask'));
        $this->server->on('finish', array($this, 'onFinish'));
        $this->server->on('close', array($this, 'onClose'));
        $this->server->start();

    }


    public function onStart($serv)
    {
        echo "TCP Server is started at tcp://\n";
    }

    public function onConnect($serv, $fd)
    {
        echo "connection open: {$fd}\n";
    }

    public function onReceive($serv, $fd, $reactor_id, $data)
    {
        $this->server->send($fd, "Swoole: {$data}");
        //开启同步 检查协议
        $result = $this->server->taskwait($data);
//        if ($result !== false) {
//            $result = json_decode($result,true);
//            if ($result['status'] == 'OK') {
//                $this->serv->send($fd, "接收成功\n");
//            } else {
//                $this->serv->send($fd, $result);
//            }
//            return;
//        } else {
//            $this->serv->send($fd, "Error. Task timeout\n");
//        }

        //开启异步
//        $this->serv->task($data);
        $this->server->send($fd, "onSyncTask: {$result}");
    }

    public function onClose($serv, $fd)
    {
        echo "connection close: {$fd}\n";
    }

    /**
     * 同步任务
     * $this->serv->taskwait($data); 触发
     * @param $server
     * @param $task_id
     * @param $data
     */
    function onSyncTask($serv, $task_id, $from_id, $data)
    {
        echo "Sync task Callback: " . $data;

        $this->t212Parser->setReader($data);
        $header = $this->t212Parser->readHeader();
        $dataLen = $this->t212Parser->readDataLen();
        $data = $this->t212Parser->readDataAndCrc($dataLen);
        echo $data;

        $result = $data;
        // 通知完成
        $this->server->finish($result);
    }

    /**
     * 异步任务
     * $this->serv->task($data); 触发
     * @param $server
     * @param $task_id
     * @param $data
     */
    function onAsynTask($serv, $task_id, $from_id, $data)
    {
        echo "Asyn task Callback: ";
        // 通知完成
        $this->server->finish($data . "cccccsssss");

    }

    public function onFinish($serv, $task_id, $data)
    {
        echo "任务完成";//taskwait  不触发这个函数。。
    }


}