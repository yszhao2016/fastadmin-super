<?php
/**
 * Created by PhpStorm
 * USER: Zhaoys
 * Date: 2022/2/15 10:52
 */

namespace app\admin\controller\hj212;


use app\common\controller\Backend;

class Pointmap extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\hj212\PollutionSite;
    }


    public function index()
    {
        $data = $this->model->all();
        $list = collection($data)->toArray();
        $this->view->assign('list', json_encode($list));
        return $this->view->fetch();
    }
}