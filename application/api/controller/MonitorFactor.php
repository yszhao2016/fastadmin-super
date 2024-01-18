<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/18
 */

namespace app\api\controller;


use app\admin\model\hj212\PollutionCode;
use app\common\controller\Api;

class MonitorFactor extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ["*"];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ["*"];


    /**
     * @throws \think\exception\DbException
     * @param  $page
     * @param  $pagesize
     */
    public function  list()
    {

        $page = $this->request->param('page', 1);
        $pagesize = $this->request->param('pagesize', 10);
        $list = PollutionCode::order('id desc')->paginate($pagesize, false, ['page' => $page]);
        $this->success("成功",$list);
    }



}