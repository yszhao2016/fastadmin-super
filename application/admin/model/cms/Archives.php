<?php

namespace app\admin\model\cms;

use addons\cms\library\FulltextSearch;
use addons\cms\library\Service;
use app\common\model\User;
use think\Model;
use traits\model\SoftDelete;

class Archives extends Model
{
    use SoftDelete;

    // 表名
    protected $name = 'cms_archives';
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
        'publishtime_text',
        'url',
        'fullurl',
        'style_bold',
        'style_color',
    ];
    protected static $config = [];


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
        $catename = isset($this->channel) && $this->channel ? $this->channel->diyname : 'all';
        $cateid = isset($this->channel) && $this->channel ? $this->channel->id : 0;
        $time = $data['publishtime'] ?? time();
        $vars = [
            ':id'       => $data['id'],
            ':diyname'  => $diyname,
            ':channel'  => $data['channel_id'],
            ':catename' => $catename,
            ':cateid'   => $cateid,
            ':year'     => date("Y", $time),
            ':month'    => date("m", $time),
            ':day'      => date("d", $time),
        ];
        return addon_url('cms/archives/index', $vars, static::$config['urlsuffix'], true);
    }

    public function getOriginData()
    {
        return $this->origin;
    }

    /**
     * 批量设置数据
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    protected static function init()
    {
        self::$config = $config = get_addon_config('cms');
        self::beforeInsert(function ($row) {
            if (!isset($row['admin_id']) || !$row['admin_id']) {
                $admin_id = session('admin.id');
                $row['admin_id'] = $admin_id ? $admin_id : 0;
            }
        });
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $channel = Channel::get($row['channel_id']);
            $row->getQuery()->where($pk, $row[$pk])->update(['model_id' => $channel ? $channel['model_id'] : 0]);
            Channel::where('id', $row['channel_id'])->setInc('items');
        });
        self::beforeWrite(function ($row) {
            $changedData = $row->getChangedData();
            if (isset($changedData['flag'])) {
                $row['weigh'] = is_array($changedData['flag']) && in_array('top', $changedData['flag'])
                    ? ($row['weigh'] == 0 ? 9999 : $row['weigh'])
                    : ($row['weigh'] == 9999 ? 0 : $row['weigh']);
            }

            if (isset($row['content']) && !self::$config['realtimereplacelink']) {
                $row['content'] = Service::autolinks($row['content']);
            }
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
        self::afterWrite(function ($row) use ($config) {
            if (isset($row['channel_id'])) {
                //在更新成功后刷新副表、TAGS表数据、栏目表
                $channel = Channel::get($row->channel_id);
                if ($channel) {
                    $model = Modelx::get($channel['model_id']);
                    if ($model) {
                        $values = array_intersect_key($row->getData(), array_flip($model->fields));
                        $values['id'] = $row['id'];
                        if (isset($row['content'])) {
                            $values['content'] = $row['content'];
                        }
                        //更新副表
                        $addonTbl = \think\Db::name($model['table']);
                        if ($addonTbl->find($row['id'])) {
                            $addonTbl->update($values);
                        } else {
                            $addonTbl->insert($values, true);
                        }
                    }
                }
            }
            if (isset($row['tags'])) {
                \addons\cms\model\Tag::refresh($row['tags'], $row['id']);
            }
            $changedData = $row->getChangedData();
            if (isset($changedData['status']) && $changedData['status'] == 'normal') {
                //增加积分
                User::score($config['score']['postarchives'], $row['user_id'], '发布文章');
                //推送到熊掌号和百度站长
                if ($config['baidupush']) {
                    $urls = [$row->fullurl];
                    \think\Hook::listen("baidupush", $urls);
                }
            }
            if ($config['searchtype'] == 'xunsearch') {
                //更新全文搜索
                FulltextSearch::update($row->id);
            }
        });
        self::afterDelete(function ($row) use ($config) {
            $data = Archives::withTrashed()->find($row['id']);
            if ($data) {
                if ($row['status'] == 'normal') {
                    User::score(-$config['score']['postarchives'], $row['user_id'], '删除文章');
                }
                if ($config['searchtype'] == 'xunsearch') {
                    FulltextSearch::del($row);
                }
            } else {
                //删除相关TAG
                \addons\cms\model\Tag::refresh('', $row['id']);
            }
            //删除评论
            Comment::deleteByType('archives', $row['id'], !$data);
        });
    }

    public function getFlagList()
    {
        $config = get_addon_config('cms');
        return $config['flagtype'];
    }

    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden'), 'rejected' => __('Status rejected'), 'pulloff' => __('Status pulloff')];
    }

    public function getStyleBoldAttr($value, $data)
    {
        return in_array('b', explode('|', $data['style']));
    }

    public function getStyleColorAttr($value, $data)
    {
        $result = preg_match("/(#([0-9a-z]{6}))/i", $data['style'], $matches);
        return $result ? $matches[1] : '';
    }

    public function getFlagTextAttr($value, $data)
    {
        $value = $value ? $value : $data['flag'];
        $valueArr = $value ? explode(',', $value) : [];
        $list = $this->getFlagList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getPublishtimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['publishtime'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPublishtimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : ($value ? $value : null);
    }

    protected function setCreatetimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : ($value ? $value : null);
    }

    protected function setKeywordsAttr($value)
    {
        return str_replace(["&nbsp;", "\r\n", "\r", "\n"], "", strip_tags($value));
    }

    protected function setDescriptionAttr($value)
    {
        return str_replace(["&nbsp;", "\r\n", "\r", "\n"], "", strip_tags($value));
    }

    public static function getArrayData($data)
    {
        if (!isset($data['value'])) {
            $result = [];
            foreach ($data as $index => $datum) {
                $result['field'][$index] = $datum['key'];
                $result['value'][$index] = $datum['value'];
            }
            $data = $result;
        }
        $fieldarr = $valuearr = [];
        $field = isset($data['field']) ? $data['field'] : (isset($data['key']) ? $data['key'] : []);
        $value = isset($data['value']) ? $data['value'] : [];
        foreach ($field as $m => $n) {
            if ($n != '') {
                $fieldarr[] = $field[$m];
                $valuearr[] = $value[$m];
            }
        }
        return $fieldarr ? array_combine($fieldarr, $valuearr) : [];
    }

    public function channel()
    {
        return $this->belongsTo('Channel', 'channel_id', '', [], 'LEFT')->setEagerlyType(0);
    }

    public function special()
    {
        return $this->belongsTo('Special', 'special_id', '', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 关联模型
     */
    public function model()
    {
        return $this->belongsTo("Modelx", 'model_id')->setEagerlyType(1);
    }

    /**
     * 关联模型
     */
    public function user()
    {
        return $this->belongsTo("\\app\\common\\model\\User", 'user_id', 'id', [], 'LEFT')->setEagerlyType(1);
    }

    /**
     * 关联模型
     */
    public function admin()
    {
        return $this->belongsTo("\\app\\admin\\model\\Admin", 'admin_id', 'id', [], 'LEFT')->setEagerlyType(1);
    }
}
