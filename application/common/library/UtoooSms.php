<?php
/**
 * Created by PhpStorm
 * USER: Zhaoys
 * Date: 2022/2/21 17:39
 */

namespace app\common\library;


use Curl\Curl;
use think\Log;

class UtoooSms
{

    private $account;

    private $password;

    private $extno = '10690548681';

    private $url = 'http://47.111.188.212:7862/sms';


    public function __construct($account, $password)
    {
        $this->account = $account;
        $this->password = $password;
    }

    public function send(string $mobiles, string $msg)
    {
        $curl = new Curl();
        $params = [
            'action' => 'send',
            'account' => $this->account,
            'password' => MD5($this->password.$this->extno.$msg.$mobiles),
            'mobile' => $mobiles,
            'content' => $msg,
            'extno' => '10690548681',
            'rt' => 'json'
        ];

        Log::init(['type' => 'File', 'path' => ROOT_PATH . '/runtime/log/sms/']);
        $res = $curl->post($this->url, $params);
        Log::write($this->url . '-----' . json_encode($params));
        Log::write('返回结果-----' . json_encode($res));
        return $res;
    }
}