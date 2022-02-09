<?php
namespace app\hj212\model;
/**
 * 数据段
 * Class Data
 * @package App\Model\Data
 */
class Data {

    public static $CP = "CP";
    public static $FLAG = "Flag";

    private $qn = "QN";

    // PNUM
    private $pNum;

    // PNO
    private $pNo;

    // ST
    private $st;

    // CN
    private $cn;

    // PW
    private $pw;

    // MN
    private $mn;

    // List  Flag
    private $dataFlag;

    // CP CpData
    private $cp;

    public function getQn() {
        return $this->qn;
    }

    public function setQn($qn) {
        $this->qn = $qn;
    }

    public function getpNum() {
        return pNum;
    }

    public function setpNum($pNum) {
        $this->pNum = pNum;
    }

    public function getpNo() {
        return $this->pNo;
    }

    public function setpNo($pNo) {
        $this->pNo = $pNo;
    }

    public function getSt() {
        return $this->st;
    }

    public function setSt($st) {
        $this->st = $st;
    }

    public function getCn() {
        return $this->cn;
    }

    public function setCn($cn) {
        $this->cn = $cn;
    }

    public function getPw() {
        return $this->pw;
    }

    public function setPw($pw) {
        $this->pw = $pw;
    }

    public function getMn() {
        return $this->mn;
    }

    public function setMn($mn) {
        $this->mn = $mn;
    }

    public function getDataFlag() {
        return $this->dataFlag;
    }

    public function setDataFlag($dataFlag) {
        $this->dataFlag = $dataFlag;
    }

    public function getCp() {
        return $this->cp;
    }

    public function setCp($cp) {
        $this->cp = $cp;
    }

}
