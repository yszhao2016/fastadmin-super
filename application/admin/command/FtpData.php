<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/2/2
 */

namespace app\admin\command;


use app\common\library\Ftp;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Log;


class FtpData extends Command
{
    protected function configure()
    {
        $this->setName('ftpdata:send')->setDescription('ftp 传送数据');
    }


    protected function execute(Input $input, Output $output)
    {

        try {
            if(date("H")=="22"){
                $filename = date("Ymd") . ".zip";
            }else if(date("H")=="01"){
                $filename = date("Ymd",strtotime("-1 day")) . ".zip";
            }else{
                $filename = date("Ymd",strtotime("-1 day")) . ".zip";
            }
//            $filename =  "test.zip";
            $local_filepath = ROOT_PATH . "ftp/";
            $downloadlock = ROOT_PATH . "runtime/lock/download_" . date("Ymd") . ".lock";
            $lockfile = ROOT_PATH . "runtime/lock/upload_" . date("Ymd") . ".lock";
            $isputlock = 3;
            if (is_file($lockfile)) {
                $isputlock = file_get_contents($lockfile);
            }
            // 没有上传  或者  上传失败
            if ($isputlock == 3 || $isputlock == 2) {

                // 判断本地下载目录是否存在
                if (!is_dir($local_filepath)) {
                    mkdir($local_filepath);
                }

                // 判断金辰是否上传了  并且 我方没有上传客户
                $islock = 3;
                if (is_file($downloadlock)) {
                    $islock = file_get_contents($downloadlock);
                }
                $jcconn = Ftp::login('36.134.18.186', 'jstest', 'Y5ZR6Ji32sWXYrNp', '22221');

                if ($jcconn && Ftp::isuploadfile($jcconn, $filename) && ($islock == 2 || $islock == 3)) {
                    //判断金辰是否上传了 并且 还未下载或者下载失败了
                    Log::info("开始下载");
                    file_put_contents($downloadlock, 0);
                    $ret = ftp_get($jcconn, $local_filepath . $filename, $filename, FTP_BINARY);
                    if ($ret) {
                        file_put_contents($downloadlock, 1);
                    } else {
                        file_put_contents($downloadlock, 2);
                    }
                    Log::info("下载结束");
                } else if (!Ftp::isuploadfile($jcconn, $filename)) {
                    //金辰 没有上传  失败处理
                    Log::info("还未上传");
                    $ret = false;
                    //?
                } else if ($islock = 1) {
                    //金辰 上传了  已经下载成功了
                    Log::info("金辰 上传了  已经下载成功了");
                    $ret = true;
                }

                // 下载成功了
                if ($ret) {
                    Log::info("开始上传");
                    $myconn = Ftp::login('36.134.37.185', 'utooo', 'FaYwDdX5tR3GknxJ', '22221');
                    file_put_contents($lockfile, 0);
                    $isputlock = ftp_put($myconn, $filename, $local_filepath . $filename, FTP_BINARY);
                    if ($isputlock) {
                        file_put_contents($lockfile, 1);
                    } else {
                        file_put_contents($lockfile, 2);
                    }
                    Log::info("上传结束");
                }
            }
            isset($jcconn) ? ftp_close($jcconn) : "";
            isset($myconn) ? ftp_close($myconn) : "";

        } catch (\Exception $exception) {
            is_file($downloadlock) ? unlink($downloadlock) : "";
            is_file($lockfile) ? unlink($lockfile) : "";
            var_dump($exception->getMessage());
        }

        // 最后判断下 昨天临时文件是否还在 在就是删除
        $filename = date("Ymd", strtotime("-5 day")) . ".zip";
        $yesterdayfile = $local_filepath . $filename;
        is_file($yesterdayfile) ? unlink($yesterdayfile) : "";
    }


}