<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2026/1/8
 */

namespace App\websocket\service;


class ImService
{
    protected $redis;

    public function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }

    /**
     * 保存消息
     */
    public function saveMessage($data)
    {
        $messageData = [
            'session_id' => $this->getSessionId($data['from_user'], $data['to_user']),
            'from_user' => $data['from_user'],
            'to_user' => $data['to_user'],
            'msg_type' => $data['msg_type'] ?? 'text',
            'content' => $data['content'] ?? '',
            'extra' => isset($data['extra']) ? json_encode($data['extra'], JSON_UNESCAPED_UNICODE) : '{}',
            'status' => 0, // 0: 未读, 1: 已读, 2: 已送达
            'created_at' => date('Y-m-d H:i:s')
        ];

        $msgId = Db::name('im_message')->insertGetId($messageData);

        if ($msgId) {
            // 记录消息ID到会话
            $this->updateSessionMessage($messageData['session_id'], $msgId);
        }

        return $msgId;
    }

    /**
     * 保存群消息
     */
    public function saveGroupMessage($data)
    {
        $messageData = [
            'session_id' => 'group_' . $data['group_id'],
            'from_user' => $data['from_user'],
            'to_group' => $data['group_id'],
            'msg_type' => $data['msg_type'] ?? 'text',
            'content' => $data['content'] ?? '',
            'extra' => isset($data['extra']) ? json_encode($data['extra'], JSON_UNESCAPED_UNICODE) : '{}',
            'status' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $msgId = Db::name('im_message')->insertGetId($messageData);

        if ($msgId) {
            // 为每个群成员创建消息记录
            $members = $this->getGroupMembers($data['group_id']);
            foreach ($members as $memberId) {
                if ($memberId != $data['from_user']) {
                    $this->saveGroupMemberMessage($msgId, $data['group_id'], $memberId);
                }
            }
        }

        return $msgId;
    }

    /**
     * 保存群成员消息记录
     */
    private function saveGroupMemberMessage($msgId, $groupId, $userId)
    {
        Db::name('im_group_message')->insert([
            'msg_id' => $msgId,
            'group_id' => $groupId,
            'user_id' => $userId,
            'status' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 获取或创建会话
     */
    public function getOrCreateSession($user1, $user2)
    {
        $sessionKey = $this->getSessionKey($user1, $user2);

        $session = Db::name('im_session')
            ->where('session_key', $sessionKey)
            ->find();

        if (!$session) {
            $sessionId = Db::name('im_session')->insertGetId([
                'session_key' => $sessionKey,
                'type' => 1, // 1: 私聊
                'user1' => $user1,
                'user2' => $user2,
                'last_msg' => '',
                'last_time' => date('Y-m-d H:i:s'),
                'unread_count' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $session = Db::name('im_session')->find($sessionId);
        }

        return $session['session_key'];
    }

    /**
     * 更新会话
     */
    public function updateSession($sessionKey, $data, $increaseUnreadUserId = null)
    {
        $updateData = $data;

        if ($increaseUnreadUserId) {
            // 增加未读数（为对方增加）
            $session = Db::name('im_session')
                ->where('session_key', $sessionKey)
                ->find();

            if ($session) {
                if ($session['user1'] == $increaseUnreadUserId) {
                    $updateData['user1_unread'] = Db::raw('user1_unread+1');
                } elseif ($session['user2'] == $increaseUnreadUserId) {
                    $updateData['user2_unread'] = Db::raw('user2_unread+1');
                }
            }
        }

        Db::name('im_session')
            ->where('session_key', $sessionKey)
            ->update($updateData);
    }

    /**
     * 更新群会话
     */
    public function updateGroupSession($groupId, $data)
    {
        $sessionKey = 'group_' . $groupId;

        Db::name('im_session')
            ->where('session_key', $sessionKey)
            ->update($data);
    }

    /**
     * 获取群成员
     */
    public function getGroupMembers($groupId)
    {
        $cacheKey = "im:group_members:{$groupId}";
        $members = $this->redis->get($cacheKey);

        if ($members === false) {
            $members = Db::name('im_group_member')
                ->where('group_id', $groupId)
                ->where('status', 1)
                ->column('user_id');

            if ($members) {
                $this->redis->set($cacheKey, json_encode($members), 300);
            }
        } else {
            $members = json_decode($members, true);
        }

        return $members ?: [];
    }

    /**
     * 保存离线消息
     */
    public function saveOfflineMessage($userId, $message)
    {
        $key = "im:offline_messages:{$userId}";
        $message['saved_at'] = date('Y-m-d H:i:s');

        $this->redis->lPush($key, json_encode($message, JSON_UNESCAPED_UNICODE));

        // 限制离线消息数量
        $this->redis->lTrim($key, 0, 99);

        // 设置过期时间（7天）
        $this->redis->expire($key, 604800);

        return true;
    }

    /**
     * 获取离线消息
     */
    public function getOfflineMessages($userId)
    {
        $key = "im:offline_messages:{$userId}";
        $messages = [];

        $messageStrs = $this->redis->lRange($key, 0, -1);
        foreach ($messageStrs as $messageStr) {
            $messages[] = json_decode($messageStr, true);
        }

        return $messages;
    }

    /**
     * 清除离线消息
     */
    public function clearOfflineMessages($userId)
    {
        $key = "im:offline_messages:{$userId}";
        return $this->redis->del($key);
    }

    /**
     * 更新消息状态
     */
    public function updateMessageStatus($msgId, $status)
    {
        Db::name('im_message')
            ->where('id', $msgId)
            ->update(['status' => $status]);

        // 如果是群消息，更新群消息表
        Db::name('im_group_message')
            ->where('msg_id', $msgId)
            ->update(['status' => $status]);

        return true;
    }

    /**
     * 撤回消息
     */
    public function recallMessage($msgId)
    {
        return Db::name('im_message')
            ->where('id', $msgId)
            ->update([
                'status' => 3, // 3: 已撤回
                'recalled_at' => date('Y-m-d H:i:s')
            ]);
    }

    /**
     * 获取消息
     */
    public function getMessage($msgId)
    {
        return Db::name('im_message')
            ->where('id', $msgId)
            ->find();
    }

    /**
     * 获取历史消息
     */
    public function getHistoryMessages($sessionKey, $lastMsgId = 0, $limit = 20)
    {
        $query = Db::name('im_message')
            ->where('session_id', $sessionKey)
            ->order('id', 'desc');

        if ($lastMsgId > 0) {
            $query->where('id', '<', $lastMsgId);
        }

        return $query->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取会话列表
     */
    public function getSessions($userId, $page = 1, $limit = 20)
    {
        $sessions = Db::name('im_session')
            ->where(function ($query) use ($userId) {
                $query->where('user1', $userId)
                    ->whereOr('user2', $userId);
            })
            ->where('type', 1)
            ->order('last_time', 'desc')
            ->page($page, $limit)
            ->select();

        // 获取最后一条消息
        foreach ($sessions as &$session) {
            $lastMessage = Db::name('im_message')
                ->where('session_id', $session['session_key'])
                ->order('id', 'desc')
                ->find();

            $session['last_message'] = $lastMessage;

            // 获取对方用户信息
            $otherUserId = $session['user1'] == $userId ? $session['user2'] : $session['user1'];
            $session['other_user'] = $this->getUserInfo($otherUserId);
        }

        return $sessions;
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo($userId)
    {
        $cacheKey = "im:user_info:{$userId}";
        $userInfo = $this->redis->get($cacheKey);

        if ($userInfo === false) {
            $user = Db::name('user')
                ->field('id,username,nickname,avatar,email,mobile,bio')
                ->where('id', $userId)
                ->find();

            if ($user) {
                $userInfo = json_encode($user, JSON_UNESCAPED_UNICODE);
                $this->redis->set($cacheKey, $userInfo, 3600);
            }
        } else {
            $user = json_decode($userInfo, true);
        }

        return $user ?? null;
    }

    /**
     * 获取会话ID
     */
    private function getSessionId($user1, $user2)
    {
        $users = [$user1, $user2];
        sort($users);
        return 'private_' . implode('_', $users);
    }

    /**
     * 获取会话Key
     */
    private function getSessionKey($user1, $user2)
    {
        $users = [$user1, $user2];
        sort($users);
        return implode('_', $users);
    }

    /**
     * 更新会话最后消息
     */
    private function updateSessionMessage($sessionId, $msgId)
    {
        $message = Db::name('im_message')
            ->where('id', $msgId)
            ->find();

        if ($message) {
            Db::name('im_session')
                ->where('session_key', $sessionId)
                ->update([
                    'last_msg' => mb_substr($message['content'], 0, 50),
                    'last_time' => $message['created_at']
                ]);
        }
    }

    /**
     * 搜索消息
     */
    public function searchMessages($userId, $keyword, $page = 1, $limit = 20)
    {
        $sessions = Db::name('im_session')
            ->where(function ($query) use ($userId) {
                $query->where('user1', $userId)
                    ->whereOr('user2', $userId);
            })
            ->column('session_key');

        if (empty($sessions)) {
            return [];
        }

        $messages = Db::name('im_message')
            ->whereIn('session_id', $sessions)
            ->where('content', 'like', "%{$keyword}%")
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();

        return $messages;
    }

    /**
     * 获取未读消息数量
     */
    public function getUnreadCount($userId)
    {
        $sessions = Db::name('im_session')
            ->whereOr([
                ['user1', '=', $userId],
                ['user2', '=', $userId]
            ])
            ->select();

        $totalUnread = 0;
        foreach ($sessions as $session) {
            if ($session['user1'] == $userId) {
                $totalUnread += $session['user1_unread'];
            } else {
                $totalUnread += $session['user2_unread'];
            }
        }

        // 群聊未读
        $groupUnread = Db::name('im_group_message')
            ->where('user_id', $userId)
            ->where('status', 0)
            ->count();

        return $totalUnread + $groupUnread;
    }
}