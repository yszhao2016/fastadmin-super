<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2026/1/8
 */

namespace App\websocket\handler;


use App\websocket\model\UserBlacklist;
use \think\Cache;
class Auth
{
    protected $redis;

    public function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }

    /**
     * 验证Token
     */
    public function verifyToken($token)
    {
        if (empty($token)) {
            return false;
        }

        $userId = \think\Cache::get('ws_token:' . $token);

        if (!$userId) {
            // 尝试从数据库验证
            $user = $this->verifyDatabaseToken($token);
            if ($user) {
                $userId = $user['id'];
                // 缓存Token
                Cache::set('ws_token:' . $token, $userId, 300);
            }
        }

        return $userId;
    }

    /**
     * 数据库验证Token
     */
    private function verifyDatabaseToken($token)
    {
        // 这里需要根据你的实际业务实现
        // 例如：验证JWT Token或从数据库查询
        $userModel = new \app\common\model\User();
        $user = $userModel->where('token', $token)
            ->where('token_expire_time', '>', time())
            ->find();

        if ($user) {
            return $user->toArray();
        }

        return false;
    }

    /**
     * 生成连接Token
     */
    public function generateToken($userId)
    {
        $token = md5($userId . time() . rand(1000, 9999) . uniqid());

        // 存储Token，有效期5分钟
        Cache::set('ws_token:' . $token, $userId, 300);

        // 记录用户Token
        $this->redis->hSet('im:user_tokens', $userId, $token);

        return $token;
    }

    /**
     * 刷新Token有效期
     */
    public function refreshToken($token)
    {
        $userId = Cache::get('ws_token:' . $token);
        if ($userId) {
            Cache::set('ws_token:' . $token, $userId, 300);
            return true;
        }
        return false;
    }

    /**
     * 获取用户在线状态
     */
    public function getUserOnlineStatus($userId)
    {
        $fd = $this->redis->hGet('im:online_users', $userId);
        if ($fd) {
            return [
                'status' => 'online',
                'fd' => $fd,
                'last_online' => $this->redis->hGet('im:user_last_online', $userId)
            ];
        }

        $lastOnline = $this->redis->hGet('im:user_last_online', $userId);
        return [
            'status' => 'offline',
            'last_online' => $lastOnline
        ];
    }

    /**
     * 验证用户权限
     */
    public function checkUserPermission($userId, $targetId, $type = 'chat')
    {
        switch ($type) {
            case 'chat':
                return $this->canChat($userId, $targetId);
            case 'group':
                return $this->inGroup($userId, $targetId);
            default:
                return true;
        }
    }

    /**
     * 检查是否可以聊天
     */
    private function canChat($userId, $targetId)
    {
        // 检查是否在黑名单
        if ($this->isInBlacklist($userId, $targetId)) {
            return false;
        }

        // 检查好友关系
        $friendModel = new \app\common\model\UserFriend();
        $isFriend = $friendModel->where([
            'user_id' => $userId,
            'friend_id' => $targetId,
            'status' => 1
        ])->find();

        return $isFriend ? true : false;
    }

    /**
     * 检查是否在群组
     */
    private function inGroup($userId, $groupId)
    {
        $groupMemberModel = new \app\common\model\GroupMember();
        $isMember = $groupMemberModel->where([
            'group_id' => $groupId,
            'user_id' => $userId,
            'status' => 1
        ])->find();

        return $isMember ? true : false;
    }

    /**
     * 检查黑名单
     */
    private function isInBlacklist($userId, $targetId)
    {
        $blacklistModel = new UserBlacklist();
        $exists = $blacklistModel->where([
            'user_id' => $userId,
            'black_user_id' => $targetId
        ])->find();

        return $exists ? true : false;
    }
}