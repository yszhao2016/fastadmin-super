<?php

namespace app\admin\model\hj212;

use think\Model;


class Pollution extends Model
{

    

    

    // 表名
    protected $name = 'hj212_pollution';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    
    public function pollutionCode()
    {
        return $this->hasOne(PollutionCode::class,'codeId','code',[],'LEFT');
    }
    
    public function PollutionAlarm()
    {
        return $this->hasOne(Alarm::class,'codeId','code');
    }
    







}
