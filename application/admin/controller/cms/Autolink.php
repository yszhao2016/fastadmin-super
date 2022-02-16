<?php

namespace app\admin\controller\cms;

use app\common\controller\Backend;

/**
 * 自动链接管理
 *
 * @icon fa fa-circle-o
 */
class Autolink extends Backend
{

    /**
     * Autolink模型对象
     * @var \app\admin\model\cms\Autolink
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\cms\Autolink;
        $this->view->assign("targetList", $this->model->getTargetList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    public function import()
    {
        parent::import();
    }

}
