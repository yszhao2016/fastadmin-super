<?php

namespace app\admin\model\fastflow\flow;

use think\Model;


class Message extends Model
{


    // 表名
    protected $name = 'fastflow_message';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];


    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getMessageOptions()
    {
        $message_options = [];
        $message_rows = (new Message())->where('status', 1)->select();
        foreach ($message_rows as $message_row) {
            $config_arr = json_decode($message_row['config'], true);
            $config_tmp = [];
            foreach ($config_arr as $config) {
                $config_tmp[$config['key']] = $config['value'];
            }
            $message_row['config'] = $config_tmp;
            $message_options[$message_row['key']] = $message_row;
        }
        return $message_options;
    }

    public function getEnabledWays()
    {
        $enabled_ways = [];
        $message_rows = (new Message())->where('status', 1)->select();
        foreach ($message_rows as $message_row) {
            $enabled_ways[] = $message_row['key'];
        }
        return $enabled_ways;
    }

}
