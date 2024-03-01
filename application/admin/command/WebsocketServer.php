<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/2/26
 */

namespace app\admin\command;


use app\common\model\ImUser;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class WebsocketServer extends Command
{

    protected function configure()
    {
        $this->setName('WebsocketServer:start')->setDescription('websocket server');
    }

    public function execute(Input $input, Output $output)
    {
        //创建WebSocket Server对象，监听0.0.0.0:9502端口。
        $ws = new \Swoole\WebSocket\Server('0.0.0.0', 9502);

        //监听WebSocket连接打开事件。
        $ws->on('Open', function ($ws, $request) {
//            echo "客户端：" . $request->fd . "已经连接\n";
            $userid = $request->get["userid"];
            //通过token获取到用户的uid
            $name = ImUser::login($userid,$request->fd);
//            $ws->push("编号{$name}:上线了");
            foreach ($ws->connections as $fd) {
                if ($request->fd == $fd) {
                    continue;
                }
                $ws->push($fd, "编号{$name}:上线了");
            }
        });

        //监听WebSocket消息事件。
        $ws->on('Message', function ($ws, $frame) {
//            echo "Message: {$frame->data}\n";
//            foreach ($ws->connections as $fd) {
//                $ws->push($fd, $frame->data);
//            }
            $params = json_decode($frame->data, true);
            $ws->push($params['touid'], "{fromuid: 1,touid:2,data:".$frame->data."}");
        });

        //监听WebSocket连接关闭事件。
        $ws->on('Close', function ($ws, $fd) {
            echo "client-{$fd} is closed\n";
            $name = ImUser::logout($fd);
            foreach ($ws->connections as $tfd) {
                if ($fd== $tfd) {
                    continue;
                }
                $ws->push($fd, "编号{$name}:下线了");
            }
        });

        $ws->start();

    }
}