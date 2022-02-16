<?php

namespace app\admin\model\cms;

use think\Exception;
use think\Model;

class Tag extends Model
{

    // 表名
    protected $name = 'cms_tag';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'url'
    ];
    protected static $config = [];

    protected static function init()
    {
        static::$config = get_addon_config('cms');
        self::beforeUpdate(function ($row) {
            $changedData = $row->getChangedData();
            if (isset($changedData['name'])) {
                $exists = \think\Db::name("cms_tag")->where('name', $changedData['name'])->where('id', '<>', $row['id'])->find();
                if ($exists) {
                    throw new Exception("标签已存在");
                }
            }
        });
        self::afterDelete(function ($row) {
            //删除关联的TAG数据
            Taggable::where('tag_id', $row['id'])->delete();
        });
        self::afterUpdate(function ($row) {
            $originData = $row->getOriginData();
            $changedData = $row->getChangedData();

            if (isset($changedData['name'])) {
                $value = $originData['name'];
                $replacement = $changedData['name'];
                $archivesList = \think\Db::name("cms_archives")->where('id', 'in', function ($query) use ($row) {
                    $query->name("cms_taggable")->where('tag_id', $row['id'])->field('archives_id');
                })->select();
                foreach ($archivesList as $index => $item) {
                    $tagsArr = explode(',', $item['tags']);
                    $tagsArr = array_map(function ($v) use ($value, $replacement) {
                        return $v == $value ? $replacement : $v;
                    }, $tagsArr);
                    \think\Db::name("cms_archives")->where('id', $item['id'])->update(['tags' => implode(',', $tagsArr)]);
                }
            }
        });
    }

    public function getOriginData()
    {
        return $this->origin;
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
        $diyname = isset($data['name']) && $data['name'] ? $data['name'] : $data['id'];
        $time = $data['createtime'] ?? time();

        $vars = [
            ':id'      => $data['id'],
            ':name'    => $diyname,
            ':diyname' => $diyname,
            ':year'    => date("Y", $time),
            ':month'   => date("m", $time),
            ':day'     => date("d", $time)
        ];
        return addon_url('cms/tag/index', $vars, static::$config['urlsuffix'], $domain);
    }
}
