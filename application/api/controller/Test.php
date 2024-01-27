<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/23
 */

namespace app\api\controller;


use app\common\controller\Api;
use app\common\library\Utils;
use app\hj212\model\Data;
use app\hj212\model\Pollution;
use app\hj212\segment\converter\DataConverter;
use app\hj212\T212Parser;
use think\Cache;
use think\Db;
use think\Env;
use think\Exception;

class Test extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ["*"];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ["*"];

    public function index()
    {
        var_dump(Utils::getYMRange("2023-09","2024-01"));
        var_dump(Utils::getYMRange("2024-01","2024-05"));
        var_dump(Utils::getYMRange("2024-01","2024-01"));exit;
        try {

            // $data ="##0612QN=20240123101501421;ST=31;CN=2061;PW=123456;MN=81733553216103;Flag=4;CP=&&DataTime=20240122170000;a00000-Cou=19191.973,a00000-Min=5.051,a00000-Avg=5.331,a00000-Max=5.617,a00000-Flag=N;a01011-Min=6.686,a01011-Avg=7.029,a01011-Max=7.425,a01011-Flag=N;a01013-Min=-0.041,a01013-Avg=-0.026,a01013-Max=0.011,a01013-Flag=N;a01012-Min=8.074,a01012-Avg=8.524,a01012-Max=8.789,a01012-Flag=N;a24088-Cou=0.018,a24088-Min=0.665,a24088-Avg=0.933,a24088-Max=1.418,a24088-Flag=N;a05002-Cou=0.000,a05002-Min=0.000,a05002-Avg=0.000,a05002-Max=0.000,a05002-Flag=N;a01014-Min=0.828,a01014-Avg=1.040,a01014-Max=1.239,a01014-Flag=N&&0E40";
            //$data="##0318QN=20240123123950241;ST=31;CN=2011;PW=123456;MN=81733553216103;Flag=4;CP=&&DataTime=20240122081600;a00000-Rtd=5.407,a00000-Flag=N;a01011-Rtd=7.072,a01011-Flag=N;a01013-Rtd=-0.052,a01013-Flag=N;a01012-Rtd=6.480,a01012-Flag=N;a24088-Rtd=0.804,a24088-Flag=N;a05002-Rtd=0.000,a05002-Flag=N;a01014-Rtd=0.970,a01014-Flag=N&&6740";
            //$data="##0437QN=20240123123205877;ST=32;CN=2011;PW=123456;MN=000101;Flag=5;CP=&&DataTime=20240123123200;w01018-SampleTime=20240123110306,w01018-Rtd=4653.8,w01018-Flag=N;w21003-SampleTime=20240123110305,w21003-Rtd=43.58,w21003-Flag=N;w21011-SampleTime=20240123110302,w21011-Rtd=1.58,w21011-Flag=N;w21001-SampleTime=20240123110336,w21001-Rtd=83.53,w21001-Flag=N;w01001-Rtd=7.17,w01001-Flag=N;w01010-Rtd=6.3,w01010-Flag=N;w00000-Rtd=3.41,w00000-Flag=N&&D941";
            //           $data="##0437QN=20240123131005589;ST=32;CN=2011;PW=123456;MN=000101;Flag=5;CP=&&DataTime=20240123131000;w01018-SampleTime=20240123110306,w01018-Rtd=4653.8,w01018-Flag=N;w21003-SampleTime=20240123110305,w21003-Rtd=43.58,w21003-Flag=N;w21011-SampleTime=20240123110302,w21011-Rtd=1.58,w21011-Flag=N;w21001-SampleTime=20240123110336,w21001-Rtd=83.53,w21001-Flag=N;w01001-Rtd=7.19,w01001-Flag=N;w01010-Rtd=8.1,w01010-Flag=N;w00000-Rtd=3.41,w00000-Flag=N&&2F80";
//            $data="##0100QN=20240123131014225;ST=31;CN=3020;PW=123456;MN=81733553216103;Flag=4;CP=&&DataTime=20240123131000&&1040";
            $data = "##0213ST=32;CN=2011;PW=123456;MN=81733553216204;CP=&&DataTime=20240123131500;001-Rtd=7.470,001-Flag=N;B01-Rtd=0.000;011-Rtd=32.439,011-Flag=N;060-Rtd=0.466,060-Flag=N;101-Rtd=0.287,101-Flag=N;065-Rtd=25.801,065-Flag=N&&47C0";
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
            $suffix = date("Yd");
            $dataTableName = "hj212_data_" . $suffix;
            $pollutionTableName = "hj212_pollution_" . $suffix;
            Db::startTrans();
            try {
                $insetdata['created_at'] = time();
                $insetdata['updated_at'] = time();
                $id = $this->getID("hj212_data_", $suffix);

                $insetdata['id'] = $id;
                Db::name($dataTableName)->insert($insetdata);
                foreach ($cpData['pollution'] as $k => $val) {
                    $val['data_id'] = $id;
                    $val['qn'] = isset($insetdata['qn'])?$insetdata['qn']:"";
                    $val['cn'] = $insetdata['cn'];
                    $val['mn'] =  $insetdata['mn'];
                    $val['cp_datatime'] = $insetdata['cp_datatime'];
                    $val['code'] = $k;
                    $val['created_at'] = time();
                    $val['updated_at'] = time();
                    Db::name($pollutionTableName)->insert($val);
                }
                Db::commit();
            } catch (\think\exception\PDOException $e) {
                Db::rollback();
                $this->createTableByTemplate($dataTableName, "hj212_data_template", true);
                $this->createTableByTemplate($pollutionTableName, "hj212_pollution_template", true);
                var_dump($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
            }
        } catch (Exception $exception) {
            var_dump($exception->getMessage());
        }
    }

    /**
     * @param $newTableName
     * @param $templateTableName
     * @return bool|mixed
     */
    public function createTableByTemplate($newTableName, $templateTableName, $ispre)
    {
        $res = false;
        if ($ispre) {
            $newTableName = Env::get('database.prefix', 'fa_') . $newTableName;
            $templateTableName = Env::get('database.prefix', 'fa_') . $templateTableName;
        }
        $isExistDataTable = Db::query("SHOW TABLES LIKE '{$newTableName}'");
        if (!$isExistDataTable) {
            $sql = "CREATE TABLE {$newTableName} LIKE {$templateTableName};";
            $res = Db::query($sql);
        }
        return $res;
    }


    /**
     * @param $tableName
     * @param $suffix
     * @return int|mixed|string
     */
    private function getID($tableName, $suffix)
    {
        $id = Cache::get($tableName);
        if (!$id) {
            $id = Db::name($tableName . $suffix)->getLastInsID();
            if (!$id) {
                $newsuffix = date("Yd", strtotime('-1 month'));
                $newid = Db::name($tableName . $newsuffix)->getLastInsID();
                $id = $newid ? $newid : 0;
            }

        }
        ++$id;
        Cache::set($tableName, $id);
        return $id;
    }
}