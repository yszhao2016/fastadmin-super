<?php
namespace app\hj212\model;

/**
 * 现场端
 * Class LiveSide
 * @package App\Model
 */
class LiveSide {

    // Info 现场端信息
    private $info;
    
    // SN 在线监控（监测）仪器仪表编码
    private $sn;

    function getInfo() {
        return $this->info;
    }

    function setInfo($info) {
        $this->info = $info;
    }

    function getSn() {
        return $this->sn;
    }

    function setSn($sn) {
        $this->sn = $sn;
    }
}
