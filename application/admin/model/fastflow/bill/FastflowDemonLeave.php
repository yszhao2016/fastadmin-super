<?php

namespace app\admin\model\fastflow\bill;

use app\admin\model\fastflow\bill\Common;



class FastflowDemonLeave extends Common
{

    

    

    // 表名
    protected $name = 'fastflow_demon_leave';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    

    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3')];
    }

    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2'), '3' => __('Type 3')];
    }


}
