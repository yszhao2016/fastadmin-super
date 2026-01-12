<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2026/1/8
 */

namespace App\websocket\model;

use think\Model;

class UserFriend extends Model
{
    protected $name = 'user_friend';

    // 好友状态
    const STATUS_PENDING = 0; // 等待验证
    const STATUS_ACCEPTED = 1; // 已接受
    const STATUS_REJECTED = 2; // 已拒绝
    const STATUS_BLOCKED = 3; // 已拉黑
}