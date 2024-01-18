<?php

namespace app\admin\controller\fastflow\flow;

use app\common\controller\Backend;
use think\Loader;
use think\Session;
use fastflow\api;

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
    protected $noNeedRight = ['flowDiagram', 'preview', 'detail', 'getSelectpageWorkers', 'getSelectpageFieldsWithComment', 'getSelectpageOperator'];

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
        $this->view->assign(["tableList" => $tableList, 'admin_id' => Session::get('admin')['id']]);
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
        $api = new api();
        $flow_id = input('flow_id');
        $flow = $api->getFlow($flow_id);
        $this->assignconfig(['flow' => $flow]);
        $this->view->assign(['scopeList' => $api->getScope(), 'flow_id' => $flow_id]);
        return $this->view->fetch();
    }

    /**
     * 流程图预览
     */
    public function preview()
    {
        if (input('flow_id')) {
            $api = new api();
            $flow_id = input('flow_id');
            $flow = $api->getFlow($flow_id);
            $this->view->assign(['image' => $flow['image']]);
            return $this->view->fetch();
        }
    }

    /**
     * 流程图
     */
    public function flowDiagram()
    {
        if (input('bill') && input('bill_id')) {
            $api = new api();
            $bill = input('bill');
            $bill_id = input('bill_id');
            $process = $api->getProcessByBill($bill, $bill_id);
            if(!$process){
                $this->error('该单据未发起流程或流程发生错误，请联系管理员','');
            }
            $flow = $api->getFlow($process['flow_id']);
            if (!$flow){
                $this->error('未找到流程，请确认流程是否已被删除','');
            }
            $nodes = $api->getAllNodes($process['flow_id']);
            if (!$nodes){
                $this->error('流程图不能为空，请完善流程设计','');
            }
            $nodes_data = [];
            $condition_data = [];
            foreach ($nodes as $node) {
                $data = $node['data'];
                if ($data['type'] != 'condition') {
                    if ($data['type'] == 'step') {
                        $data['worker'] = $api->getWorkerNameStr(explode(',', $data['worker']), $data['scope']);
                    }
                    else if ($data['type'] == 'start' || $data['type'] == 'end') {
                        $data['sign'] = '';
                        $data['back'] = '';
                        $data['checkmode'] = '';
                        $data['worker'] = '';

                    }
                    $nodes_data [] = $data;
                }
                else {
                    $data['flowoutcondition'] = json_decode($data['flowoutcondition'], true);
                    $data['conditioncount'] = count($data['flowoutcondition']);
                    foreach ($data['flowoutcondition'] as &$row) {
                        if (isset($row['step']) && $row['step'] != '') {
                            $step = $api->getNodeById($flow['id'], $row['step']);
                            if ($step) {
                                $row['step'] = $step['data']['name'];
                            }
                            else {
                                $row['step'] = false;
                            }
                        }
                    }
                    $condition_data[] = $data;
                }
            }
            $this->view->assign(['image' => $flow['image'], 'flow_name'=>$flow['name'], 'nodes' => $nodes_data, 'conditions' => $condition_data]);
            return $this->view->fetch();
        }
    }

    /**
     * 查看详情
     */
    public function detail()
    {
        $api = new api();
        $thread_logs = $api->getThreadLog(input('bill'), input('bill_id'));
        $this->view->assign(['logs' => $thread_logs]);
        $this->assignconfig([
            'bill_id' => input('bill_id'),
            'controller_url' => $this->getBillControllerUrl(input('bill')),
        ]);
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

    private function getBillControllerUrl($bill)
    {
        $flowBillRow = (new \app\admin\model\fastflow\flow\Bill())->where(['bill_table' => $bill])->find();
        $url = Loader::parseName($flowBillRow['controller'], 0);
        $url = str_replace('/_', '/', $url);
        return $url;
    }
}