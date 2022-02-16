<?php

namespace app\admin\model\cms;

use think\Model;

class Taggable extends Model
{

    // 表名
    protected $name = 'cms_taggable';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;
    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    // 追加属性
    protected $append = [
    ];
    protected static $config = [];

    protected static function init()
    {
        static::$config = get_addon_config('cms');
    }
}
