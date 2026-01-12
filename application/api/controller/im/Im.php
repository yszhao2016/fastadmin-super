<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2026/1/8
 */

namespace App\api\controller\im;

use app\common\controller\Api;
use think\Cache;

class Im extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 获取连接Token
     */
    public function getToken()
    {
        $user = $this->auth->getUser();
        $token = md5($user->id . time() . rand(1000, 9999));

        // 存储Token，有效期5分钟
        Cache::set('ws_token:' . $token, $user->id, 300);

        $this->success('success', [
            'token' => $token,
            'ws_url' => 'ws://' . request()->host() . ':9501',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'avatar' => $user->avatar
            ]
        ]);
    }

    /**
     * 获取会话列表
     */
    public function getSessions()
    {
        $user = $this->auth->getUser();
        $sessions = \app\common\model\ImSession::where('user1', $user->id)
            ->whereOr('user2', $user->id)
            ->order('last_time', 'desc')
            ->select();

        $this->success('success', $sessions);
    }

    /**
     * 获取历史消息
     */
    public function getHistory()
    {
        $sessionId = $this->request->param('session_id');
        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 20);

        $messages = \app\common\model\ImMessage::where('session_id', $sessionId)
            ->order('id', 'desc')
            ->paginate($limit);

        $this->success('success', $messages);
    }
}