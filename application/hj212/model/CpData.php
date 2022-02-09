<?php
namespace app\hj212\model;

/**
 * 数据区
 * Class CpData
 * @package App\Model\Data
 */
class CpData {

    public static $LIVESIDE = "LiveSide";
    public static $DEVICE = "Device";
    public static $POLLUTION = "Pollution";

    // SystemTime
    private $systemTime;

    // QN
    private $qn;

    // QnRtn
    private $qnRtn;

    // ExeRtn
    private $exeRtn;

    // RtdInterval
    private $rtdInterval;

    // MinInterval
    private $minInterval;

    // RestartTime
    private $restartTime;

    // AlarmTime
    private $alarmTime;

    // AlarmType
    private $alarmType;

    // ReportTarget
    private $reportTarget;

    // PolId
    private $polId;

    // BeginTime
    private $beginTime;

    // EndTime
    private $endTime;

    // DataTime
    private $dataTime;

    // ReportTime
    private $reportTime;

    // DayStdValue
    private $dayStdValue;

    // NightStdValue
    private $nightStdValue;

    // PNO
    private $pNo;

    // PNUM
    private $pNum;

    // PW
    private $pw;

    // NewPW
    private $newPW;

    // OverTime
    private $overTime;

    // ReCount
    private $reCount;

    // WarnTime
    private $warnTime;

    // Ctime
    private $cTime;


    // VaseNo
    private $vaseNo;

    // CstartTime
    private $cStartTime;

    // Stime
    private $sTime;

    // InfoId
    private $infoId;

    // Flag
    private $dataFlag;

    // Pollution
    private $pollution;

    // Device
    private $device;

    // LiveSide
    private $liveSide;


    public function getSystemTime() {
        return $this->$this->systemTime;
    }

    public function setSystemTime($systemTime) {
        $this->systemTime = $systemTime;
    }

    public function getQn() {
        return $this->qn;
    }

    public function setQn($qn) {
        $this->qn = qn;
    }

    public function getQnRtn() {
        return $this->qnRtn;
    }

    public function setQnRtn($qnRtn) {
        $this->qnRtn = qnRtn;
    }

    public function getExeRtn() {
        return $this->exeRtn;
    }

    public function setExeRtn($exeRtn) {
        $this->exeRtn = exeRtn;
    }

    public function getRtdInterval() {
        return $this->rtdInterval;
    }

    public function setRtdInterval($rtdInterval) {
        $this->rtdInterval = rtdInterval;
    }

    public function getMinInterval() {
        return $this->minInterval;
    }

    public function setMinInterval($minInterval) {
        $this->minInterval = minInterval;
    }

    public function getRestartTime() {
        return $this->restartTime;
    }

    public function setRestartTime($restartTime) {
        $this->restartTime = restartTime;
    }

    public function getPollution() {
        return $this->pollution;
    }

    public function setPollution($pollution) {
        $this->pollution = pollution;
    }

    public function getAlarmTime() {
        return $this->alarmTime;
    }

    public function setAlarmTime($alarmTime) {
        $this->alarmTime = alarmTime;
    }

    public function getAlarmType() {
        return $this->alarmType;
    }

    public function setAlarmType($alarmType) {
        $this->alarmType = alarmType;
    }

    public function getReportTarget() {
        return $this->reportTarget;
    }

    public function setReportTarget($reportTarget) {
        $this->reportTarget = reportTarget;
    }

    public function getPolId() {
        return $this->polId;
    }

    public function setPolId($polId) {
        $this->polId = polId;
    }

    public function getBeginTime() {
        return $this->beginTime;
    }

    public function setBeginTime($beginTime) {
        $this->beginTime = beginTime;
    }

    public function getEndTime() {
        return $this->endTime;
    }

    public function setEndTime($endTime) {
        $this->endTime = endTime;
    }

    public function getDataTime() {
        return $this->dataTime;
    }

    public function setDataTime($dataTime) {
        $this->dataTime = dataTime;
    }

    public function getReportTime() {
        return $this->reportTime;
    }

    public function setReportTime($reportTime) {
        $this->reportTime = reportTime;
    }

    public function getDayStdValue() {
        return $this->dayStdValue;
    }

    public function setDayStdValue($dayStdValue) {
        $this->dayStdValue = dayStdValue;
    }

    public function getNightStdValue() {
        return $this->nightStdValue;
    }

    public function setNightStdValue($nightStdValue) {
        $this->nightStdValue = nightStdValue;
    }

    public function getDataFlag() {
        return $this->dataFlag;
    }

    public function setDataFlag($dataFlag) {
        $this->dataFlag = dataFlag;
    }

    public function getpNo() {
        return $this->pNo;
    }

    public function setpNo($pNo) {
        $this->pNo = pNo;
    }

    public function getpNum() {
        return $this->pNum;
    }

    public function setpNum($pNum) {
        $this->pNum = pNum;
    }

    public function getPw() {
        return $this->pw;
    }

    public function setPw($pw) {
        $this->pw = pw;
    }

    public function getNewPW() {
        return $this->newPW;
    }

    public function setNewPW($newPW) {
        $this->newPW = newPW;
    }

    public function getOverTime() {
        return $this->overTime;
    }

    public function setOverTime($overTime) {
        $this->overTime = overTime;
    }

    public function getReCount() {
        return $this->reCount;
    }

    public function setReCount($reCount) {
        $this->reCount = reCount;
    }

    public function getWarnTime() {
        return $this->warnTime;
    }

    public function setWarnTime($warnTime) {
        $this->warnTime = warnTime;
    }

    public function getcTime() {
        return $this->cTime;
    }

    public function setcTime($cTime) {
        $this->cTime = cTime;
    }

    public function getVaseNo() {
        return $this->vaseNo;
    }

    public function setVaseNo($vaseNo) {
        $this->vaseNo = vaseNo;
    }

    public function getcStartTime() {
        return $this->cStartTime;
    }

    public function setcStartTime($cStartTime) {
        $this->cStartTime = cStartTime;
    }

    public function getsTime() {
        return $this->sTime;
    }

    public function setsTime($sTime) {
        $this->sTime = sTime;
    }

    public function getInfoId() {
        return $this->infoId;
    }

    public function setInfoId($infoId) {
        $this->infoId = infoId;
    }

    public function getDevice() {
        return $this->device;
    }

    public function setDevice($device) {
        $this->device = device;
    }

    public function getLiveSide() {
        return $this->liveSide;
    }

    public function setLiveSide($liveSide) {
        $this->liveSide = liveSide;
    }
}
