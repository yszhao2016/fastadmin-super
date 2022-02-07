<?php

namespace app\admin\model\flow;

use think\Model;

class Leave extends Model
{
    // 表名
    protected $name = 'flow_leave';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;

    // 追加属性
    protected $append = [

    ];


}
