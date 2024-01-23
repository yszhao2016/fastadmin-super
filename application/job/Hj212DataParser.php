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
//            Log::info('开始发送消息：' . json_encode($data));
//            var_dump($data);exit("xxx");
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
            var_dump($data);
            var_dump($exception->getMessage());
//            exit("xxx12334");
            $job->delete();
//            // 队列执行失败
//            Log::error('发送消息队列执行失败：---' . json_encode($data) . '---' . PHP_EOL . $exception->getMessage());
        }
    }
}