<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/29
 */

namespace app\hj212\command;


use app\common\library\Utils;
use think\console\Input;
use think\console\Output;

class CreateTable
{

    protected function configure()
    {
        $this->setName('hjcheck:start')->setDescription('Start hj212 check!');
    }


    protected function execute(Input $input, Output $output)
    {
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