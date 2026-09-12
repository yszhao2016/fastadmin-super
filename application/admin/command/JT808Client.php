<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2026/9/12
 */

namespace app\admin\command;

use think\console\Input;
use think\console\Output;
use think\console\Command;
use Swoole\Server as SwooleServer;
class JT808Client extends Command
{
    // Server 实例
    protected $server;

    protected $t212Parser;

    protected $buffer;

    protected $connections = [];

    protected function configure()
    {
        $this->setName('JT808Client:start')->setDescription('JT808 client');
    }

    public function execute(Input $input, Output $output)
    {

//        // 监听所有地址，监听端口f
        $this->server = new SwooleServer('0.0.0.0', 65213);

        $this->server->set(array(
            'worker_num' => 10,
//            'task_worker_num' => 2,   //必须设置 on task
            'max_request' => 10000,
//            'open_length_check' => true,
//            'package_length_func' => '',
            'daemonize' => true,
            'log_file' => "runtime/log/swoole.log",
            'dispatch_mode' => 2,
        ));

        $this->server->on('start', array($this, 'onStart'));

        $this->server->on('connect', array($this, 'onConnect'));
        $this->server->on('receive', array($this, 'onReceive'));

        $this->server->on('close', array($this, 'onClose'));
        $this->server->start();

    }


    public function onStart($serv)
    {
        echo "TCP Server is started at tcp://\n";
    }

    public function onConnect($serv, $fd)
    {
        echo "connection open: {$fd}\n";
        // 为每个连接创建独立的buffer
        $this->connections[$fd] = [
            'buffer' => '',
            'ip' => $serv->getClientInfo($fd)['remote_ip'] ?? 'unknown',
            'connect_time' => time()
        ];
        file_put_contents('/home/apps/web/fastadmin-super/runtime/log/hj212connect_' . date("Ymd") . '.log', date('Y-m-d H:i:s') . "ip" . "open: {$fd}");
    }

    public function onReceive($serv, $fd, $reactor_id, $data)
    {
        if (!isset($this->connections[$fd])) {
            $this->connections[$fd] = [
                'buffer' => '',
                'ip' => $serv->getClientInfo($fd)['remote_ip'] ?? 'unknown',
                'connect_time' => time()
            ];
        }

        $connection = &$this->connections[$fd];
        $hex = strtoupper(bin2hex($data));
        $frames = $this->jt808SplitFrames($data);
        var_dump($frames);
        file_put_contents('/home/apps/web/fastadmin-super/runtime/log/jt808receive_' . date("Ymd") . '.log', PHP_EOL."data:". $hex . PHP_EOL, FILE_APPEND);

        foreach ($frames as $frame) {
            $data1 = self::jt808Unescape($frame);   // 去掉首尾 7E 后再调
            $h = self::jt808_parse_2019_header($data1);

            if ($h && $h['msgId'] === '0200') {
                $h['location'] = self::jt808_parse_0200_body($h['body']);
            }


            file_put_contents('/home/apps/web/fastadmin-super/runtime/log/jt808receive_' . date("Ymd") . '.log', PHP_EOL."解析出来是:". PHP_EOL . print_r($h, true) . PHP_EOL, FILE_APPEND);
        }
        $this->server->send($fd, "2222333444");

    }

    public function onClose($serv, $fd)
    {
        echo "connection close: {$fd}\n";
        // 清理连接相关的资源
        if (isset($this->connections[$fd])) {
            unset($this->connections[$fd]);
        }
    }

    /**
     * 同步任务
     * $this->serv->taskwait($data); 触发
     * @param $server
     * @param $task_id
     * @param $data
     */
    function onSyncTask($serv, $task_id, $from_id, $data)
    {
        echo "Sync task Callback: " . $data;

        $data="同步任务这边";
        echo $data;

        $result = $data;
        // 通知完成
        $this->server->finish($result);
    }

    /**
     * 异步任务
     * $this->serv->task($data); 触发
     * @param $server
     * @param $task_id
     * @param $data
     */
    function onAsynTask($serv, $task_id, $from_id, $data)
    {
        echo "Asyn task Callback: ";
        // 通知完成
        $this->server->finish($data . "cccccsssss");

    }

    public function onFinish($serv, $task_id, $data)
    {
        echo "任务完成";//taskwait  不触发这个函数。。
    }

    function jt808Unescape(string $data): string
    {
        // 0x7D 0x01 -> 0x7D
        // 0x7D 0x02 -> 0x7E
        return strtr($data, [
            "\x7D\x01" => "\x7D",
            "\x7D\x02" => "\x7E",
        ]);
    }

    private function jt808SplitFrames(string $stream): array
    {
        $frames = [];
        $parts = explode("\x7E", $stream);

        // 首尾可能是空（因为 7E 在边界）
        foreach ($parts as $part) {
            if ($part !== '') {
                $frames[] = $part;
            }
        }
        return $frames;
    }

