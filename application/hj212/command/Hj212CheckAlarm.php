<?php
/**
 * Created by PhpStorm
 * USER: Zhaoys
 * Date: 2022/2/17 16:30
 */

namespace app\hj212\command;


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
        Log::init(['type' => 'File', 'path' => ROOT_PATH . '/runtime/log/sms/']);
        $device = Device::with('Site')->where('device_code', $sitemn)->find();
        $sms = new UtoooSms('200207', 'RtGZi6');
        $contact = json_decode($device->contact, true);
        $mobile = implode(',', array_column($contact, 'tel'));
        if (!cache($device->site->id)) {
            cache($device->site->id, true, 3600);
            $sms->send($mobile, '【环境系统 报警信息】' . '【站点 ' . $device->site->site_name . '】' . $msg);
            Log::write($mobile . '-----' . $msg);
        }


    }

    protected function execute(Input $input, Output $output)
    {
        $data = Data::where('is_forward', 0)
            ->where('is_alarm', 0)
            ->where('cn', '2061')
            ->select();

        foreach ($data as $item) {
            $pollutionData = Pollution::field('id,code,cou,min,max,avg,flag')
                ->with('Alarm,Info')
                ->where('data_id', $item->id)
                ->select();
            $msg = "";
            foreach ($pollutionData as $val) {

                if ($val->alarm->alarm_max && $val->avg > $val->alarm->alarm_max) {
                    Db::startTrans();
                    try {
                        Pollution::where('id', $val->id)->update(['is_alarm' => 1]);
                        $item->is_alarm = 1;
                        $item->save();
                        $msg .= $val->Info->name . '为' . $val->avg . '已经超出报警值' . "\n";

                    } catch (Exception $e) {
                        Db::rollback();
                        var_dump($e->getMessage());
                    }
                    Db::commit();

                }

            }
            if ($msg) {
                $this->sendMessage($item->mn, $msg);
            }

        }
    }

}