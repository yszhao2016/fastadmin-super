<?php

namespace app\admin\controller\fastflow\flow;

use app\common\controller\Backend;
use fastflow\api;

/**
 * 流程监控
 *
 * @icon fa fa-tv
 */
class Process extends Backend
{

    /**
     * Process模型对象
     * @var \app\admin\model\fastflow\flow\Process
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\fastflow\flow\Process;
        $this->view->assign('statisticDataList', $this->model->getStatisticData());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("totalData", $this->model->getTotalData());
        $this->assignconfig('chartData', $this->model->getChartData());
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            return ['code' => 0, 'msg' => '不允许直接对该表进行编辑'];
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            return ['code' => 0, 'msg' => '不允许直接添加'];
        }
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        return ['code' => 0, 'msg' => '不允许删除'];
    }

    /**
     *强制终止
     */
    public function termination($ids = "")
    {
        $process = $this->model->find($ids);
        if (!$process) {
            return ['code' => 0, 'msg' => '参数错误'];
        }
        if ((new api())->terminateProcess($ids)) {
            return ['code' => 1, 'msg' => '操作成功'];
        }
    }


}
