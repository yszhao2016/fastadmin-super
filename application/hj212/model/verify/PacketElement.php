<?php
namespace App\hj212\model\verify;

/**
 * 通信包
 * Class PacketElement
 * @package App\core\verify
 */
class PacketElement {
    const HEADER = 2;
    const DATA_LEN = 4;
    const DATA = -0;
    const DATA_CRC = 4;
    const FOOTER = 2;


    private $len;

    function __construct(int $len) {
        $this->len = $len;
    }

    function getLen() {
        return $this->len;
    }

    function setLen(int $len) {
        $this->len = $len;
    }

    public static function getElementVar() {
        $oClass = new \ReflectionClass(__CLASS__);
        return json_encode($oClass->getConstants());
    }

}