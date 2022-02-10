<?php
namespace app\hj212\segment\converter;

/**
 * 反向封装数据
 * @author Tqq
 *
 */
class DataReverseConverter 
{
    public $header = '##';
    public $footer = "\\r\\n";
    public $CpHeader = 'CP=&&';
    public $CpFooter = '&&';
    
    public function __construct()
    {
        
    }
    /*
             * 生成通信包
     */
    public function writeHeader()
    {
        return $this->header;
    }
    /**
             * 生成数据段长度
     */
    public function writeDateLen($data = "")
    {
        $count = strlen($data);
        $len = (string)$count;
        if(strlen($len) < 4){
            //不足4位时，补足0
            $len = str_pad($len,4,'0',STR_PAD_LEFT);
        }
        return $len;
    }
    /**
             * 生成数据段
     */
    public function writeData($data = array())
    {
        $res = '';
        $i=1;
        foreach($data as $k=>$v){
            if($i == 1){
                $res.=strtoupper($k).'='.$v;
            }else{
                $res.=';'.strtoupper($k).'='.$v;
            }
            $i++;
        }
        return $res;
        
    }
    /**
             * 生成数据区
     */
    public function writeCpData($data = array())
    {
        $res = '';
        $i=1;
        foreach($data as $k=>$v){
            if($i == 1){
                $res.=strtoupper($k).'='.$v;
            }else{
                $res.=';'.strtoupper($k).'='.$v;
            }
            $i++;
        }
        return $res;  
    }
    /**
             * 生成污染因子
     */
    public function writePollution($data = array())
    {
        $res = '';
        $j = 1;
        foreach($data as $k1=>$v1){
            if($j==1){
                $res.= '';
            }else{
                $res.= ';';
            }
            $i = 1;
            foreach($v1 as $k2=>$v2){
                if($i == 1){
                    $res.= $k1.'-'.strtoupper($k2).'='.$v2;
                }else{
                    $res.= ','.$k1.'-'.strtoupper($k2).'='.$v2;
                }
                $i++;
            }
            $j++;
        }
        return $res;
        
    }
    /**
              * 生成现场端
     */
    public function writeLiveSide($data = array())
    {
        $res = '';
        $j = 1;
        foreach($data as $k1=>$v1){
            if($j==1){
                $res.= '';
            }else{
                $res.= ';';
            }
            $i = 1;
            foreach($v1 as $k2=>$v2){
                if($i == 1){
                    $res.= $k1.'-'.strtoupper($k2).'='.$v2;
                }else{
                    $res.= ','.$k1.'-'.strtoupper($k2).'='.$v2;
                }
                $i++;
            }
            $j++;
        }
        return $res;  
    }
    /**
             * 生成设备
     */
    public function writeDevice($data = array())
    {
        $res = '';
        $j = 1;
        foreach($data as $k1=>$v1){
            if($j==1){
                $res.= '';
            }else{
                $res.= ';';
            }
            $i = 1;
            foreach($v1 as $k2=>$v2){
                if($i == 1){
                    $res.= 'SB'.$k1.'-'.strtoupper($k2).'='.$v2;
                }else{
                    $res.= ',SB'.$k1.'-'.strtoupper($k2).'='.$v2;
                }
                $i++;
            }
            $j++;
        }
        return $res;
        
    }
    /**
             * 生成CRC
     */
    public function writeCrc($msg, int $length)
    {
        $crc_reg = 0xFFFF;
        for($i=0; $i<$length; $i++) {
            $crc_reg = ($crc_reg>>8) ^ ord($msg[$i]);
            for($j=0;$j<8;$j++) {
                $check = $crc_reg & 0x0001;
                $crc_reg >>= 1;
                if ($check == 0x0001) {
                    $crc_reg ^= 0xA001;
                }
            }
        }
        return strtoupper(dechex($crc_reg));
    }
    /**
             * 生成包尾
     */
    public function writeFooter()
    {
        return $this->footer;
    }
}
