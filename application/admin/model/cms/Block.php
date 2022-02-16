<?php

namespace app\admin\model\cms;

use think\Model;

class Block extends Model
{

    // 表名
    protected $name = 'cms_block';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'status_text'
    ];

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $row->save(['weigh' => $row['id']]);
        });
        self::beforeWrite(function ($row) {
            //在更新之前对数组进行处理
            foreach ($row->getData() as $k => $value) {
                if (is_array($value) && is_array(reset($value))) {
                    $value = json_encode(self::getArrayData($value), JSON_UNESCAPED_UNICODE);
                } else {
                    $value = is_array($value) ? implode(',', $value) : $value;
                }
                $row->$k = $value;
            }
        });
    }

    public function getBegintimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['begintime'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setBegintimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : ($value ? $value : null);
    }

    public function getEndtimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['endtime'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setEndtimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : ($value ? $value : null);
    }

    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }
}
