<?php

namespace app\admin\model\cms;

use think\Config;
use think\Db;
use think\Exception;
use think\Model;

class Diyform extends Model
{

    // 表名
    protected $name = 'cms_diyform';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'url'
    ];
    protected static $config = [];

    public static function init()
    {
        self::$config = $config = get_addon_config('cms');
        self::beforeInsert(function ($row) {
            if (!isset($row['admin_id']) || !$row['admin_id']) {
                $admin_id = session('admin.id');
                $row['admin_id'] = $admin_id ? $admin_id : 0;
            }
            if (!preg_match("/^([a-z0-9_]+)$/", $row['table'])) {
                throw new Exception("表名只支持小写字母、数字、下划线");
            }
        });
        self::afterInsert(function ($row) {
            $prefix = Config::get('database.prefix');
            $sql = "CREATE TABLE `{$prefix}{$row['table']}` (
              `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` int(10) DEFAULT NULL COMMENT '会员ID',
              `title` varchar(255) DEFAULT '' COMMENT '标题',
              `images` varchar(1500) DEFAULT '' COMMENT '图片',
              `content` text DEFAULT NULL COMMENT '内容',
              `createtime` int(10) DEFAULT NULL COMMENT '添加时间',
              `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
              `memo` varchar(1500) DEFAULT '' COMMENT '备注',
              `status` enum('normal','hidden','rejected') DEFAULT 'hidden' COMMENT '状态',
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `createtime` (`createtime`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='{$row['name']}'";
            db()->execute($sql);
            $time = time();
            $fieldsArr = [
                [
                    "source"       => "diyform",
                    "source_id"    => $row['id'],
                    "name"         => "title",
                    "type"         => "string",
                    "title"        => "标题",
                    "content"      => "value1|title1\r\nvalue2|title2",
                    "length"       => 255,
                    "iscontribute" => 1,
                    "isfilter"     => 0,
                    "weigh"        => 3,
                    "defaultvalue" => '',
                    "createtime"   => $time,
                    "updatetime"   => $time,
                    "status"       => "normal"
                ],
                [
                    "source"       => "diyform",
                    "source_id"    => $row['id'],
                    "name"         => "images",
                    "type"         => "images",
                    "title"        => "图片",
                    "content"      => "value1|title1\r\nvalue2|title2",
                    "length"       => 1500,
                    "iscontribute" => 1,
                    "isfilter"     => 0,
                    "weigh"        => 2,
                    "defaultvalue" => '',
                    "createtime"   => $time,
                    "updatetime"   => $time,
                    "status"       => "normal"
                ],
                [
                    "source"       => "diyform",
                    "source_id"    => $row['id'],
                    "name"         => "content",
                    "type"         => "editor",
                    "title"        => "内容",
                    "content"      => "value1|title1\r\nvalue2|title2",
                    "length"       => 0,
                    "iscontribute" => 1,
                    "isfilter"     => 0,
                    "weigh"        => 1,
                    "defaultvalue" => '',
                    "createtime"   => $time,
                    "updatetime"   => $time,
                    "status"       => "normal"
                ],
            ];
            Db::name("cms_fields")->insertAll($fieldsArr);
        });
    }

    public function getUrlAttr($value, $data)
    {
        return $this->buildUrl($value, $data);
    }

    public function getFullurlAttr($value, $data)
    {
        return $this->buildUrl($value, $data, true);
    }

    private function buildUrl($value, $data, $domain = false)
    {
        $diyname = isset($data['diyname']) && $data['diyname'] ? $data['diyname'] : $data['id'];
        $time = $data['createtime'] ?? time();

        $vars = [
            ':id'      => $data['id'],
            ':diyname' => $diyname,
            ':year'    => date("Y", $time),
            ':month'   => date("m", $time),
            ':day'     => date("d", $time)
        ];
        return addon_url('cms/diyform/index', $vars, static::$config['urlsuffix'], $domain);
    }

    public function getPosturlAttr($value, $data)
    {
        $diyname = isset($data['diyname']) && $data['diyname'] ? $data['diyname'] : $data['id'];
        $time = $data['createtime'] ?? time();

        $vars = [
            ':id'      => $data['id'],
            ':diyname' => $diyname,
            ':year'    => date("Y", $time),
            ':month'   => date("m", $time),
            ':day'     => date("d", $time)
        ];
        return addon_url('cms/diyform/post', $vars, static::$config['urlsuffix']);
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