    public function jt808CheckSum(string $data): int
    {
        $sum = 0;
        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $sum ^= ord($data[$i]);
        }
        return $sum & 0xFF;
    }

    public function jt808Parse(string $rawFrame): ?array
    {
        // 反转义
        $data =self::jt808Unescape($rawFrame);

        // 至少：头12 + 校验1
        if (strlen($data) < 13) {
            return null;
        }

        // 拆分
        $msgBody    = substr($data, 0, -1);
        $checkSum   = ord($data[-1]);

        // 校验
        if(self::jt808CheckSum($msgBody) !== $checkSum) {
            return null;
        }

        // 消息头解析
        $msgId   = unpack('n', substr($msgBody, 0, 2))[1];
        $attr    = unpack('n', substr($msgBody, 2, 2))[1];
        $phone   = bin2hex(substr($msgBody, 4, 6));
        $seq     = unpack('n', substr($msgBody, 10, 2))[1];

        // 消息体长度（低10位）
        $bodyLen = $attr & 0x03FF;
        $body    = substr($msgBody, 12, $bodyLen);

        return [
            'msgId'   => sprintf('%04X', $msgId),
            'attr'    => $attr,
            'phone'   => $phone,
            'seq'     => $seq,
            'body'    => $body,
            'bodyHex' => bin2hex($body),
        ];
    }


    private function jt808_u32(string $b, int $off): int {
        // 大端无符号 32 位，避免 unpack('N') 负数是坑
        $v = unpack('N', substr($b, $off, 4))[1];
        return $v & 0xFFFFFFFF;
    }

    private function jt808_bcd_time(string $bcd6): string {
        $h = bin2hex($bcd6);
        if (strlen($h) !== 12) return 'INVALID';
        $yy = substr($h,0,2);
        $mm = substr($h,2,2);
        $dd = substr($h,4,2);
        $HH = substr($h,6,2);
        $ii = substr($h,8,2);
        $ss = substr($h,10,2);
        // 简单校验月份/日期，避免 2c 这种
        if (!checkdate((int)$mm, (int)$dd, (int)("20$yy"))) {
            return "BCD_WRONG($h)";
        }
        return "20$yy-$mm-$dd $HH:$ii:$ss";
    }

    private function jt808_parse_2019_header(string $data): ?array {
        // data = 已经去掉 0x7E 并反转义后的内容（不含首尾 7E）
        if (strlen($data) < 5) return null;

        $msgId = unpack('n', substr($data,0,2))[1];
        $attr  = unpack('n', substr($data,2,2))[1];

        $bodyLen = $attr & 0x03FF;
        $encrypt = ($attr >> 10) & 0x07;
        $subPkg  = ($attr >> 13) & 0x01;
        $verFlag = ($attr >> 14) & 0x01;

        $off = 4;

        if ($verFlag) {
            // 2019
            $version  = ord($data[$off]); $off += 1;
            $phone    = bin2hex(substr($data, $off, 10)); $off += 10;
        } else {
            // 2011/2013
            $version  = null;
            $phone    = bin2hex(substr($data, $off, 6)); $off += 6;
        }

        $seq = unpack('n', substr($data, $off, 2))[1]; $off += 2;

        if ($subPkg) {
            $pkgTotal = unpack('n', substr($data, $off, 2))[1]; $off += 2;
            $pkgNo    = unpack('n', substr($data, $off, 2))[1]; $off += 2;
        } else {
            $pkgTotal = null;
            $pkgNo = null;
        }

        $body = substr($data, $off, $bodyLen);

        return [
            'msgId'   => sprintf('%04X', $msgId),
            'attr'    => $attr,
            'verFlag' => $verFlag,
            'version' => $version,
            'phone'   => $phone,
            'seq'     => $seq,
            'subPkg'  => $subPkg,
            'pkgTotal'=> $pkgTotal,
            'pkgNo'   => $pkgNo,
            'bodyHex' => bin2hex($body),
            'body'    => $body,
        ];
    }

    private function jt808_parse_0200_body(string $body): ?array {
        if (strlen($body) < 28) return null;

        $alarm  = self::jt808_u32($body, 0);
        $status = self::jt808_u32($body, 4);
        $latRaw = self::jt808_u32($body, 8);
        $lngRaw = self::jt808_u32($body, 12);

        $alt     = unpack('n', substr($body,16,2))[1];
        $speed   = unpack('n', substr($body,18,2))[1] / 10;
        $dir     = unpack('n', substr($body,20,2))[1];
        $time    = self::jt808_bcd_time(substr($body,22,6));

        $extra   = substr($body,28);

        return [
            'alarm'   => $alarm,
            'status'  => $status,
            'lat'     => $latRaw / 1000000,
            'lng'     => $lngRaw / 1000000,
            'alt'     => $alt,
            'speed'   => $speed,
            'dir'     => $dir,
            'time'    => $time,
            'acc_on'  => (bool)($status & 0x01),
            'located' => (bool)($status & 0x02),
            'extraHex'=> bin2hex($extra),
        ];
    }
}