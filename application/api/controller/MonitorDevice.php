<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/18
 */

namespace app\api\controller;


use app\common\controller\Api;
use app\admin\model\hj212\Device;

class MonitorDevice extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ["*"];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ["*"];


    public function  list()
    {
        file_put_contents("0118.txt",$request->getContent(),8);
        $page = $this->request->param('page', 1);
        $pagesize = $this->request->param('pagesize', 10);
        $list = Device::with('Site')->order('id desc')->paginate($pagesize, false, ['page' => $page]);
        $this->success("成功",$list);
    }
}