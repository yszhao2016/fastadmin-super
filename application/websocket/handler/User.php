<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2026/1/8
 */

namespace App\websocket\handler;

use Swoole\WebSocket\Server as SwooleServer;
use think\facade\Log;

class User
{
    protected $server;
    protected $redis;

    public function __construct(SwooleServer $server = null)
    {
        if ($server) {
            $this->server = $server;
        }

        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }

    /**
     * 用户上线
     */
    public function online($userId, $fd, $data = [])
    {
        // 绑定用户ID和FD
        $this->redis->hSet('im:online_users', $userId, $fd);
        $this->redis->hSet('im:fd_to_user', $fd, $userId);

        // 设置用户信息
        $userInfo = [
            'fd' => $fd,
            'online_time' => time(),
            'device' => $data['device'] ?? 'web',
            'ip' => $data['ip'] ?? '',
            'user_agent' => $data['user_agent'] ?? ''
        ];

        $this->redis->hSet('im:user_info', $userId, json_encode($userInfo));

        // 添加到在线用户集合
        $this->redis->sAdd('im:online_user_set', $userId);

        // 设置最后在线时间
        $this->redis->hSet('im:user_last_online', $userId, time());

        // 通知好友用户上线
        $this->notifyFriendsOnline($userId, true);

        // 发送离线消息
        $this->sendOfflineMessages($userId, $fd);

        Log::info("用户上线: {$userId}, FD: {$fd}");

        return true;
    }

    /**
     * 用户下线
     */
    public function offline($userId, $fd)
    {
        // 从在线用户中移除
        $this->redis->hDel('im:online_users', $userId);
        $this->redis->hDel('im:fd_to_user', $fd);
        $this->redis->hDel('im:user_info', $userId);
        $this->redis->sRem('im:online_user_set', $userId);

        // 更新最后在线时间
        $this->redis->hSet('im:user_last_online', $userId, time());

        // 通知好友用户下线
        $this->notifyFriendsOnline($userId, false);

        Log::info("用户下线: {$userId}, FD: {$fd}");

        return true;
    }

    /**
     * 获取在线用户列表
     */
    public function getOnlineUsers($userId = null)
    {
        if ($userId) {
            $fd = $this->redis->hGet('im:online_users', $userId);
            if ($fd) {
                $userInfo = $this->redis->hGet('im:user_info', $userId);
                return [
                    'user_id' => $userId,
                    'status' => 'online',
                    'info' => $userInfo ? json_decode($userInfo, true) : null
                ];
            } else {
                $lastOnline = $this->redis->hGet('im:user_last_online', $userId);
                return [
                    'user_id' => $userId,
                    'status' => 'offline',
                    'last_online' => $lastOnline
                ];
            }
        }

        // 获取所有在线用户
        $onlineUsers = [];
        $userIds = $this->redis->sMembers('im:online_user_set');

        foreach ($userIds as $uid) {
            $fd = $this->redis->hGet('im:online_users', $uid);
            if ($fd) {
                $userInfo = $this->redis->hGet('im:user_info', $uid);
                $onlineUsers[] = [
                    'user_id' => $uid,
                    'fd' => $fd,
                    'info' => $userInfo ? json_decode($userInfo, true) : null
                ];
            }
        }

        return $onlineUsers;
    }

    /**
     * 获取用户连接
     */
    public function getUserFd($userId)
    {
        return $this->redis->hGet('im:online_users', $userId);
    }

    /**
     * 获取连接对应的用户
     */
    public function getFdUser($fd)
    {
        return $this->redis->hGet('im:fd_to_user', $fd);
    }

    /**
     * 检查用户是否在线
     */
    public function isOnline($userId)
    {
        $fd = $this->getUserFd($userId);
        if (!$fd) {
            return false;
        }

        if ($this->server) {
            return $this->server->isEstablished($fd);
        }

        return false;
    }

    /**
     * 心跳检测
     */
    public function heartbeat($fd)
    {
        $userId = $this->getFdUser($fd);
        if ($userId) {
            $this->redis->hSet('im:user_heartbeat', $userId, time());
            return true;
        }
        return false;
    }

    /**
     * 清理过期心跳
     */
    public function cleanExpiredHeartbeat($timeout = 60)
    {
        $now = time();
        $heartbeats = $this->redis->hGetAll('im:user_heartbeat');

        foreach ($heartbeats as $userId => $time) {
            if ($now - $time > $timeout) {
                // 心跳超时，强制下线
                $fd = $this->getUserFd($userId);
                if ($fd && $this->server) {
                    $this->server->close($fd);
                }
                $this->offline($userId, $fd);
                $this->redis->hDel('im:user_heartbeat', $userId);
            }
        }
    }

    /**
     * 通知好友在线状态变更
     */
    private function notifyFriendsOnline($userId, $isOnline)
    {
        // 获取好友列表
        $friends = $this->getUserFriends($userId);

        foreach ($friends as $friendId) {
            if ($this->isOnline($friendId)) {
                $fd = $this->getUserFd($friendId);
                if ($fd && $this->server && $this->server->isEstablished($fd)) {
                    $this->server->push($fd, json_encode([
                        'type' => 'friend_status',
                        'friend_id' => $userId,
                        'status' => $isOnline ? 'online' : 'offline',
                        'timestamp' => time()
                    ]));
                }
            }
        }
    }

    /**
     * 发送离线消息
     */
    private function sendOfflineMessages($userId, $fd)
    {
        $imService = new \app\service\ImService();
        $offlineMessages = $imService->getOfflineMessages($userId);

        if (empty($offlineMessages)) {
            return;
        }

        foreach ($offlineMessages as $message) {
            if ($this->server && $this->server->isEstablished($fd)) {
                $this->server->push($fd, json_encode($message));
            }
        }

        // 删除已发送的离线消息
        $imService->clearOfflineMessages($userId);
    }

    /**
     * 获取用户好友列表
     */
    private function getUserFriends($userId)
    {
        $friendModel = new \app\common\model\UserFriend();
        $friends = $friendModel->where('user_id', $userId)
            ->where('status', 1)
            ->column('friend_id');

        return $friends ?: [];
    }

    /**
     * 广播系统消息
     */
    public function broadcastSystemMessage($message, $excludeUsers = [])
    {
        $data = [
            'type' => 'system',
            'content' => $message,
            'timestamp' => time()
        ];

        $onlineUsers = $this->getOnlineUsers();
        foreach ($onlineUsers as $user) {
            if (in_array($user['user_id'], $excludeUsers)) {
                continue;
            }

            $fd = $user['fd'];
            if ($fd && $this->server && $this->server->isEstablished($fd)) {
                $this->server->push($fd, json_encode($data));
            }
        }
    }
}