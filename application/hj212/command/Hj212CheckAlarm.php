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

        try {
            $alarmData = collection(Alarm::all())->toArray();
            foreach ($alarmData as $alarm) {
                $alarmArr[$alarm['code']] = $alarm;
            }
            $data = Db::name($pollutionTableName)
                ->where('is_check', 0)
                ->where('cn', "in", \app\admin\model\hj212\Data::SEARCH_CN)
                ->order('id', "desc")
                ->select();

            foreach ($data as $item) {
                if (!isset($alarmArr[$item['code']])) {
                    continue;
                }

                if (
                    (in_array($item['cn'], ["2051", "2061", "2031"])
                        && ($alarmArr[$item['code']]["alarm_min"] > $item['min']
                            || $alarmArr[$item['code']]["alarm_max"] < $item['max']
                            || $alarmArr[$item['code']]["alarm_max"] < $item['avg']
                        )) || ($item['cn'] == "2011"
                        && $alarmArr[$item['code']]["alarm_max"] < $item['rtd'])) {
                    //当是分钟数据的时候 判断最小值 是否小于最小报警值
                    //                   或者最大值是否大于最大报警值
                    //                   或者平均值是否大于最大报警值


                   $test=  Db::name($pollutionTableName)->where('id', $item['id'])->update(['is_alarm' => 1]);
                    Db::name($dataTableName)->where('id', $item['data_id'])->update(['is_alarm' => 1]);


                    $data = Db::name($dataTableName)->where('id', $item['data_id'])->find();
                    $pollution = Db::name($pollutionTableName)->where('id', $item['id'])->find();
                    $data['detail'] = json_encode($pollution);
                    unset($data['id']);
                    unset($data['is_forward']);
                    unset($data['is_change']);
                    unset($data['is_alarm']);
                    unset($data['is_check']);
                    Db::name("hj212_alarm_data")->insert($data);
                }
                Db::name($pollutionTableName)->where('id', $item['id'])->update(['is_check' => 1]);
            }
        } catch (Exception $exception) {
            var_dump($exception->getMessage());
        }
    }


}