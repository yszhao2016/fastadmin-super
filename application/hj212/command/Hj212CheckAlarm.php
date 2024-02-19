<?php
/**
 * Created by PhpStorm
 * USER: Zhaoys
 * Date: 2022/2/17 16:30
 */

namespace app\hj212\command;


use app\admin\model\hj212\Alarm;
use app\common\library\UtoooSms;
use app\hj212\model\Data;
use app\admin\model\hj212\Device;
use app\hj212\model\Pollution;
use think\Cache;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Exception;
use think\Log;

class Hj212CheckAlarm extends Command
{
    protected function configure()
    {
        $this->setName('hjcheck:start')->setDescription('Start hj212 check!');
    }

    protected function sendMessage($sitemn, $msg)
    {
//        Log::init(['type' => 'File', 'path' => ROOT_PATH . '/runtime/log/sms/']);
//        $device = Device::with('Site')->where('device_code', $sitemn)->find();
//        $sms = new UtoooSms('200207', 'RtGZi6');
//        $contact = json_decode($device->contact, true);
//        $mobile = implode(',', array_column($contact, 'tel'));
//        if (!cache($device->site->id)) {
//            cache($device->site->id, true, 3600);
//            $sms->send($mobile, '【环境系统 报警信息】' . '【站点 ' . $device->site->site_name . '】' . $msg);
//            Log::write($mobile . '-----' . $msg);
//        }


    }

    protected function execute(Input $input, Output $output)
    {
        $suffix = date("Ym");
        $dataTableName = "hj212_data_" . $suffix;
        $pollutionTableName = "hj212_pollution_" . $suffix;
        $rem = [];
        $isupdate = false;
        try {
            $alarmData = collection(Alarm::all())->toArray();
            foreach ($alarmData as $alarm) {
                $alarmArr[$alarm['site_id']][$alarm['code']] = $alarm;
            }
            $data = Db::name($pollutionTableName)
                ->alias('a')
                ->join('hj212_device', "a.mn=hj212_device.device_code", 'left')
                ->where('is_check', 0)
                ->where('cn', "in", \app\admin\model\hj212\Data::SEARCH_CN)
                ->whereBetween("a.created_at", [time() - 300, time()])
                ->order("a.id", "desc")
                ->select();

            foreach ($data as $item) {
                // 没有因子报警 设置 跳过 is_check 设置为2
                if (!$item['site_id'] ||  !isset($alarmArr[$item['site_id']][$item['code']])) {
                    Db::name($pollutionTableName)->where('id', $item['id'])->update(['is_check' => 2]);
                    continue;
                }
                $alarminfo = $alarmArr[$item['site_id']][$item['code']];
                if (
                    (in_array($item['cn'], ["2051", "2061", "2031"])
                        && ($alarminfo["alarm_min"] > $item['min']
                            || $alarminfo["alarm_max"] < $item['max']
                            || $alarminfo["alarm_max"] < $item['avg']
                        )) || ($item['cn'] == "2011"
                        && $alarminfo["alarm_max"] < $item['rtd'])) {
                    //当是分钟数据的时候 判断最小值 是否小于最小报警值
                    //                   或者最大值是否大于最大报警值
                    //                   或者平均值是否大于最大报警值


                    Db::name($pollutionTableName)->where('id', $item['id'])->update(['is_alarm' => 1]);
                    //这条数据中 已经有因子报警了  data表 is_alarm 就无需更新
                    if (!in_array($item['data_id'], $rem)) {
                        $sdata = Db::name($dataTableName)->where('id', $item['data_id'])->find();

                        if ($sdata['is_alarm']) {
                            continue;
                        }
                        $isupdate = Db::name($dataTableName)->where('id', $item['data_id'])->update(['is_alarm' => 1]);

//                        $pollution = Db::name($pollutionTableName)->where('id', $item['id'])->find();
//                        $data['detail'] = json_encode($pollution);
                        $sdata['data_id'] = $sdata['id'];
                        unset($sdata['id']);
                        unset($sdata['is_forward']);
                        unset($sdata['is_change']);
                        unset($sdata['is_alam']);
                        unset($sdata['is_check']);
                        Db::name("hj212_alarm_data")->insert($sdata);
                    }
                    // 如果跟新data 表成功 就记住id 下次就无需 跟新data表 和alarm_data表
                    if ($isupdate) {
                        $rem[] = $item['data_id'];
                    }
                }
                // 无论是否报警更新  is_check 字段
                Db::name($pollutionTableName)->where('id', $item['id'])->update(['is_check' => 1]);
            }
        } catch (Exception $exception) {
            var_dump($exception->getTraceAsString());
            var_dump($exception->getMessage());
        }
    }


}