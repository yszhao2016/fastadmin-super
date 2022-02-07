<?php

namespace app\admin\model\fastflow\flow;

use app\admin\model\Admin;
use fastflow\api;
use think\Model;

class Flow extends Model
{
    // 表名
    protected $name = 'fastflow_flow';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'createusername',
        'isrun'
    ];


    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }

    public function getIsrunAttr($value, $data)
    {
        $process = (new api())->getProcessByFlowId($data['id']);
        $is_run = 0;
        if ($process){
            foreach ($process as $item) {
                if ($item['status'] == 1) {
                    $is_run = 1;
                }
            }
        }
        return $is_run;
    }

    public function getCreateUserNameAttr($value, $data)
    {
        $createUserId = $data['createuser_id'];
        $user = Admin::find($createUserId);
        if ($user) {
            return $user['nickname'];
        }
        return '';
    }
}
