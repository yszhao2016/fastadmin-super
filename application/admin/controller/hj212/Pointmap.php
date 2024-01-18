<?php
/**
 * Created by PhpStorm
 * USER: Zhaoys
 * Date: 2022/2/15 10:52
 */

namespace app\admin\controller\hj212;


use app\common\controller\Backend;
use app\admin\model\hj212\Data;
use app\admin\model\hj212\Device;

class Pointmap extends Backend
{
    public function _initialize()
    {
        $this->layout = "";
        parent::_initialize();
        $this->model = new \app\admin\model\hj212\PollutionSite;
    }


    public function index()
    {
        $data = $this->model->all();
        $list = collection($data)->toArray();
        $list = array_map(function ($val) {
            $device = Device::where('site_id', $val['id'])->find();
            $data = [];
            if (isset($device->device_code)&&$device->device_code) {
                $data = Data::where('mn', $device->device_code)
                    ->order('created_at','desc')
                    ->find();
            }
            if ($data) {
                $pollution = \app\admin\model\hj212\Pollution::with('PollutionCode')
                    ->where('data_id', $data->id)->select();
                $pollution = array_map(function ($val) {
                    $val['name'] = $val['PollutionCode']['name'];
                    unset($val['PollutionCode']);
                    return $val;
                }, $pollution);
                $data['pollution'] = $pollution;
            }
            $val['data'] = $data;
            return $val;
        }, $list);
        $this->assignconfig('list', json_encode($list));
        return $this->view->fetch();
    }
}