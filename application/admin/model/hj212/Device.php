<?php

namespace app\admin\model\hj212;

use think\Model;

class Device extends Model
{

    // 表名
    protected $name = 'hj212_device';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    public function Site()
    {
        return $this->hasOne(PollutionSite::class, 'id', 'site_id')->setEagerlyType(0);
    }

}
