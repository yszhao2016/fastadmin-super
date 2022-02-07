<?php

namespace app\admin\model\flow;

use think\Model;

class Scheme extends Model
{
    // 表名
    protected $name = 'flow_scheme';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'datetime';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 追加属性
    protected $append = [

    ];

    protected static function init()
    {
        self::afterDelete(function ($row) {
            if (isset($row['id'])) {
                Bizscheme::where('scheme_id', $row['id'])->delete();
            }
        });
    }
}
