<?php
/**
 * Created by PhpStorm
 * USER: Zhaoys
 * Date: 2022/5/7 17:07
 */

namespace app\admin\command;

use fast\Ftp;
use think\console\Command;
use think\Exception;
use think\console\Input;
use think\console\Output;

class Test extends Command
{
    protected $xml_path = "tydk/";  //局方ftp服务器路径

    protected function configure()
    {
        $this->setName('readtydk')->setDescription('read tydk data');
    }

    protected function execute(Input $input, Output $output)
    {

	$data = \app\admin\model\hj212\Pollution::all();
        foreach ($data as  $item){
            $d = \app\admin\model\hj212\Data::get($item->data_id);
	    $item->qn=$d->qn;
	   // $item->cp_datatime=$d->cp_datatime;
            $item->save();
        }	

        exit();
        try{
            $ftp_host = config("site.ftp_host");
            $ftp_port = config("site.ftp_port");
            $ftp_username = config("site.ftp_username");
            $ftp_pwd = config("site.ftp_pwd");

            $config = [
                'hostname' => $ftp_host,
                'username' => $ftp_username,
                'password' => $ftp_pwd,
                'port' => $ftp_port,
            ];
            $ftp = new Ftp();
            $ftp->connect($config);
            $ftp->move_file('tydk/342201199406282537-1651914676599.xml','tydk/processed/342201199406282537-1651914676599.xml');
        }catch (Exception $e){
            var_dump($e->getMessage());
        }



    }
}
