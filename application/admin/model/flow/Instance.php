<?php

namespace app\admin\model\flow;

use think\Model;

class Instance extends Model
{
    // 表名
    protected $name = 'flow_instance';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;

    // 追加属性
    protected $append = [

    ];

    protected static function init()
    {
        self::afterDelete(function ($row) {
            if (isset($row['id'])) {
                Task::where('instanceid', $row['id'])->delete();
            }
        });
    }
}
