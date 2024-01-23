<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/23
 */

namespace app\api\controller;


use app\common\controller\Api;
use app\hj212\segment\converter\DataConverter;
use app\hj212\T212Parser;
use think\Exception;

class Test extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ["*"];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ["*"];
    public function index(){
        try{
            $data ="##0612QN=20240123101501421;ST=31;CN=2061;PW=123456;MN=81733553216103;Flag=4;CP=&&DataTime=20240122170000;a00000-Cou=19191.973,a00000-Min=5.051,a00000-Avg=5.331,a00000-Max=5.617,a00000-Flag=N;a01011-Min=6.686,a01011-Avg=7.029,a01011-Max=7.425,a01011-Flag=N;a01013-Min=-0.041,a01013-Avg=-0.026,a01013-Max=0.011,a01013-Flag=N;a01012-Min=8.074,a01012-Avg=8.524,a01012-Max=8.789,a01012-Flag=N;a24088-Cou=0.018,a24088-Min=0.665,a24088-Avg=0.933,a24088-Max=1.418,a24088-Flag=N;a05002-Cou=0.000,a05002-Min=0.000,a05002-Avg=0.000,a05002-Max=0.000,a05002-Flag=N;a01014-Min=0.828,a01014-Avg=1.040,a01014-Max=1.239,a01014-Flag=N&&0E40";
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
        }catch (Exception $exception){
            var_dump($exception->getMessage());
        }
    }
}