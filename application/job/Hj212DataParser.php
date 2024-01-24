<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/19
 */

namespace app\job;

use app\hj212\model\Data;
use app\hj212\model\Pollution;
use app\hj212\segment\converter\DataConverter;
use app\hj212\T212Parser;
use think\Db;
use think\Log;
use think\queue\Job;


class Hj212DataParser
{
    public function fire(Job $job, $data)
    {
        try {
            $ysdata = $data;
            $t212Parser = new T212Parser();
            $sourceData = $data;
            $t212Parser->setReader($data);
            $t212Parser->readHeader();
            $dataLen = $t212Parser->readDataLen();
            $data = $t212Parser->readDataAndCrc($dataLen);
            $dataConverter = new DataConverter($data);
            $insetdata = $dataConverter->convertData();
            $insetdata['data_len'] = $dataLen;
            $insetdata['crc'] = $t212Parser->readCrcInt16();
            $insetdata['source_data'] = $sourceData;
            $cpData = $dataConverter->convertCpData();
            //存在数据空的情况 ##0069QN=20230723080035006;ST=91;CN=9021;PW=123456;MN=000201;Flag=4;CP=&&&&BDC1
            $insetdata['cp_datatime'] = isset($cpData['cpData']['datatime']) ? $cpData['cpData']['datatime'] : 0;
            Db::startTrans();
            try {

                $dataModel = new Data($insetdata);
                $dataModel->save();
                $pollutionModel = new Pollution();
                foreach ($cpData['pollution'] as $k => $val) {
                    $pModel = clone $pollutionModel;
                    $val['data_id'] = $dataModel->id;
                    $val['cn'] = $dataModel->cn;
                    $val['mn'] = $dataModel->mn;
                    $val['cp_datatime'] = $dataModel->cp_datatime;
                    $val['code'] = $k;
                    $pModel->data($val);
                    $pModel->save();
                }
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
            }
            $job->delete();
        } catch (\Exception $exception) {
            file_put_contents(ROOT_PATH . "/runtime/log/hj212queue-error-" . date("Y-m-d") . ".log", $ysdata . PHP_EOL . $exception->getMessage() . PHP_EOL . PHP_EOL);
            $job->delete();
//            // 队列执行失败
//            Log::error('发送消息队列执行失败：---' . json_encode($data) . '---' . PHP_EOL . $exception->getMessage());
        }
    }
}