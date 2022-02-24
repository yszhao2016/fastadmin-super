<?php
/**
 * Created by PhpStorm
 * USER: Zhaoys
 * Date: 2022/2/11 14:25
 */

namespace app\hj212\command;


use app\common\model\Config;
use app\hj212\model\Data;
use app\hj212\model\Pollution;
use app\hj212\segment\converter\DataReverseConverter;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use Swoole\Client as swooleClient;
use Swoole\Coroutine as Co;
use think\Exception;
use think\Log;

class Hj212Client extends Command
{
    protected function configure()
    {
        $this->setName('hjclient:start')->setDescription('Start hj212 Client!');
    }

    protected function execute(Input $input, Output $output)
    {
        Log::init(['type' => 'File', 'path' => ROOT_PATH . '/runtime/log/hjclient/']);
        do {
            $data = Data::field('id,source_data,qn,st,cn,pw,mn,flag,pnum,pno,cp_datatime,is_forward,is_change')
                ->where('is_forward', 0)->select();
            if ($data) {
                $serverInfo = Config::where('group', 'hj212')
                    ->where('name', 'push_server_info')
                    ->find();
                $serverInfoarr = json_decode($serverInfo->value);
                $client = new swooleClient(SWOOLE_SOCK_TCP);
                foreach ($serverInfoarr as $info) {
                    try {
                        $isConnect = "";
                        $isConnect = $client->connect(trim($info->ip), trim($info->port), 1);
                        if (!$isConnect) {
                            Log::write("{$info->ip}:{$info->port}connect failed. Error: {$client->errCode}\n");
                            continue;
                        }
                        foreach ($data as $model) {
                            //检查数据 是否能发送
                            if (!$this->checkData($model->id)) {
                                continue;
                            }
                            //改动的数据
                            if ($model->is_change) {
                                $sendData = $this->dealChangeData($model);
                                $client->send($sendData);
                            } else {
                                $sendData = $model->source_data;
                            }
                            $client->send($sendData);
                            Log::write("{$model->qn}--send data: {$sendData}\n");
                            $receive = $client->recv();
                            if ($receive) {
                                $model->is_forward = 1;
                                //报警的  修改后 转发  恢复is_alarm字段
                                $model->is_alarm = 0;
                                $res = $model->save();
                                Log::write("{$model->qn}--receive data: {$receive}\n");
                            }
                        }
                    } catch (Exception $e) {
                        Log::write("异常: {$e->getMessage()}\n");
                    }
                }
                if ($isConnect) {
                    $client->close();
                }

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
        $sendData = '##' . $len . $datastr . $crc . "\r\n";
        return $sendData;
    }


    protected function checkData($dataId)
    {
        //根据数据id 查询出 关联最大值比对
        $pollutionData = Pollution::field('code,cou,min,max,avg,flag')
            ->with('Alarm')
            ->where('data_id', $dataId)
            ->select();
        $data = collection($pollutionData)->toArray();
        foreach ($data as $item) {
            if ($item['alarm']['alarm_max'] && ($item['max'] > $item['alarm']['alarm_max'] || $item['avg'] > $item['alarm']['alarm_max'])) {
                return false;
            }
        }
        return true;
    }
}