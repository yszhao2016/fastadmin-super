<?php

namespace app\admin\model\fastflow\flow;

use app\admin\model\Admin;
use app\admin\model\AuthGroup;
use think\Config;
use think\Db;
use think\Model;


class Agency extends Model
{
    // 表名
    protected $name = 'fastflow_agency';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'bill_comment',
        'principal',
        'agent',
    ];


    public function getRangeList()
    {
        return ['1' => __('Range 1'), '2' => __('Range 2'), '3' => __('Range 3')];
    }

    public function getScopeList()
    {
        return ['1' => __('Scope 1'), '2' => __('Scope 2')];
    }


    public function getBillCommentAttr($value, $data)
    {
        $tables = Db::query("select TABLE_NAME as name,TABLE_COMMENT as comment from information_schema.tables where table_schema='" . Config::get('database')['database'] . "'");
        $bill_comment = '';
        for ($i = 0; $i < count($tables); $i++) {
            if ($tables[$i]['name'] == $data['bill']) {
                $bill_comment = $tables[$i]['comment'];
                break;
            }
        }
        return $bill_comment;
    }

    public function getPrincipalAttr($value, $data)
    {
        if ($data['scope'] == 1) {
            $admin = (new Admin())->find($data['principal_id']);
            if ($admin) {
                return $admin['nickname'];
            }
        }
        elseif ($data['scope'] == 2) {
            $group = (new AuthGroup())->find($data['principal_id']);
            if ($group) {
                return $group['name'];
            }
        }
    }

    public function getAgentAttr($value, $data)
    {
        if ($data['scope'] == 1) {
            $admin = (new Admin())->find($data['agent_id']);
            if ($admin) {
                return $admin['nickname'];
            }
        }
        elseif ($data['scope'] == 2) {
            $group = (new AuthGroup())->find($data['agent_id']);
            if ($group) {
                return $group['name'];
            }
        }
    }
}
