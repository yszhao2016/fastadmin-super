<?php

namespace app\admin\model\cms;

use think\Model;
use traits\model\SoftDelete;

class Page extends Model
{

    use SoftDelete;

    // 表名
    protected $name = 'cms_page';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'flag_text',
        'status_text',
        'url'
    ];
    protected static $config = [];

    protected static function init()
    {
        self::$config = $config = get_addon_config('cms');
        self::beforeInsert(function ($row) {
            if (!isset($row['admin_id']) || !$row['admin_id']) {
                $admin_id = session('admin.id');
                $row['admin_id'] = $admin_id ? $admin_id : 0;
            }
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
        self::afterInsert(function ($row) {
            $row->save(['weigh' => $row['id']]);
        });
        self::afterDelete(function ($row) {
            $data = Page::withTrashed()->find($row['id']);
            //删除评论
            Comment::deleteByType('page', $row['id'], !$data);
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
        return addon_url('cms/page/index', $vars, static::$config['urlsuffix'], $domain);
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

    public function getFlagList()
    {
        $config = get_addon_config('cms');
        return $config['flagtype'];
    }

    public function getFlagTextAttr($value, $data)
    {
        $value = $value ? $value : $data['flag'];
        $valueArr = explode(',', $value);
        $list = $this->getFlagList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }

    protected function setKeywordsAttr($value)
    {
        return str_replace(["&nbsp;", "\r\n", "\r", "\n"], "", strip_tags($value));
    }

    protected function setDescriptionAttr($value)
    {
        return str_replace(["&nbsp;", "\r\n", "\r", "\n"], "", strip_tags($value));
    }
}
