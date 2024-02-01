<?php

namespace app\admin\model\hj212;

use think\Model;


class AlarmData extends Model
{

    

    

    // 表名
    protected $name = 'hj212_alarm_data';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    protected $append = [
        'cp_datatime_text'
    ];


    public function getCpDatatimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['cp_datatime']) ? $data['cp_datatime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCpDatatimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? date("YmdHis", strtotime($value)) : $value);
    }

    public function getStatusList()
    {
        return ["0" => __('No'), "1" => __('Yes')];
    }

    public function Device()
    {
        return $this->hasOne(Device::class, "device_code", "mn", [], 'LEFT')->setEagerlyType(0);
    }

    public function Pollution()
    {
        return $this->hasMany(Pollution::class, 'data_id', 'id');
    }

    







}
