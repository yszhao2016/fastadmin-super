<?php

namespace app\index\controller;

use app\common\controller\Frontend;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function index()
    {
        Header("Location:dicMxYKUfh.php/index/login");
//        return $this->view->fetch();
    }

}
