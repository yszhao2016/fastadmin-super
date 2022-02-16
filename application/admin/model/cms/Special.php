<?php

namespace app\admin\model\cms;

use addons\cms\library\Service;
use think\Model;
use traits\model\SoftDelete;


class Special extends Model
{
    use SoftDelete;

    // 表名
    protected $name = 'cms_special';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'url',
        'fullurl',
        'flag_text',
        'status_text'
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

            if (isset($row['tag_ids'])) {
                $tagIds = explode(',', $row['tag_ids']);
                if ($tagIds) {
                    foreach ($tagIds as $index => &$tagId) {
                        $tag = Tag::get($tagId);
                        if (!$tag && !is_numeric($tagId)) {
                            $data = [
                                'name' => $tagId,
                            ];
                            $tag = Tag::create($data);
                            $tagId = $tag->id;
                        }
                    }
                    $row->tag_ids = implode(',', $tagIds);
                }
            }
        });
        self::afterWrite(function ($row) use ($config) {
            $changedData = $row->getChangedData();
            if (isset($changedData['status']) && $changedData['status'] == 'normal') {
                //推送到熊掌号+百度站长
                if ($config['baidupush']) {
                    $urls = [$row->fullurl];
                    \think\Hook::listen("baidupush", $urls);
                }
            }

            $oldArchivesIds = self::getArchivesIds($row['id']);
            if (isset($row['archives_ids'])) {
                $newArchivesIds = explode(",", $row['archives_ids']);
                $remainIds = array_diff($newArchivesIds, $oldArchivesIds);
                $removeIds = array_diff($oldArchivesIds, $newArchivesIds);

                $archivesList = \addons\cms\model\Archives::where('id', 'in', array_merge($remainIds, $removeIds))->select();
                foreach ($archivesList as $index => $item) {
                    $ids = explode(',', $item['special_ids']);
                    if (in_array($item['id'], $remainIds)) {
                        $ids[] = $row['id'];
                    }
                    if (in_array($item['id'], $removeIds)) {
                        $ids = array_diff($ids, [$row['id']]);
                    }
                    $item->save(['special_ids' => implode(',', array_unique(array_filter($ids)))]);
                }
            }
        });

        self::afterDelete(function ($row) {
            $data = Special::withTrashed()->find($row['id']);
            //删除评论
            Comment::deleteByType('special', $row['id'], !$data);
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
        return addon_url('cms/special/index', $vars, static::$config['urlsuffix'], $domain);
    }

    public function getFlagList()
    {
        $config = get_addon_config('cms');
        return $config['flagtype'];
    }

    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }


    public function getFlagTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['flag']) ? $data['flag'] : '');
        $valueArr = explode(',', $value);
        $list = $this->getFlagList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setFlagAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    protected function setKeywordsAttr($value)
    {
        return str_replace(["&nbsp;", "\r\n", "\r", "\n"], "", strip_tags($value));
    }

    protected function setDescriptionAttr($value)
    {
        return str_replace(["&nbsp;", "\r\n", "\r", "\n"], "", strip_tags($value));
    }

    /**
     * 获取专题文档集合
     */
    public static function getArchivesIds($special_id)
    {
        $ids = Archives::whereRaw("FIND_IN_SET('{$special_id}', `special_ids`)")->column('id');
        return $ids;
    }

    /**
     * 获取专题的文档ID集合
     */
    public function getArchivesIdsAttr($value, $data)
    {
        if (isset($data['archives_ids'])) {
            return $data['archives_ids'];
        }
        $ids = Special::getArchivesIds($data['id']);
        return implode(',', $ids);
    }


}
