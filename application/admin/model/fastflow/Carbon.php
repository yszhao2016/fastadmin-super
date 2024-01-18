<?php

namespace app\admin\model\fastflow;

use app\admin\model\Admin;
use app\admin\model\fastflow\flow\Bill;
use fastflow\api;
use think\Model;


class Carbon extends Model
{


    // 表名
    protected $name = 'fastflow_carbon';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'is_read_text',
        'bill_name',
        'bill_row_title',
        'sender_name',
    ];


    public function getIsReadList()
    {
        return ['0' => __('Is_Read 0'), '1' => __('Is_Read 1')];
    }


    public function getBillNameAttr($value, $data)
    {
        $bill_row = Bill::where(['bill_table' => $data['bill']])->find();
        if ($bill_row) {
            return $bill_row['bill_name'];
        }
        return $data['bill'];
    }

    public function getBillRowTitleAttr($value, $data)
    {
        $api = new api();
        $bill_row = $api->getBillRow($data['bill'], $data['bill_id']);
        if ($bill_row){
            $name = '';
            if (isset($bill_row['name'])) {
                $name = $bill_row['name'];
            }
            elseif (isset($bill_row['titile'])){
                $name = $bill_row['titile'];
            }
            return $name;
        }
        return '';
    }

    public function getSenderNameAttr($value, $data)
    {
        $admin=Admin::find($data['sender_id']);
        if ($admin){
            return $admin['nickname'];
        }
        else{
            return '';
        }
    }

    public function getIsReadTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_read']) ? $data['is_read'] : '');
        $list = $this->getIsReadList();
        return isset($list[$value]) ? $list[$value] : '';
    }


}
