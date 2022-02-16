<?php

namespace app\admin\model\cms;

use addons\cms\library\Alter;
use addons\cms\library\Service;
use app\common\model\Config;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\Model;

class Fields extends Model
{

    // 表名
    protected $name = 'cms_fields';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'status_text',
        'content_list',
    ];
    protected $type = [
        'setting' => 'json',
    ];
    protected static $listField = ['select', 'selects', 'checkbox', 'radio', 'array', 'selectpage', 'selectpages'];

    public function setError($error)
    {
        $this->error = $error;
    }

    protected static function init()
    {
        $beforeUpdateCallback = function ($row) {
            $changedData = $row->getChangedData();
            if (isset($changedData['name'])) {
                if (!preg_match("/^([a-zA-Z0-9_]+)$/i", $row['name'])) {
                    throw new Exception("字段只支持字母数字下划线");
                }
                if (is_numeric(substr($row['name'], 0, 1))) {
                    throw new Exception("字段不能以数字开始");
                }

                if ($row['source'] == 'model') {
                    $tableFields = \think\Db::name('cms_archives')->getTableFields();
                    if (in_array(strtolower($row['name']), $tableFields)) {
                        throw new Exception("字段已经在主表存在了");
                    }
                    if (in_array($row['name'], ['id', 'content'])) {
                        throw new Exception("字段已经存在");
                    }
                } elseif (in_array($row['source'], ['channel', 'page', 'special', 'block'])) {
                    //栏目、单页、专题、区块需过滤主表字段
                    $tableFields = \think\Db::name('cms_' . $row['source'])->getTableFields();
                    $customFieldList = Service::getCustomFields($row['source'], 0);
                    $tableFields = array_diff($tableFields, array_map(function ($field) {
                        return $field['name'];
                    }, collection($customFieldList)->toArray()));
                    if (in_array(strtolower($row['name']), $tableFields)) {
                        throw new Exception("字段已经在表中存在了");
                    }
                } elseif ($row['source'] == 'diyform') {
                    $tableFields = ['id', 'user_id', 'createtime', 'updatetime', 'memo', 'status'];
                    if (in_array(strtolower($row['name']), $tableFields)) {
                        throw new Exception("字段已经存在");
                    }
                } else {
                    $tableFields = ['id', 'user_id', 'type', 'createtime', 'updatetime'];
                    if (in_array(strtolower($row['name']), $tableFields)) {
                        throw new Exception("字段为保留字段，请使用其它字段");
                    }
                }
                $vars = array_keys(get_class_vars('\think\Model'));
                $vars = array_map('strtolower', $vars);
                $vars = array_merge($vars, ['url', 'fullurl']);
                if (in_array(strtolower($row['name']), $vars)) {
                    throw new Exception("字段为模型保留字段，请使用其它字段");
                }
            }
        };

        $afterInsertCallback = function ($row) {
            //为了避免引起更新的事件回调，这里采用直接执行SQL的写法
            Db::name('cms_fields')->update(['id' => $row['id'], 'weigh' => $row['id']]);
            Fields::refreshTable($row, 'insert');
        };
        $afterUpdateCallback = function ($row) {
            Fields::refreshTable($row, 'update');
        };

        self::beforeInsert($beforeUpdateCallback);
        self::beforeUpdate($beforeUpdateCallback);

        self::afterInsert($afterInsertCallback);
        self::afterUpdate($afterUpdateCallback);

        self::afterDelete(function ($row) {
            Fields::refreshTable($row, 'delete');
        });
    }

    public function getContentListAttr($value, $data)
    {
        return in_array($data['type'], self::$listField) ? Config::decode($data['content']) : $data['content'];
    }

    public static function getContributeFields()
    {
        return ["channel_ids", "image", "images", "tags", "price", "outlink", "content", "keywords", "description"];
    }

    public static function getPublishFields()
    {
        return ["channel_ids", "user_id", "special_ids", "image", "images", "diyname", "tags", "price", "outlink", "content", "seotitle", "keywords", "description"];
    }

    public static function refreshTable($row, $action = 'insert')
    {
        $model = null;
        if (in_array($row['source'], ['model', 'diyform'])) {
            $model = $row['source'] == 'model' ? Modelx::get($row['source_id']) : Diyform::get($row['source_id']);
            if (!$model) {
                throw new Exception("未找到指定模型");
            }
            $table = $model['table'];
        } elseif (in_array($row['source'], ['channel', 'page', 'special', 'block'])) {
            $table = "cms_" . $row['source'];
        } else {
            throw new Exception("未找到指定模型");
        }

        $alter = Alter::instance();
        if (isset($row['oldname']) && $row['oldname'] != $row['name']) {
            $alter->setOldname($row['oldname']);
        }
        $alter
            ->setTable($table)
            ->setName($row['name'])
            ->setLength($row['length'])
            ->setContent($row['content'])
            ->setDecimals($row['decimals'])
            ->setDefaultvalue($row['defaultvalue'])
            ->setComment($row['title'])
            ->setType($row['type']);
        if ($action == 'insert') {
            $sql = $alter->getAddSql();
        } elseif ($action == 'update') {
            $sql = $alter->getModifySql();
        } elseif ($action == 'delete') {
            $sql = $alter->getDropSql();
        } else {
            throw new Exception("操作类型错误");
        }
        //变更模型数据
        if ($model) {
            $fields = Fields::where('source', $row['source'])->where('source_id', $row['source_id'])->field('name')->column('name');
            $model->fields = implode(',', $fields);
            $model->save();
        }
        try {
            db()->execute($sql);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
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
