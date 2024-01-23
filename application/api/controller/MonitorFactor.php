<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/18
 */

namespace app\api\controller;


use app\admin\model\hj212\PollutionCode;
use app\common\controller\Api;

/**
 * 检查因子
 * Class MonitorFactor
 * @package app\api\controller
 */
class MonitorFactor extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = [""];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ["*"];


    /**
     * @param  $page
     * @param  $pagesize
     * @throws \think\exception\DbException
     */
    public function  list()
    {

        $page = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', 10);
        $search = $this->request->get('search');
        $model = PollutionCode::order('id desc');
        if ($search) {
            $model = $model->where('name', 'like', "%$search%");
        }
        $list = $model->paginate($pagesize, false, ['page' => $page]);
        $this->success("成功", $list);
    }


}