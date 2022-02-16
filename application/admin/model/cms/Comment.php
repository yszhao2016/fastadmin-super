<?php

namespace app\admin\model\cms;

use app\common\model\User;
use think\Db;
use think\Model;
use traits\model\SoftDelete;

class Comment extends Model
{
    use SoftDelete;

    // 表名
    protected $name = 'cms_comment';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'type_text',
        'status_text'
    ];

    protected static function init()
    {
        $config = get_addon_config('cms');
        self::afterWrite(function ($row) use ($config) {
            $changedData = $row->getChangedData();
            if (isset($changedData['status'])) {
                if ($changedData['status'] == 'normal') {
                    Db::name("cms_{$row['type']}")->where('id', $row['aid'])->setInc("comments");
                    User::score($config['score']['postcomment'], $row['user_id'], '发表评论');
                } else {
                    Db::name("cms_{$row['type']}")->where('id', $row['aid'])->where('comments', '>', 0)->setDec("comments");
                    User::score(-$config['score']['postcomment'], $row['user_id'], '删除评论');
                }
            }
        });
        self::afterDelete(function ($row) use ($config) {
            $data = Comment::withTrashed()->where('id', $row['id'])->find();
            if ($data) {
                if ($data['status'] == 'normal') {
                    Db::name("cms_{$row['type']}")->where('id', $row['aid'])->where('comments', '>', 0)->setDec("comments");
                    User::score(-$config['score']['postcomment'], $row['user_id'], '删除评论');
                }
            }
        });
    }

    public function getTypeList()
    {
        return ['archives' => __('Archives'), 'page' => __('Page'), 'special' => __('Special')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }

    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['type'];
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    /**
     * 根据类型和ID删除
     */
    public static function deleteByType($type, $aid, $force = false)
    {
        if (!$force) {
            //删除评论
            $commentList = Comment::where(['type' => $type, 'aid' => $aid])->select();
            foreach ($commentList as $index => $item) {
                $item->delete();
            }
        } else {
            //强制删除评论
            $commentList = Comment::withTrashed()->where(['type' => $type, 'aid' => $aid])->select();
            foreach ($commentList as $index => $item) {
                $item->delete(true);
            }
        }
    }

    public function user()
    {
        return $this->belongsTo('\app\common\model\User', 'user_id', '', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 关联文档模型
     */
    public function archives()
    {
        return $this->belongsTo('Archives', 'aid', '', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 关联单页模型
     */
    public function spage()
    {
        return $this->belongsTo("addons\cms\model\Page", 'aid', '', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 关联专题模型
     */
    public function special()
    {
        return $this->belongsTo("addons\cms\model\Special", 'aid', '', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 关联模型
     */
    public function source()
    {
        $type = $this->getData('type');
        $modelArr = ['page' => 'Page', 'archives' => 'Archives', 'special' => 'Special'];
        $model = isset($modelArr[$type]) ? $modelArr[$type] : $modelArr['archives'];
        return $this->belongsTo($model, "aid");
    }
}
