<?php

namespace app\admin\controller\fastflow\flow;

use app\common\controller\Backend;
use fastflow\api;
use fastflow\fa\BillAuth;
use think\Loader;

/**
 * 流程运行
 *
 * @icon fa fa-o
 */
class Run extends Backend
{
    protected $model = null;
    protected $noNeedRight = [
        'start',
        'agree',
        'back',
        'sign',
        'getSelectpageWorkers',
        'getStartStepNeedSelectWorkerSteps',
        'getStartDynamicSteps',
    ];
    protected $api;
    protected $auth = null;

    public function _initialize()
    {
        $this->api = new api(new CustomMessage());
        $this->view->assign(['carbon_scope' => $this->api->getCarbonScope()]);
        parent::_initialize();
    }


    public function start()
    {
        if ($this->request->isGet()) {
            $bill = input('bill');
            $bill_id = input('bill_id');
            $flows = $this->api->getUseableFlowByBill($bill);
            if (!$flows) {
                $flows = [];
            }
            $this->view->assign(['flows' => $flows, '$bill_id', 'bill' => $bill, 'bill_id' => $bill_id]);
            $scope = $this->api->getDynamicSelectScope();
            $this->assignconfig([
                'scope' => $scope,
                'bill' => $bill,
                'bill_id' => $bill_id,
                'controller_url' => $this->getBillControllerUrl($bill),
                'bill_fields' => $this->getBillFields($bill),
                'can_edit_fields' => $this->getCanEditFields($bill, $bill_id),
            ]);

            return $this->view->fetch();
        }
        if ($this->request->isPost()) {
            return $this->api->startFlow(input('row/a'));
        }
    }

    public function agree()
    {
        if ($this->request->isGet()) {
            $thread_id = input('thread_id');
            $process = $this->api->getProcess($this->api->getThreadById($thread_id)['process_id']);
            $flow = $this->api->getFlow($process['flow_id']);
            $have_dynamic = $this->api->getIfHaveDynamicSteps($flow['id'], $thread_id);
            if ($have_dynamic) {
                $scope = $this->api->getDynamicSelectScope();
                $dynamic_step_data = $this->api->getCurrentDynamicSteps($flow['id'], $thread_id);
                $this->view->assign(['scope' => $scope, 'dynamic_step_data' => $dynamic_step_data]);
                $this->assignconfig(['dynamic_step_data' => $dynamic_step_data]);
            }
            $this->view->assign(['thread_id' => $thread_id, 'flow_id' => $flow['id'], 'have_dynamic' => $have_dynamic]);

            $this->assignconfig([
                'bill_id' => $process['bill_id'],
                'thread_id' => $thread_id,
                'controller_url' => $this->getBillControllerUrl($process['bill']),
                'bill_fields' => $this->getBillFields($process['bill']),
                'can_edit_fields' => $this->getCanEditFields($process['bill'], $process['bill_id'], $thread_id),
            ]);
            return $this->view->fetch();
        }
        if ($this->request->isPost()) {
            return $this->api->agreeFlow(input('row/a'));
        }
    }


    public function back()
    {
        if ($this->request->isGet()) {
            $thread_id = input('thread_id');
            $thread = $this->api->getThreadById($thread_id);
            $process = $this->api->getProcess($thread['process_id']);
            $flow = $this->api->getFlow($process['flow_id']);
            $canback_presteps = $this->api->getCanBackPreStep($flow['id'], $process['id'], $thread['step_id']);
            $this->view->assign(['thread_id' => $thread_id, 'flow_id' => $flow['id'], 'canback_presteps' => $canback_presteps,]);
            $this->assignconfig([
                'bill_id' => $process['bill_id'],
                'thread_id' => $thread_id,
                'controller_url' => $this->getBillControllerUrl($process['bill']),
                'bill_fields' => $this->getBillFields($process['bill']),
                'can_edit_fields' => $this->getCanEditFields($process['bill'], $process['bill_id'], $thread_id),
            ]);
            return $this->view->fetch();
        }
        if ($this->request->isPost()) {
            return $this->api->backFlow(input('row/a'));
        }
    }


    public function sign()
    {
        if ($this->request->isGet()) {
            $thread_id = input('thread_id');
            $process = $this->api->getProcess($this->api->getThreadById($thread_id)['process_id']);
            $flow = $this->api->getFlow($process['flow_id']);
            $scope = $this->api->getSignScope();
            $this->view->assign(['thread_id' => $thread_id, 'flow_id' => $flow['id'], 'scope' => $scope]);
            $this->assignconfig([
                'bill_id' => $process['bill_id'],
                'thread_id' => $thread_id,
                'controller_url' => $this->getBillControllerUrl($process['bill']),
                'bill_fields' => $this->getBillFields($process['bill']),
                'can_edit_fields' => $this->getCanEditFields($process['bill'], $process['bill_id'], $thread_id),
            ]);
            return $this->view->fetch();
        }
        if ($this->request->isPost()) {
            return $this->api->signFlow(input('row/a'));
        }
    }

    public function getSelectpageWorkers()
    {
        return (new api())->getSelectpageWorkers();
    }


    public function getStartDynamicSteps()
    {
        return (new api())->getStartDynamicSteps();
    }

    private function getCanEditFields($bill, $bill_id, $thread_id = null)
    {
        return (new BillAuth())->getCanEditFields($bill, $bill_id, $thread_id);
    }

    private function getBillFields($bill)
    {
        return (new BillAuth())->getBillFields($bill);
    }

    private function getBillControllerUrl($bill)
    {
        $flowBillRow = (new \app\admin\model\fastflow\flow\Bill())->where(['bill_table' => $bill])->find();
        $url = Loader::parseName($flowBillRow['controller'], 0);
        $url = str_replace('/_', '/', $url);
        return $url;
    }

}