<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/19
 */

namespace app\api\controller;




use app\common\controller\Api;

class Alarm extends Api
{

    // 无需登录的接口,*表示全部
    protected $noNeedLogin = [""];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ["*"];


    public function list()
    {
        $page = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', 10);
        $search = $this->request->get('search');

//        Alarm::
        $model = \app\admin\model\hj212\Alarm::alias('a')
            ->field("a.id,c.name as name,alarm_min,alarm_max")
            ->join('fa_hj212_pollution_code c',"a.code=c.code","left")
            ->order('a.id desc');
        if ($search) {
            $model = $model->where('name', 'like', "%$search%");
        }
        $list = $model->paginate($pagesize, false, ['page' => $page]);
        $this->success("成功",$list);
    }
}