<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;

/**
 * 首页接口
 */
class Index extends Api
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

    public function info()
    {
        $is_need_read = 0;
        $data = Db::name("hj212_alarm_data")
            ->where("is_read", 0)
            ->find();
        if ($data) {
            $is_need_read = 1;
        }
        $res['is_need_read'] = $is_need_read;
        $this->success("成功", $res);
    }
}
