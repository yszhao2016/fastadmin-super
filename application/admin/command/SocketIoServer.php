<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/3/1
 */

namespace app\admin\command;


use think\console\Command;
use think\console\Input;
use think\console\Output;
use Swoole\Http\Server;
use Swoole\WebSocket\Server as WebSocketServer;
use Swoole\WebSocket\Frame;

class SocketIoServer extends Command
{

    protected function configure()
    {
        $this->setName('SocketIoServer:start')->setDescription('SocketIo Server');
    }

    public function execute(Input $input, Output $output)
    {
        $server = new Server("0.0.0.0", 9502);

        $server->on("request", function ($request, $response) {
            $response->header("Content-Type", "text/plain");
            $response->end("This is a socket.io server");
        });

        $server->on("message", function (WebSocketServer $server, Frame $frame) {
            $message = json_decode($frame->data, true);
            switch ($message['event']) {
                case 'connection':
                    $socketId = $server->getClientInfo($frame->fd)['remote_addr'];
                    $server->push($frame->fd, '{"event": "connected", "socketId": "' . $socketId . '"}');
                    break;
                case 'chat_message':
                    $clients = $server->connection_list();
                    foreach ($clients as $fd) {
                        $server->push($fd, '{"event": "chat_message", "data": "' . $message['data'] . '"}');
                    }
                    break;
                default:
                    $server->push($frame->fd, '{"event": "error", "message": "Unknown event"}');
            }
        });

        $server->start();
    }

}