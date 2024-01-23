<?php
/**
 * Created by PhpStorm
 * USER: Zhaoys
 * Date: 2022/2/8 19:09
 */

namespace app\hj212\command;

use app\admin\model\hj212\OriginalData;
use app\hj212\model\Data;
use app\hj212\model\Pollution;
use app\hj212\segment\converter\DataConverter;
use app\hj212\VerifyUtil;
use app\job\Hj212DataParser;
use app\job\Message;
use Swoole\Server as SwooleServer;
use app\hj212\T212Parser;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;
use think\Db;
use think\Queue;

class Hj212Server extends Command
{
    // Server 实例
    protected $server;

    protected $t212Parser;

    protected $buffer;

    protected function configure()
    {
        $this->setName('hj212:start')->setDescription('Start hj212 Server!');
    }

    protected function execute(Input $input, Output $output)
    {
//        // 监听所有地址，监听端口f
        $this->server = new SwooleServer('0.0.0.0', 65212);

        $this->server->set(array(
            'worker_num' => 10,
//            'task_worker_num' => 2,   //必须设置 on task
            'max_request' => 10000,
//            'open_length_check' => true,
//            'package_length_func' => '',
            'daemonize' => true,
            'log_file' => "runtime/log/swoole.log",
            'dispatch_mode' => 2,
        ));
        $this->t212Parser = new T212Parser();
        $this->server->on('start', array($this, 'onStart'));

        $this->server->on('connect', array($this, 'onConnect'));
        $this->server->on('receive', array($this, 'onReceive'));

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
        file_put_contents('runtime/log/hj212connect_' . date("Ymd") . '.log', date('Y-m-d H:i:s') . "ip" . $this->getIP() . "open: {$fd}");
    }

    public function onReceive($serv, $fd, $reactor_id, $data)
    {
        file_put_contents('runtime/log/hj212receive_' . date("Ymd") . '.log', date('Y-m-d H:i:s') . "receive:  " . $data . PHP_EOL, FILE_APPEND);

        $tempdata = rtrim($data, "\r\n");
        $strlen = strlen($tempdata);
        $endstr = substr($tempdata, $strlen - 6, 2);
        $strartstr = substr($data, 0, 2);
        if ($strartstr == "##" && $endstr != "&&") {
            $this->buffer = $data;
            return;
        } else if ($strartstr != "##" && $endstr == "&&" && $this->buffer && VerifyUtil::verifyCrc($this->buffer . $tempdata)) {
            $data = rtrim($this->buffer, "\r\n") . $data;
        } else if ($strartstr != "##" && $endstr == "&&" && $this->buffer && !VerifyUtil::verifyCrc($this->buffer . $tempdata)) {
            $this->buffer = rtrim($this->buffer, "\r\n") . $tempdata;
            return;
            //##1715QN=20230727145559023;ST=32;CN=3020;PW=123456;MN=000102;Flag=5;CP=&&Data
        }else if($strartstr == "##" && $endstr == "&&" && !VerifyUtil::verifyCrc($this->buffer . $tempdata)){
            $this->buffer = $data;
            return;
        }
        Queue::push(Hj212DataParser::class,$data,"hj212");
        preg_match('/QN=([^;]+)/', $data, $qnarr);
        preg_match('/PW=([^;]+)/', $data, $pwarr);
        preg_match('/MN=([^;]+)/', $data, $mnarr);
        $qn = isset($qnarr[1]) ? $qnarr[1] : "";
        $pw = isset($pwarr[1]) ? $pwarr[1] : "";
        $mn = isset($mnarr[1]) ? $mnarr[1] : "";
        $str = "QN={$qn};ST=91;CN=9014;PW={$pw};MN={$mn};Flag=4;CP=&&&&\r\n";
        $num = strlen($str);
        $newNum = str_pad($num, 4, "0", STR_PAD_LEFT);
        $resStr = "##" . $newNum . $str;
        $this->buffer = "";
        $this->server->send($fd, $resStr);

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

    public function getIP()
    {
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $realip = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '127.0.0.1';
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }
        return $realip;
    }

}