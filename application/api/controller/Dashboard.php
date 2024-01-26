<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/23
 */

namespace app\api\controller;


use app\admin\model\hj212\Device;
use app\admin\model\hj212\Pollution;
use app\common\controller\Api;
use think\Db;

class Dashboard extends Api
{

    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ["*"];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ["*"];


    public function RealTimeData()
    {
        $device_code = $this->request->get("mn");
//        pq.id,pq.avg as rtd,pq.quota_id,pq.is_alarm,pq.qn,q.title,q.unit
        $tableName= "hj212_pollution_".date("Ym");
        $last = Db::name($tableName)->field("qn,cp_datatime")
            ->where("mn", $device_code)
            ->where("cn", "2051")
            ->order("id desc")
            ->find();
        $list = Db::name($tableName)->field("avg as rtd,c.name as title,is_alarm,cp_datatime")
            ->alias('p')
            ->join('hj212_pollution_code c', "p.code=c.code", "left")
            ->where("mn", $device_code)
            ->where("cn", "2051")
            ->where("qn", $last['qn'])
            ->select();
        $this->success("成功", [
            "list" => $list,
            'time' => date('Y-m-d H:i:s', $last['cp_datatime'])
        ]);
    }


    public function devList()
    {

        $list = Device::field("device_code,site_name,type")
            ->alias("p")
            ->join('hj212_site s', "s.id=p.site_id", 'left')
            ->select();
        $this->success("成功", $list);
    }

    public function chartData()
    {
        $start = $this->request->get('start', date("Y-m-d"));
        $end = $this->request->get("end", date("Y-m-d"));
        $mn = $this->request->get("mn", "81733553216103");
        $suffix= date("Ym",strtotime($end));
        $sql = 'SELECT p.code,c.name,DATE_FORMAT( FROM_UNIXTIME( `cp_datatime` ), "%Y-%m-%d %H:%i") AS time,avg FROM
	            fa_hj212_pollution_'.$suffix.'  p left join fa_hj212_pollution_code c on p.code=c.code where mn = "' . $mn . '"
                AND cn = "2061"
                AND cp_datatime BETWEEN ' . strtotime($start . " 00:00:00") . ' and ' . strtotime($end . " 23:59:59");
        $data = Db::query($sql);
        if(!$data){
            $this->error("没有数据");
        }
        $iscztime = array();
        foreach ($data as $item) {
            isset($iscztime[$item['code']])?"":$iscztime[$item['code']]=[];
            if (in_array($item['time'], $iscztime[$item['code']])) {
                continue;
            }
            $iscztime[$item['code']][] = $item['time'];
            $datat[$item['code']][] = $item;
        }
        unset($iscztime);

        foreach ($datat as $nitem) {
            $data = array();
            $x_asse = array();
            foreach ($nitem as $t) {
                $data[] = $t['avg'];
                $x_asse[] = $t['time'];
            }
            $linetemp['name'] = $t['name'];
            $linetemp['type'] = 'line';
            $linetemp['smooth'] = true;
            $linetemp['data'] = $data;
            $linetemp['x_asse'] = $x_asse;
            $temp[] = $linetemp;
        }
        $x = $x_asse;
        $this->success('success', ['main' => $temp, 'list' => [$temp], 'x' => $x]);

    }


    private function getTime()
    {

    }

    // 水   w01018 化学需氧量(COD)   w21003 氨氮   w21011 总磷  w21001  总氮    w01001 pH值  流量

    //气体
    // a01014 烟气湿度
    // a00000 废气流量
    // a19001 O2
    // a01011 烟气流速
    // a01012 烟气温度
    // a01013 烟气压力
    // a01017 烟气动压
    // a01017 烟尘：颗粒物
    // a21026 SO2
    // a21002 NOX
    // a05002 甲烷
    // a34013 烟尘折算
    // a24088 非甲烷总经


}