<?php
namespace app\hj212;

use app\hj212\feature\ParserFeature;
use app\hj212\model\verify\PacketElement;

/**
 * T212通信包解析器
 * Class T212Parser
 * @package App\core
 */
class T212Parser {

    public static $HEADER = '##';
    //需要双引号("")
    public static $FOOTER = "\r\n";

    protected $reader;
    //位置
    protected $readerIndex = 0;

    private $parserFeature;

    //TEMP
    private $count;
//    private PacketElement token;

    function __construct($reader = ''){
        $this->reader = $reader;

    }

    public function setReader($reader) {
        $this->reader = $reader;
        $this->readerIndex = 0;
    }

    /**
     * 设置解析特性
     * @param parserFeature 解析特性
     */
    function setParserFeature(int $parserFeature) {
        $this->parserFeature = $parserFeature;
    }

    /**
     * 读取 包头
     * @return mixed
     * @throws IOException
     */
    public function readHeader() {

//        $header = new char[2];
//        $count = $reader.read($header);

        $header = substr($this->reader,$this->readerIndex,PacketElement::HEADER);
        $this->readerIndex = $this->readerIndex + PacketElement::HEADER;
        $count = strlen($header);
        VerifyUtil::verifyEqualLen($count, PacketElement::HEADER, PacketElement::getElementVar());
        VerifyUtil::verifyChar($header, self::$HEADER, PacketElement::getElementVar());

        return $header;
    }

    /**
     * 读取 数据段长度
     * @see PacketElement#DATA_LEN
     * @return chars
     * @throws T212FormatException
     * @throws IOException
     */
    function readDataLen(){
        $len = substr($this->reader,$this->readerIndex,PacketElement::DATA_LEN);
        $this->readerIndex = $this->readerIndex + PacketElement::DATA_LEN;
        $count = strlen($len);
        VerifyUtil::verifyEqualLen($count,PacketElement::DATA_LEN, PacketElement::getElementVar());
        return $len;
    }

    /**
     * 读取 数据段
     * @see PacketElement#DATA
     * @return chars
     * @throws IOException
     */
    public function readData(int $segmentLen) {
        $segment = substr($this->reader,$this->readerIndex, $segmentLen);
        $this->readerIndex = $this->readerIndex + $segmentLen;
        $count = strlen($segment);
        VerifyUtil::verifyEqualLen($count,$segmentLen, PacketElement::getElementVar());
        return $segment;
    }

    /**
     * 读取 DATA_CRC 校验
     * @see PacketElement#DATA_CRC
     * @return header chars
     * @throws IOException
     */
    function readCrc() {
        $crc = substr($this->reader,$this->readerIndex, PacketElement::DATA_CRC);
        $this->readerIndex = $this->readerIndex + PacketElement::DATA_CRC;
        $count = strlen($crc);
        VerifyUtil::verifyEqualLen($count,PacketElement::DATA_CRC, PacketElement::getElementVar());
        return $crc;
    }

    /**
     * 读取 包尾
     * @see PacketElement
     * @return chars
     * @throws IOException
     */
    function readFooter() {
        $footer = substr($this->reader,$this->readerIndex,PacketElement::FOOTER);
        $this->readerIndex = $this->readerIndex + PacketElement::FOOTER;
        $count = strlen($footer);

        VerifyUtil::verifyEqualLen($count, PacketElement::FOOTER, PacketElement::getElementVar());
        VerifyUtil::verifyChar($footer, self::$FOOTER, PacketElement::getElementVar());

        return $footer;
    }

    /**
     * 读取 4字节Integer
     * @param radix 进制
     * @return Integer
     * @throws IOException
     */
    public function readCrcInt16() {
        $crc = substr($this->reader,$this->readerIndex, PacketElement::DATA_CRC);
//        $this->readerIndex = $this->readerIndex + PacketElement::DATA_CRC;
        $count = strlen($crc);

        if($count != 4){
            return -1;
        }
        //本身为十六进制字符串
        return $crc;
    }

    /**
     * 读取 data + 校验
     * @see PacketElement
     * @return chars
    \     * @throws IOException
     */
    function readDataAndCrc(int $dataLen) {
        $data = substr($this->reader,$this->readerIndex, $dataLen);
        $this->readerIndex = $this->readerIndex + $dataLen;
        $count = strlen($data);
        VerifyUtil::verifyEqualLen($count,$dataLen, PacketElement::getElementVar());

        $crc = $this->readCrcInt16();
        $check_crc = self::crc16Checkout($data,$dataLen);

        if($crc != -1 && $check_crc == $crc){
            return $data;
        }
        return null;
    }

    /**
     * CRC校验
     * @param msg 消息
     * @param length 长度
     * @return DATA_CRC 校验码
     */
    public static function crc16Checkout($msg, int $length) {
        $crc_reg = 0xFFFF;
        for($i=0; $i<$length; $i++) {
            $crc_reg = ($crc_reg>>8) ^ ord($msg[$i]);
//            echo $crc_reg . "\r\n";
            for($j=0;$j<8;$j++) {
                $check = $crc_reg & 0x0001;
                $crc_reg >>= 1;
                if ($check == 0x0001) {
                    $crc_reg ^= 0xA001;
                }
            }
        }
        $res = strtoupper(dechex($crc_reg));
        $res = str_pad($res, 4, "0", STR_PAD_LEFT);
        return $res;
    }



}
