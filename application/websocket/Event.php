<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2026/1/7
 */

namespace App\websocket;

use Swoole\WebSocket\Server as SwooleServer;
use app\service\ImService;

class Event
{
    protected $server;
    protected $frame;
    protected $redis;
    protected $imService;

    public function __construct(SwooleServer $server, $frame, $redis)
    {
        $this->server = $server;
        $this->frame = $frame;
        $this->redis = $redis;
        $this->imService = new ImService();
    }

    /**
     * 处理私聊消息
     */
    public function chat($data)
    {
        $message = [
            'type' => 'chat',
            'from_user' => $data['user_id'],
            'to_user' => $data['to_user'],
            'content' => $data['content'],
            'msg_type' => $data['msg_type'] ?? 'text',
            'timestamp' => time()
        ];

        // 保存到数据库
        $msgId = $this->imService->saveMessage($message);
        $message['msg_id'] = $msgId;

        // 获取接收方FD
        $toFd = $this->redis->hGet('im:online_users', $data['to_user']);

        if ($toFd && $this->server->isEstablished($toFd)) {
            // 接收方在线，实时推送
            $this->server->push($toFd, json_encode($message));

            // 发送送达回执
            $this->server->push($this->frame->fd, json_encode([
                'type' => 'receipt',
                'msg_id' => $msgId,
                'status' => 'delivered'
            ]));
        } else {
            // 接收方离线，存储离线消息
            $this->imService->saveOfflineMessage($data['to_user'], $message);
        }

        // 推送给自己（消息同步）
        $this->server->push($this->frame->fd, json_encode($message));
    }

    /**
     * 处理群聊消息
     */
    public function group($data)
    {
        $groupId = $data['group_id'];
        $members = $this->imService->getGroupMembers($groupId);

        $message = [
            'type' => 'group',
            'from_user' => $data['user_id'],
            'group_id' => $groupId,
            'content' => $data['content'],
            'timestamp' => time()
        ];

        // 保存群消息
        $msgId = $this->imService->saveGroupMessage($message);
        $message['msg_id'] = $msgId;

        // 推送给所有在线群成员
        foreach ($members as $memberId) {
            if ($memberId == $data['user_id']) continue;

            $toFd = $this->redis->hGet('im:online_users', $memberId);
            if ($toFd && $this->server->isEstablished($toFd)) {
                $this->server->push($toFd, json_encode($message));
            } else {
                $this->imService->saveOfflineMessage($memberId, $message);
            }
        }
    }

    /**
     * 心跳处理
     */
    public function heartbeat($data)
    {
        $this->server->push($this->frame->fd, json_encode([
            'type' => 'heartbeat',
            'timestamp' => time()
        ]));
    }
}