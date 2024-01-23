<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/19
 */

namespace app\admin\model\hj212;


use think\Model;

class OriginalData extends Model
{
    // 表名
    protected $name = 'hj212_original_data';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $deleteTime = false;
}