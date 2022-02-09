<?php
namespace app\hj212\model;

/**
 * 污染治理设施
 * Class Device
 * @package App\Model
 */
class Device {
    // RS 污染治理设施运行状态的实时采样值
    private $rs;

    // RT 污染治理设施一日内的运行时间
    private $rt;

    function getRs() {
        return $this->rs;
    }

    function setRs($rs) {
        $this->rs = $rs;
    }

    function getRt() {
        return $this->rt;
    }

    function setRt($rt) {
        $this->rt = $rt;
    }
}
