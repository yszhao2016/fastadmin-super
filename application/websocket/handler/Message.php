<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2026/1/8
 */

namespace App\websocket\handler;

use Swoole\WebSocket\Server as SwooleServer;
use app\service\ImService;
use think\facade\Log;

class Message
{
    protected $server;
    protected $redis;
    protected $imService;
    protected $authHandler;

    public function __construct(SwooleServer $server = null)
    {
        if ($server) {
            $this->server = $server;
        }

        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379);

        $this->imService = new ImService();
        $this->authHandler = new Auth();
    }

    /**
     * 处理私聊消息
     */
    public function handlePrivateChat($data, $fd)
    {
        $userId = $this->redis->hGet('im:fd_to_user', $fd);

        if (!$userId) {
            return $this->sendError($fd, '用户未认证');
        }

        // 验证权限
        if (!$this->authHandler->checkUserPermission($userId, $data['to_user'], 'chat')) {
            return $this->sendError($fd, '无权限发送消息');
        }

        // 消息限流
        if (!$this->checkRateLimit($userId)) {
            return $this->sendError($fd, '消息发送过于频繁');
        }

        // 敏感词过滤
        $data['content'] = $this->filterSensitiveWords($data['content']);

        $message = [
            'type' => 'chat',
            'from_user' => $userId,
            'to_user' => $data['to_user'],
            'content' => $data['content'],
            'msg_type' => $data['msg_type'] ?? 'text',
            'extra' => $data['extra'] ?? [],
            'timestamp' => time(),
            'client_msg_id' => $data['client_msg_id'] ?? ''
        ];

        // 保存消息
        $msgId = $this->imService->saveMessage($message);
        $message['msg_id'] = $msgId;

        // 获取会话ID
        $sessionId = $this->imService->getOrCreateSession($userId, $data['to_user']);
        $message['session_id'] = $sessionId;

        // 更新会话
        $this->imService->updateSession($sessionId, [
            'last_msg' => $data['content'],
            'last_time' => date('Y-m-d H:i:s'),
            'unread_count' => ['exp', 'unread_count+1']
        ], $data['to_user']);

        // 发送消息
        $this->sendToUser($message, $data['to_user']);

        // 发送回执
        $this->sendReceipt($fd, $msgId, 'delivered');

        // 记录消息日志
        Log::info('私聊消息', $message);

        return $msgId;
    }

    /**
     * 处理群聊消息
     */
    public function handleGroupChat($data, $fd)
    {
        $userId = $this->redis->hGet('im:fd_to_user', $fd);

        if (!$userId) {
            return $this->sendError($fd, '用户未认证');
        }

        // 验证权限
        if (!$this->authHandler->checkUserPermission($userId, $data['group_id'], 'group')) {
            return $this->sendError($fd, '无权限在群内发言');
        }

        // 消息限流
        if (!$this->checkRateLimit($userId, 'group')) {
            return $this->sendError($fd, '消息发送过于频繁');
        }

        // 敏感词过滤
        $data['content'] = $this->filterSensitiveWords($data['content']);

        $message = [
            'type' => 'group',
            'from_user' => $userId,
            'group_id' => $data['group_id'],
            'content' => $data['content'],
            'msg_type' => $data['msg_type'] ?? 'text',
            'extra' => $data['extra'] ?? [],
            'timestamp' => time(),
            'client_msg_id' => $data['client_msg_id'] ?? ''
        ];

        // 保存群消息
        $msgId = $this->imService->saveGroupMessage($message);
        $message['msg_id'] = $msgId;

        // 获取群成员
        $members = $this->imService->getGroupMembers($data['group_id']);

        // 更新群会话
        $this->imService->updateGroupSession($data['group_id'], [
            'last_msg' => $data['content'],
            'last_time' => date('Y-m-d H:i:s')
        ]);

        // 发送给群成员
        $sentCount = 0;
        foreach ($members as $memberId) {
            if ($memberId == $userId) {
                // 不发送给自己
                continue;
            }

            $message['to_user'] = $memberId;
            if ($this->sendToUser($message, $memberId)) {
                $sentCount++;
            }
        }

        // 发送给自己（同步消息）
        $message['to_user'] = $userId;
        $this->sendToUser($message, $userId);

        // 记录消息日志
        Log::info('群聊消息', [
            'msg_id' => $msgId,
            'group_id' => $data['group_id'],
            'sender' => $userId,
            'sent_count' => $sentCount
        ]);

        return $msgId;
    }

    /**
     * 发送消息给用户
     */
    public function sendToUser($message, $userId)
    {
        $fd = $this->redis->hGet('im:online_users', $userId);

        if ($fd && $this->server && $this->server->isEstablished($fd)) {
            // 用户在线，实时推送
            $this->server->push($fd, json_encode($message));

            // 更新消息状态为已送达
            if (isset($message['msg_id'])) {
                $this->imService->updateMessageStatus($message['msg_id'], 2);
            }

            return true;
        } else {
            // 用户离线，存储离线消息
            $this->imService->saveOfflineMessage($userId, $message);
            return false;
        }
    }

    /**
     * 消息撤回
     */
    public function recallMessage($data, $fd)
    {
        $userId = $this->redis->hGet('im:fd_to_user', $fd);

        if (!$userId) {
            return false;
        }

        $msgId = $data['msg_id'];
        $message = $this->imService->getMessage($msgId);

        if (!$message || $message['from_user'] != $userId) {
            return $this->sendError($fd, '无权撤回此消息');
        }

        // 检查消息是否超过2分钟
        if (time() - strtotime($message['created_at']) > 120) {
            return $this->sendError($fd, '消息超过2分钟，无法撤回');
        }

        // 更新消息为撤回状态
        $this->imService->recallMessage($msgId);

        // 构造撤回消息
        $recallMsg = [
            'type' => 'recall',
            'msg_id' => $msgId,
            'from_user' => $userId,
            'timestamp' => time()
        ];

        // 发送撤回通知
        if ($message['to_group']) {
            // 群消息撤回
            $recallMsg['group_id'] = $message['to_group'];
            $members = $this->imService->getGroupMembers($message['to_group']);

            foreach ($members as $memberId) {
                $this->sendToUser($recallMsg, $memberId);
            }
        } else {
            // 私聊消息撤回
            $recallMsg['to_user'] = $message['to_user'];
            $this->sendToUser($recallMsg, $message['to_user']);

            // 也发给自己
            $recallMsg['to_user'] = $userId;
            $this->sendToUser($recallMsg, $userId);
        }

        return true;
    }

    /**
     * 消息已读回执
     */
    public function messageRead($data, $fd)
    {
        $userId = $this->redis->hGet('im:fd_to_user', $fd);

        if (!$userId) {
            return false;
        }

        $msgIds = is_array($data['msg_ids']) ? $data['msg_ids'] : [$data['msg_ids']];

        foreach ($msgIds as $msgId) {
            $this->imService->updateMessageStatus($msgId, 1);
        }

        // 发送回执给发送方
        $readReceipt = [
            'type' => 'read_receipt',
            'msg_ids' => $msgIds,
            'reader' => $userId,
            'timestamp' => time()
        ];

        // TODO: 这里需要获取消息的发送方，简化处理
        // 实际应根据消息ID查询发送方

        return true;
    }

    /**
     * 发送回执
     */
    private function sendReceipt($fd, $msgId, $status)
    {
        if ($this->server && $this->server->isEstablished($fd)) {
            $this->server->push($fd, json_encode([
                'type' => 'receipt',
                'msg_id' => $msgId,
                'status' => $status,
                'timestamp' => time()
            ]));
        }
    }

    /**
     * 发送错误
     */
    private function sendError($fd, $message)
    {
        if ($this->server && $this->server->isEstablished($fd)) {
            $this->server->push($fd, json_encode([
                'type' => 'error',
                'message' => $message,
                'timestamp' => time()
            ]));
        }
        return false;
    }

    /**
     * 检查消息发送频率
     */
    private function checkRateLimit($userId, $type = 'private')
    {
        $key = "im:rate_limit:{$userId}:{$type}";
        $limit = $type === 'private' ? 10 : 30; // 私聊10条/分钟，群聊30条/分钟

        $count = $this->redis->incr($key);
        if ($count == 1) {
            $this->redis->expire($key, 60);
        }

        return $count <= $limit;
    }

    /**
     * 敏感词过滤
     */
    private function filterSensitiveWords($content)
    {
        // 这里可以接入敏感词库
        $sensitiveWords = ['敏感词1', '敏感词2', '敏感词3'];

        foreach ($sensitiveWords as $word) {
            $content = str_replace($word, '***', $content);
        }

        return $content;
    }
}