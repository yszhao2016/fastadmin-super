<?php
namespace app\hj212\model;

/**
 * 通信包
 * Class Pack
 * @package App\Model
 */
class Pack {

    // 包头 2
    private $header;
    // 数据长度 4
    private $length;
    // 数据段 1024
    private $segment;
    // crc校验 4
    private $crc;
    // 包尾
    private $footer;

    function getHeader() {
        return $this->header;
    }

    function setHeader($header) {
        $this->header = $header;
    }

    function getLength() {
        return $this->length;
    }

    function setLength($length) {
        $this->length = $length;
    }

    function getSegment() {
        return $this->segment;
    }

    function setData($segment) {
        $this->segment = $segment;
    }

    function getCrc() {
        return $this->crc;
    }

    function setCrc($crc) {
        $this->crc = $crc;
    }

    function getFooter() {
        return $this->footer;
    }

    function setFooter($footer) {
        $this->footer = $footer;
    }
}
