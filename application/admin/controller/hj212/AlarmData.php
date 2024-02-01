<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/2/1
 */

namespace app\admin\controller\hj212;


use app\common\controller\Backend;

class AlarmData extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\hj212\AlarmData;

    }


}