<?php

namespace app\admin\controller\fastflow\flow;

use app\common\controller\Backend;
use think\Session;
use fastflow\api;
use fastflow\lib\lib;

/**
 * 流程管理
 *
 * @icon fa fa-list-alt
 */
class Flow extends Backend
{
    /**
     * @var \app\admin\model\Flow
     */
    protected $model = null;
    protected $noNeedRight = ['viewer', 'detail', 'getSelectpageWorkers', 'getSelectpageFieldsWithComment', 'getSelectpageOperator'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\fastflow\flow\Flow;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    public function index()
    {
        return parent::index();
    }

    /**
     * 添加
     */
    public function add()
    {
        $tableList = (new \app\admin\model\fastflow\flow\Bill())->select();
        $this->view->assign(["tableList" => $tableList, 'uid' => Session::get('admin')['id']]);
        return parent::add();
    }

    /*
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->model->getIsrunAttr(null, $row)) {
            return $this->view->fetch('cannotedit');
        }
        $tableList = (new \app\admin\model\fastflow\flow\Bill())->select();
        $this->view->assign(["tableList" => $tableList]);
        return parent::edit($ids);
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = explode(',', $ids);
        $pk = $this->model->getPk();
        $rows = $this->model->where($pk, 'in', $ids)->select();
        $is_run = false;
        $no_run_ids = [];
        foreach ($rows as $row) {
            if ($this->model->getIsrunAttr(null, $row)) {
                $is_run = true;
            }
            else {
                $no_run_ids[] = $row[$pk];
            }
        }
        if ($is_run && count($ids) == 1) {
            return ['code' => 0, 'msg' => '正在运行的流程无法删除'];
        }
        return parent::del(implode(',', $no_run_ids));
    }

    /**
     * 流程设计
     */
    public function designer()
    {
        $api=new api();
        $flow_id = input('flow_id');
        $flow = $api->getFlow($flow_id);
        $this->assignconfig(['flow' => $flow]);
        $this->view->assign(['scopeList' => $api->getScope(), 'flow_id' => $flow_id]);
        return $this->view->fetch();
    }

    /**
     * 流程图预览
     */
    public function viewer()
    {
        $api=new api();
        if (input('flow_id')) {
            $flow_id = input('flow_id');
            $flow = $api->getFlow($flow_id);
        }
        elseif (input('bill') && input('bill_id')) {
            $bill = input('bill');
            $bill_id = input('bill_id');
            $process = $api->getProcessByBill($bill, $bill_id);
            $flow = $api->getFlow($process['flow_id']);
        }
        $this->view->assign(['image' => $flow['image']]);
        return $this->view->fetch();
    }

    /**
     * 查看详情
     */
    public function detail()
    {
        $thread_logs = (new lib())->getThreadLog(input('bill'), input('bill_id'));
        $this->view->assign(['logs' => $thread_logs]);
        return $this->view->fetch();
    }

    public function getSelectpageWorkers()
    {
        return (new api())->getSelectpageWorkers();
    }


    public function getSelectpageFieldsWithComment()
    {
        return (new api())->getSelectpageFieldsWithComment();
    }

    public function getSelectpageOperator()
    {
        return (new api())->getSelectpageOperator();
    }

    /**
     * 保存流程图
     */
    public function saveGraph()
    {
        return (new api())->saveGraph();
    }
}