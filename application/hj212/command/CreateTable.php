<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/29
 */

namespace app\hj212\command;


use app\common\library\Utils;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class CreateTable extends Command
{

    protected function configure()
    {
        $this->setName('hj212:createtable')->setDescription('hj212:createtable');
    }


    protected function execute(Input $input, Output $output)
    {

        $data = Db::name("hj212_alarm_data")->select();

        foreach($data as $item){

            $p = json_decode($item['detail'],true);
            if(!isset($p['data_id'])) continue;
            Db::name("hj212_alarm_data")->where('id',$item['id'])->update(['data_id'=>$p['data_id']]);

        }
        exit;
        $suffix = date('Ym', strtotime("+1 month"));
        $dataTableName = "hj212_data_" . $suffix;
        $pollutionTableName = "hj212_pollution_" . $suffix;
        if (!Utils::isTableExist($dataTableName)) {
            Utils::createTableByTemplate($dataTableName, "hj212_data_template", true);
        }
        if (!Utils::isTableExist($pollutionTableName)) {
            Utils::createTableByTemplate($pollutionTableName, "hj212_pollution_template", true);
        }
    }
}