<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/25
 */

namespace app\common\library;


use think\Db;
use think\Env;

class Utils
{
    /**
     * 根据模版表创建表
     * @param $newTableName
     * @param $templateTableName
     * @return bool|mixed
     */
    public static function createTableByTemplate($newTableName, $templateTableName, $ispre)
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
     * 判断数据库表是否存在
     * @param $tableName
     * @param bool $ispre
     * @return mixed
     */
    public static function isTableExist($tableName, $ispre = true)
    {
        if ($ispre) {
            $tableName = Env::get('database.prefix', 'fa_') . $tableName;
        }
        $isExistDataTable = Db::query("SHOW TABLES LIKE '{$tableName}'");
        return $isExistDataTable;
    }

    public static function getYMRange($start, $end)
    {
        $startYear = date("Y",strtotime($start));
        $endYear =  date("Y",strtotime($end));
        $startMonth = date("m",strtotime($start));
        $endMonth = date("m",strtotime($end));
        $res = [];
        if ($startYear == $endYear) {
            for($i = $startMonth;$i<= $endMonth;$i++){
                $res[] = "$startYear".str_pad($i,2,"0",STR_PAD_LEFT);
            }

        } else if ($startYear > $endYear) {
            //开始年不能大于结束年
            return [];
        } else {
            for($k = $startYear;$k<=$endYear;$k++){
                if($k == $endYear){

                    for($i = 1;$i<= $endMonth;$i++){
                        $res[] = "$endYear".str_pad($i,2,"0",STR_PAD_LEFT);
                    }
                }else{
                    if(!isset($month)){
                        $month =$startMonth;
                    }
                    for($j=$month;$j<=12;$j++){
                        $res[] = "$k".str_pad($j,2,"0",STR_PAD_LEFT);
                    }
                    $month =1;
                }

            }

        }
        return $res;
    }
}