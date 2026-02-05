<?php
// application/websocket/WsServer.php
namespace App\websocket;

use think\swoole\WebSocketServer as Server;
use think\swoole\websocket\Room;
use Swoole\WebSocket\Server as SwooleServer;
use Swoole\WebSocket\Frame;

class WsServer extends Server
{
    protected $redis;

    public function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }

    /**
     * 连接建立
     */
    public function onOpen(SwooleServer $server, $request)
    {
        $token = $request->get['token'] ?? '';
        $userId = $this->verifyToken($token);

        if (!$userId) {
            $server->close($request->fd);
            return;
        }

        // 绑定用户ID和连接FD
        $this->bindUser($userId, $request->fd);

        // 加入全局房间
        $this->room->add($request->fd, 'global');

        // 发送连接成功消息
        $server->push($request->fd, json_encode([
            'type' => 'system',
            'event' => 'connect_success',
            'data' => ['user_id' => $userId]
        ]));
    }

    /**
     * 消息处理
     */
    public function onMessage(SwooleServer $server, Frame $frame)
    {
        $data = json_decode($frame->data, true);

        if (!$data || !isset($data['type'])) {
            return;
        }

        $eventHandler = new \app\websocket\Event($server, $frame, $this->redis);

        switch ($data['type']) {
            case 'auth':
                $eventHandler->auth($data);
                break;
            case 'chat':
                $eventHandler->chat($data);
                break;
            case 'heartbeat':
                $eventHandler->heartbeat($data);
                break;
            case 'group':
                $eventHandler->group($data);
                break;
        }
    }

    /**
     * 连接关闭
     */
    public function onClose($server, $fd)
    {
        $userId = $this->getUserByFd($fd);
        if ($userId) {
            $this->offline($userId, $fd);
        }

        $this->room->leave($fd, 'global');
    }

    /**
     * 验证Token
     */
    private function verifyToken($token)
    {
        // 实现Token验证逻辑
        return Cache::get('ws_token:' . $token);
    }

    /**
     * 绑定用户
     */
    private function bindUser($userId, $fd)
    {
        $this->redis->hSet('im:online_users', $userId, $fd);
        $this->redis->hSet('im:fd_to_user', $fd, $userId);
    }
}