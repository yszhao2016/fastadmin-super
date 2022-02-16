<?php

namespace app\admin\model\cms;

use app\admin\library\Auth;
use think\Model;

class ChannelAdmin extends Model
{

    // 表名
    protected $name = 'cms_channel_admin';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    // 追加属性
    protected $append = [
    ];

    public static function getAdminChanneIds($admin_id = null)
    {
        $admin_id = $admin_id ? $admin_id : Auth::instance()->id;
        $selected = ChannelAdmin::where('admin_id', $admin_id)->column('channel_id');
        return $selected;
    }

    public function admin()
    {
        return $this->belongsTo('\app\admin\model\Admin', 'admin_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function channel()
    {
        return $this->belongsTo('\app\admin\model\cms\Channel', 'channel_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
