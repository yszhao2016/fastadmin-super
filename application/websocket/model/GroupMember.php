<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2026/1/8
 */

namespace App\websocket\model;

use think\Model;

class GroupMember extends Model
{
    protected $name = 'im_group_member';

    // 成员状态
    const STATUS_NORMAL = 1; // 正常
    const STATUS_MUTED = 2; // 禁言
    const STATUS_KICKED = 3; // 踢出

    // 成员角色
    const ROLE_OWNER = 1; // 群主
    const ROLE_ADMIN = 2; // 管理员
    const ROLE_MEMBER = 3; // 普通成员
}