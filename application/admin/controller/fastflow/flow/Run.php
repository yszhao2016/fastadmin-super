<?php

namespace app\admin\controller\fastflow\flow;

use app\common\controller\Backend;
use fastflow\api;


/**
 * 流程运行
 *
 * @icon fa fa-o
 */
class Run extends Backend
{
    protected $model = null;
    protected $noNeedRight = ['start', 'agree', 'back', 'sign', 'getSelectpageWorkers', 'getStartStepNeedSelectWorkerSteps', 'getStartDynamicSteps'];

    public function _initialize()
    {
        parent::_initialize();
    }


    public function start()
    {
        $messageModel=new \app\admin\model\fastflow\flow\Message();
        $api=new api(new CustomMessage($messageModel->getEnabledWays(),$messageModel->getMessageOptions()));
        if ($this->request->isGet()) {
            $bill = input('bill');
            $bill_id = input('bill_id');
            $flows = $api->getUseableFlowByBill($bill) ? $api->getUseableFlowByBill($bill) : [];
            $this->view->assign(['flows' => $flows, '$bill_id', 'bill' => $bill, 'bill_id' => $bill_id]);
            $scope = $api->getDynamicSelectScope();
            $this->assignconfig(['scope' => $scope, 'bill' => $bill, 'bill_id' => $bill_id]);
            return $this->view->fetch();
        }
        if ($this->request->isPost()) {
            return $api->startFlow(input('row/a'));
        }
    }

    public function agree()
    {
        $api=new api(new CustomMessage());
        if ($this->request->isGet()) {
            $thread_id = input('thread_id');
            $process = $api->getProcess($api->getThreadById($thread_id)['process_id']);
            $flow = $api->getFlow($process['flow_id']);
            $have_dynamic = $api->getIfHaveDynamicSteps($flow['id'], $thread_id);
            if ($have_dynamic) {
                $scope = $api->getDynamicSelectScope();
                $dynamic_step_data = $api->getCurrentDynamicSteps($flow['id'], $thread_id);
                $this->view->assign(['scope' => $scope, 'dynamic_step_data' => $dynamic_step_data]);
                $this->assignconfig(['dynamic_step_data' => $dynamic_step_data]);
            }
            $this->view->assign(['thread_id' => $thread_id, 'flow_id' => $flow['id'], 'have_dynamic' => $have_dynamic]);
            return $this->view->fetch();
        }
        if ($this->request->isPost()) {
            return $api->agreeFlow(input('row/a'));
        }
    }


    public function back()
    {
        $api=new api(new CustomMessage());
        if ($this->request->isGet()) {
            $thread_id = input('thread_id');
            $thread = $api->getThreadById($thread_id);
            $process = $api->getProcess($thread['process_id']);
            $flow = $api->getFlow($process['flow_id']);
            $canback_presteps = $api->getCanBackPreStep($flow['id'], $process['id'], $thread['step_id']);
            $this->view->assign(['thread_id' => $thread_id, 'flow_id' => $flow['id'], 'canback_presteps' => $canback_presteps]);
            return $this->view->fetch();
        }
        if ($this->request->isPost()) {
            return $api->backFlow(input('row/a'));
        }
    }


    public function sign()
    {
        $api=new api(new CustomMessage());
        if ($this->request->isGet()) {
            $thread_id = input('thread_id');
            $process = $api->getProcess($api->getThreadById($thread_id)['process_id']);
            $flow = $api->getFlow($process['flow_id']);
            $scope = $api->getSignScope();
            $this->view->assign(['thread_id' => $thread_id, 'flow_id' => $flow['id'], 'scope' => $scope]);
            return $this->view->fetch();
        }
        if ($this->request->isPost()) {
            return $api->signFlow(input('row/a'));
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


}