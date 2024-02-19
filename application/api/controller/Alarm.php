<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/19
 */

namespace app\api\controller;




use app\common\controller\Api;
use think\Db;

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
            ->field("a.id,c.name as name,alarm_min,alarm_max,site_name")
            ->join('fa_hj212_pollution_code c',"a.code=c.code","left")
            ->join('fa_hj212_site s','a.site_id=s.id','left')
            ->order('a.id desc');
        if ($search) {
            $model = $model->where('name', 'like', "%$search%");
        }
        $list = $model->paginate($pagesize, false, ['page' => $page]);
        $this->success("成功",$list);
    }


    public function data()
    {
        $page = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', 10);
        $search = $this->request->get('search');
        $query = DB::name("hj212_alarm_data" )
        ->alias('a')
        ->field('a.id as alarm_data_id,qn,cn,mn,cp_datatime,site_name,is_read,a.created_at as created_at,data_id')
        ->join('hj212_device d', 'a.mn=d.device_code', 'left')
        ->join('hj212_site s', 'd.site_id=s.id', 'left');
        if ($search) {
            $query = $query->where("s.site_name", "like", "%" . $search . "%");
        }
        $list = $query->order("cp_datatime", 'desc')->paginate($pagesize, false, ['page' => $page]);
        $this->success("成功", $list);
    }


    public function read(){
        $id = $this->request->post('alarm_data_id');
        DB::name("hj212_alarm_data" )->where('id',$id)->update(["is_read"=>1]);
        $this->success("成功", "");
    }
}