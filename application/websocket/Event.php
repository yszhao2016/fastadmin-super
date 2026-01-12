<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2026/1/7
 */

namespace App\websocket;

use Swoole\WebSocket\Server as SwooleServer;
use app\websocket\handler\Message as MessageHandler;
use app\websocket\handler\User as UserHandler;
use app\websocket\handler\Auth as AuthHandler;

class Event
{
    protected $server;
    protected $frame;
    protected $redis;
    protected $messageHandler;
    protected $userHandler;
    protected $authHandler;

    public function __construct(SwooleServer $server, $frame, $redis)
    {
        $this->server = $server;
        $this->frame = $frame;
        $this->redis = $redis;

        $this->messageHandler = new MessageHandler($server);
        $this->userHandler = new UserHandler($server);
        $this->authHandler = new AuthHandler();
    }

    /**
     * 处理认证
     */
    public function auth($data)
    {
        $token = $data['token'] ?? '';
        $userId = $this->authHandler->verifyToken($token);

        if (!$userId) {
            $this->server->close($this->frame->fd);
            return;
        }

        // 绑定用户
        $this->userHandler->online($userId, $this->frame->fd, [
            'device' => $data['device'] ?? 'web',
            'ip' => $this->getClientIp(),
            'user_agent' => $data['user_agent'] ?? ''
        ]);

        // 发送认证成功消息
        $this->server->push($this->frame->fd, json_encode([
            'type' => 'auth_success',
            'user_id' => $userId,
            'timestamp' => time()
        ]));
    }

    /**
     * 处理聊天消息
     */
    public function chat($data)
    {
        $messageType = $data['message_type'] ?? 'private';

        switch ($messageType) {
            case 'private':
                $this->messageHandler->handlePrivateChat($data, $this->frame->fd);
                break;
            case 'group':
                $this->messageHandler->handleGroupChat($data, $this->frame->fd);
                break;
            case 'recall':
                $this->messageHandler->recallMessage($data, $this->frame->fd);
                break;
            case 'read_receipt':
                $this->messageHandler->messageRead($data, $this->frame->fd);
                break;
        }
    }

    /**
     * 处理心跳
     */
    public function heartbeat($data)
    {
        $this->userHandler->heartbeat($this->frame->fd);

        $this->server->push($this->frame->fd, json_encode([
            'type' => 'heartbeat',
            'timestamp' => time()
        ]));
    }

    /**
     * 处理用户状态
     */
    public function user($data)
    {
        $action = $data['action'] ?? '';

        switch ($action) {
            case 'get_online_status':
                $userId = $data['user_id'] ?? null;
                $status = $this->userHandler->getOnlineUsers($userId);
                $this->server->push($this->frame->fd, json_encode([
                    'type' => 'online_status',
                    'data' => $status
                ]));
                break;
        }
    }

    /**
     * 获取客户端IP
     */
    private function getClientIp()
    {
        $fdInfo = $this->server->getClientInfo($this->frame->fd);
        return $fdInfo['remote_ip'] ?? '0.0.0.0';
    }
}