<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/19
 */

namespace app\job;

use app\common\library\Utils;
use app\hj212\segment\converter\DataConverter;
use app\hj212\T212Parser;
use think\Cache;
use think\Db;
use think\Exception;
use think\Log;
use think\queue\Job;

/**
 * Class Hj212DataParser
 * @package app\job
 * 解析处理 队列中hj212 格式数据 2017版本的协议
 */
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
            $suffix = date("Ym");
            $dataTableName = "hj212_data_" . $suffix;
            $pollutionTableName = "hj212_pollution_" . $suffix;
            Db::startTrans();
            try {

                $insetdata['created_at'] = time();
                $insetdata['updated_at'] = time();
                $id = $this->getID("hj212_data_", $suffix);
                $insetdata['updated_at'] =  $id;
                Db::name($dataTableName)->insert($insetdata);
                // 遍历数据 并插入数据库 pollution
                foreach ($cpData['pollution'] as $k => $val) {
                    $val['data_id'] = $id;
                    $val['qn'] = isset($insetdata['qn']) ? $insetdata['qn'] : "";
                    $val['cn'] = $insetdata['cn'];
                    $val['mn'] = $insetdata['mn'];
                    $val['cp_datatime'] = $insetdata['cp_datatime'];
                    $val['code'] = $k;
                    $val['created_at'] = time();
                    $val['updated_at'] = time();
                    Db::name($pollutionTableName)->insert($val);
                }
                Db::commit();
            } catch (\think\exception\PDOException $e) {
                Db::rollback();
                Utils::createTableByTemplate($dataTableName, "hj212_data_template", true);
                Utils::createTableByTemplate($pollutionTableName, "hj212_pollution_template", true);
                Log::info("hj212 队列 PDOException:" . $e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                Log::info("hj212 队列 Exception:" . $e->getMessage());
            }
            $job->delete();
        } catch (\Exception $exception) {
            file_put_contents(ROOT_PATH . "/runtime/log/hj212queue-error-" . date("Y-m-d") . ".log", $ysdata . PHP_EOL . $exception->getMessage() . PHP_EOL . PHP_EOL);
            $job->delete();
        }
    }

    /**
     * @param $tableName
     * @param $suffix
     * @return int|mixed|string
     */
    /**
     * @param $tableName
     * @param $suffix
     * @return int|mixed|string
     */
    private function getID($tableName, $suffix)
    {
        $id = Cache::get($tableName);
        if (!$id) {
            // 如果缓存不存在，数据库获取
            $model = Db::name($tableName . $suffix)->order("id","desc")->find();

            if (!isset($model->id)) {
                // 如果数据库获取不打 那就上个月的表获取id
                $newsuffix = date("Yd", strtotime('-1 month'));
                $newmodel = Db::name($tableName . $newsuffix)->order("id","desc")->find();

                // 如果还取不到，就从0开始吧
                $id = isset($newmodel->id) ? $newmodel->id : 0;
            }
        }
        // 取到上一次id 自增
        $id++;
        Cache::set($tableName, $id);
        return $id;
    }

}