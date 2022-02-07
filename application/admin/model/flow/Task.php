<?php

namespace app\admin\model\flow;

use think\Model;

class Task extends Model
{
    // 表名
    protected $name = 'flow_task';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;

    // 追加属性
    protected $append = [

    ];


    public function instance()
    {
        return $this->belongsTo('Instance', 'instanceid', '', [], 'LEFT')->setEagerlyType(0);
    }

    public function receive()
    {
        return $this->belongsTo('Admin', 'receiveid', '', [], 'LEFT')->setEagerlyType(0);
    }

    public function originator()
    {
        return $this->belongsTo('Instance', 'instanceid', '', [], 'LEFT')->setEagerlyType(0);
    }

}
