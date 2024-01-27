<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/19
 */

namespace app\api\controller;


use app\admin\model\hj212\Device;
use app\admin\model\hj212\Pollution;
use app\admin\model\hj212\PollutionCode;
use app\common\controller\Api;
use app\common\library\Utils;
use think\Db;
use think\Exception;
use think\Loader;

/**
 *  上报数据
 * @package app\api\controller
 */
class Data extends Api
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
        $suffixArr = [date("Ym")];
        $f = 0;
//        $where = [];
        $query = "";
        foreach ($suffixArr as $suffix) {
            //需求处理月份 不存在的表
            $isExist = Utils::isTableExist("hj212_data_" . $suffix);
            if (!$isExist) {
                continue;
            }

            if ($f == 0) {
                $query = DB::name("hj212_data_" . $suffix)
                    ->alias('a')
                    ->field('a.id as id,qn,cn,mn,cp_datatime,site_name,is_alarm,a.created_at as created_at')
                    ->join('hj212_device d', 'a.mn=d.device_code', 'left')
                    ->join('hj212_site s', 'd.site_id=s.id', 'left');
                if ($search) {
                    $query = $query->where("s.site_name", "like", "%" . $search . "%");
                }

            } else {
                $query1 = DB::name("hj212_data_" . $suffix)
                    ->alias('a')
                    ->field('"a.id,a.qn,a.cn,a.mn,a.is_alarm,cp_datatime,s.site_name')
                    ->join('hj212_device d', 'a.mn=d.device_code', 'left')
                    ->join('hj212_site s', 'd.site_id=s.id', 'left');
                if ($search) {
                    $query = $query->where("s.site_name", "like", "%" . $search . "%");
                }
                $query = $query->union($query1);
            }
            $f++;
        }
        $list = $query->order("id", 'desc')->paginate($pagesize, false, ['page' => $page]);

//         $page = $this->request->get('page', 1);
//        $pagesize = $this->request->get('pagesize', 10);
//        $list = \app\admin\model\hj212\Data::field("a.id,a.qn,a.cn,a.mn,a.is_alarm,cp_datatime,s.site_name")->alias('a')
//            ->join('hj212_device d', 'a.mn=d.device_code', 'left')
//            ->join('hj212_site s', 'd.site_id=s.id', 'left')
//            ->order('id desc')
//            ->paginate($pagesize, false, ['page' => $page]);
        $this->success("成功", $list);
    }

    /**
     * 监测数据-检测数据查询
     * @param  $id  数据id
     */
    public function detail()
    {
        $id = $this->request->get("id");
        $time = $this->request->get("qn", "");
        try {
            if (!$time || $time == "null") {
                $suffix = date("Ym", time());
            } else {
                $suffix = substr($time, 0, 6);
            }
            $tableName = "hj212_pollution_" . $suffix;
            if (Utils::isTableExist($tableName)) {
                $list = Db::name($tableName)
                    ->alias("p")
                    ->field("p.id as id,c.name as name, p.code as code,avg,min,max,is_alarm")
                    ->join('fa_hj212_pollution_code c', 'p.code=c.code', 'left')
                    ->where("data_id", $id)
                    ->select();
            } else {
                $list = [];
            }
            $this->success('成功', $list);
        } catch (Exception $e) {
            $this->error('失败', "");
        }

    }


    /**
     *数据分析
     * @param  $id  数据id
     * @param  $mn  设备mn
     */
    public function analysis()
    {
        $id = $this->request->get("id");
        $mn = $this->request->get("mn");
        $time = $this->request->param('qn', date("Ym"));
        if (trim($time, " ") == "null") {
            $time = date("Ym");
        }
        $suffix = substr($time, 0, 6);
        $tableName = "hj212_pollution_" . $suffix;
        $res['device'] = Device::field("device_code,site_name,address,lon,lat,industrial_park,s.contact as contact")->alias("p")
            ->where('device_code', $mn)
            ->join('fa_hj212_site s', 'p.site_id=s.id', 'left')
            ->find();

        $res['data'] = Db::name($tableName)->alias("p")
            ->field("p.id as id,c.name as  name, p.code as code,avg,min,max,is_alarm,alarm_min,alarm_max,avg_min as alarm_avg_min ,avg_max as alarm_avg_max,measures,emissions,type ")
            ->join('fa_hj212_pollution_code c', 'p.code=c.code', 'left')
            ->join('fa_hj212_alarm a', 'p.code=a.code', 'left')
            ->where("data_id", $id)
            ->select();
        $this->success('成功', $res);
    }


}