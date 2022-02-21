<?php
/**
 * Created by PhpStorm
 * USER: Zhaoys
 * Date: 2022/2/17 16:30
 */

namespace app\hj212\command;


use app\hj212\model\Data;
use app\hj212\model\Pollution;
use function EasyWeChat\Kernel\data_get;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Hj212CheckAlarm extends Command
{
    protected function configure()
    {
        $this->setName('hjcheck:start')->setDescription('Start hj212 check!');
    }

    protected function execute(Input $input, Output $output)
    {
        $data = Data::where('is_forward', 0)
            ->select();
        foreach ($data as $item) {
            $pollutionData = Pollution::field('code,cou,min,max,avg,flag')
                ->with('Alarm')
                ->where('data_id', $item->id)
                ->select();
            foreach ($pollutionData as $val) {
                if ($item['alarm']['alarm_max'] && ($val->max > $item->alarm->alarm_max || $val->avg > $item->alarm->alarm_max)) {
                    $val->is_alarm = 1;
                    $val->save();
                    $item->is_alarm = 1;
                    $item->save();
                }
            }
        }
    }

}