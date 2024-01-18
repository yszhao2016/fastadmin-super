<?php

namespace app\admin\model\fastflow\flow;

use app\admin\model\Admin;
use fastflow\api;
use think\Config;
use think\Db;
use think\Model;


class Process extends Model
{


    // 表名
    protected $name = 'fastflow_process';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'flow_name',
        'bill_comment',
        'flow_progress',
        'createusername',
        'bill_status',
        'bill_name',
    ];


    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getFlowNameAttr($value, $data)
    {
        $flow = (new Flow())->find($data['flow_id']);
        if (!$flow) {
            return '-';
        }
        return $flow['name'];
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

    public function getFlowProgressAttr($value, $data)
    {
        return (new api())->getFlowProgress($data['bill'], $data['bill_id']);
    }

    public function getCreateUserNameAttr($value, $data)
    {
        $bill_row = (new api())->getBillRow($data['bill'], $data['bill_id']);
        if (!$bill_row) {
            return '';
        }
        $createUserId = $bill_row['admin_id'];
        $user = Admin::find($createUserId);
        if ($user) {
            return $user['nickname'];
        }
        return '';
    }

    public function getBillStatusAttr($value, $data)
    {
        $bill_row = (new api())->getBillRow($data['bill'], $data['bill_id']);
        if (!$bill_row) {
            return 0;
        }
        return 1;
    }

    public function getBillNameAttr($value, $data)
    {
        return (new api())->getTableComment($data['bill']);
    }

    public function getStatisticData()
    {
        $rows = $this->select();
        $statistic_data = [];
        foreach ($rows as $row) {
            if (!isset($statistic_data[$row['bill']])) {
                $statistic_data[$row['bill']] = [
                    'total' => 0,
                    'run' => 0,
                    'finish' => 0,
                    'termination' => 0
                ];

            }
            $tables = Db::query("select TABLE_NAME as name,TABLE_COMMENT as comment from information_schema.tables where table_schema='" . Config::get('database')['database'] . "'");
            for ($i = 0; $i < count($tables); $i++) {
                if ($tables[$i]['name'] == $row['bill']) {
                    $statistic_data[$row['bill']]['comment'] = $tables[$i]['comment'];
                    break;
                }
            }
            $statistic_data[$row['bill']]['total'] += 1;
            if ($row['status'] == 1) {
                $statistic_data[$row['bill']]['run'] += 1;
            }
            elseif ($row['status'] == 2) {
                $statistic_data[$row['bill']]['finish'] += 1;
            }
            elseif ($row['status'] == 3) {
                $statistic_data[$row['bill']]['termination'] += 1;
            }
        }
        return $statistic_data;
    }

    public function getChartData()
    {
        $statistic_data = $this->getStatisticData();
        $yAxis_data = [];
        $finish_data = [];
        $termination_data = [];
        $run_data = [];
        foreach ($statistic_data as $key => $data) {
            $yAxis_data[] = $data['comment'];
            $finish_data[] = $data['finish'];
            $termination_data[] = $data['termination'];
            $run_data[] = $data['run'];
        }
        return ['yAxis_data' => $yAxis_data, 'finish_data' => $finish_data, 'termination_data' => $termination_data, 'run_data' => $run_data];
    }

    public function getTotalData()
    {
        $rows = $this->select();
        $total = 0;
        $finish = 0;
        $termination = 0;
        $run = 0;
        foreach ($rows as $row) {
            if ($row['status'] == 1) {
                $run += 1;
            }
            elseif ($row['status'] == 2) {
                $finish += 1;
            }
            elseif ($row['status'] == 3) {
                $termination += 1;
            }
            $total += 1;
        }
        return ['total' => $total, 'finish' => $finish, 'termination' => $termination, 'run' => $run];
    }
}
