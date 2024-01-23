<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/18
 */

namespace app\api\controller;


use app\admin\validate\hj212\Site;
use app\common\controller\Api;
use app\admin\model\hj212\Device;

class MonitorDevice extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = [""];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ["*"];


    public function  list()
    {
        $page = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', 10);
        $search = $this->request->get('search');
//        $model = $model ->join('lzyd_dynamic_like', 'a.id=lzyd_dynamic_like.dynamic_id and lzyd_dynamic_like.user_id='.$userId,'left');

        $model = Device::with('Site');

        if ($search) {
            $model = $model->where('device_code', 'like', "%" . $search . "%");
        }
        $list = $model->order('id desc')->paginate($pagesize, false, ['page' => $page]);


        $this->success("成功", $list);
    }

    /**
     * 设备所属于站点
     * @param $device_id
     * @param $site_id
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function change()
    {

        $device_id = $this->request->post('device_id');
        $site_id = $this->request->post('site_id');
        if (!$device_id || !$site_id) {
            $this->error("缺少必要参数或者参数为空");
        }
        $data = Device::where('site_id',$site_id)->find();
//        if( $data->id !=$device_id ){
//            $this->error("这站点已绑定");
//        }
        Device::update(['site_id' => $site_id], ['id' => $device_id]);
        $this->success("成功");
    }
}