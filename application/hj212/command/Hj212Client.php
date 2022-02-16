<?php
/**
 * Created by PhpStorm
 * USER: Zhaoys
 * Date: 2022/2/11 14:25
 */

namespace app\hj212\command;


use app\hj212\model\Data;
use app\hj212\model\Pollution;
use app\hj212\segment\converter\DataReverseConverter;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use Swoole\Client as swooleClient;
use Swoole\Coroutine as Co;
use think\Exception;

class Hj212Client extends Command
{
    protected function configure()
    {
        $this->setName('hjclient:start')->setDescription('Start hj212 Client!');
    }

    protected function execute(Input $input, Output $output)
    {
        do {
            $data = Data::field('id,source_data,qn,st,cn,pw,mn,flag,pnum,pno,cp_datatime,is_forward,is_change')
                ->where('is_forward', 0)->select();
            if ($data) {
                try {
                    $client = new swooleClient(SWOOLE_SOCK_TCP);
                    if (!$client->connect('192.168.100.230', 9503, 1)) {
                        echo "connect failed. Error: {$client->errCode}\n";
                    }
                    foreach ($data as $model) {
                        if ($model->is_change) {
                            $sendData = $this->dealChangeData($model);
                            $client->send($sendData);
                        } else {
                            $client->send($model->source_data);
                        }
                        if ($client->recv()) {
                            $model->is_forward = 1;
                            $res = $model->save();
                        }
                    }
                } catch (Exception $e) {
                    var_dump($e->getMessage());
                }
                $client->close();
            }
            sleep(30);
        } while (true);
    }


    protected function dealChangeData($model)
    {
        $pollutionData = Pollution::field('code,cou,min,max,avg,flag')->where('data_id', $model->id)->select();
        $pdata = array_column(collection($pollutionData)->toArray(), NULL, 'code');
        $pdata = array_map(function ($d) {
            unset($d['code']);
            return $d;
        }, $pdata);

        $pollutionstr = DataReverseConverter::writePollution($pdata);
        $arr = $model->toArray();
        unset($arr['id']);
        unset($arr['source_data']);
        unset($arr['is_forward']);
        unset($arr['is_change']);
        $res = DataReverseConverter::writeData($arr);
        $datastr = $res . ';' . $pollutionstr . '&&';
        $crc = DataReverseConverter::writeCrc($datastr);
        $len = DataReverseConverter::writeDateLen($datastr);
        $sendData = '##' . $len . $datastr . $crc;
        return $sendData;
    }
}