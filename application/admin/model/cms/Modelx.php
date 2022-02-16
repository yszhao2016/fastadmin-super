<?php

namespace app\admin\model\cms;

use think\Config;
use think\Db;
use think\Exception;
use think\Model;

class Modelx extends Model
{

    // 表名
    protected $name = 'cms_model';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
    ];

    public static function init()
    {
        self::beforeInsert(function ($row) {
            $setting = [
                'contributefields' => Fields::getContributeFields(),
                'publishfields'    => Fields::getPublishFields(),
            ];
            $row['setting'] = json_encode($setting);
            if (!preg_match("/^([a-z0-9_]+)$/", $row['table'])) {
                throw new Exception("表名只支持小写字母、数字、下划线");
            }
        });
        self::afterInsert(function ($row) {
            $prefix = Config::get('database.prefix');
            $sql = "CREATE TABLE `{$prefix}{$row['table']}` (`id` int(10) NOT NULL,`content` longtext NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='{$row['name']}'";
            Db::execute($sql);
        });
        //存在栏目无法删除
        self::beforeDelete(function ($row) {
            $exist = Channel::where('model_id', $row['id'])->find();
            if ($exist) {
                throw new Exception("模型下存在栏目，无法进行删除");
            }
        });
        //删除模型后删除对应的表字段
        self::afterDelete(function ($row) {
            Db::name("cms_fields")->where(['source' => 'model', 'source_id' => $row['id']])->delete();
        });
    }

    public function getFieldsAttr($value, $data)
    {
        return is_array($value) ? $value : ($value ? explode(',', $value) : []);
    }

    public function getSettingAttr($value, $data)
    {
        return is_array($value) ? $value : (array)json_decode($data['setting'], true);
    }

    public function setSettingAttr($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }
}
