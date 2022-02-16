<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 首页接口
 */
class Company extends Api
{
    protected $noNeedLogin = [''];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {
        $this->success('请求成功');
    }
}
