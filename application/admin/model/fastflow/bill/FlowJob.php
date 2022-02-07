<?php

namespace app\admin\model\fastflow\bill;

use app\admin\model\fastflow\bill\Common;



class FlowJob extends Common
{

    

    

    // 表名
    protected $name = 'flow_job';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    

    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3')];
    }


}
