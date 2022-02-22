<?php

namespace app\hj212\model;

use app\admin\model\cms\Bank;
use app\admin\model\hj212\Alarm;
use app\admin\model\hj212\PollutionCode;
use think\Model;

/**
 * 污染因子
 * Class Pollution
 * @package App\Model
 */
class Pollution extends Model
{

    // 表名
    protected $name = 'hj212_pollution';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    // SampleTime 污染物采样时间
    private $sampleTime;

    // Rtd 污染物实时采样数据
    private $rtd;

    // Min 污染物指定时间内最小值
    private $min;

    // Avg 污染物指定时间内平均值
    private $avg;

    // Max 污染物指定时间内最大值
    private $max;

    // ZsRtd 污染物实时采样折算数据
    private $zsRtd;

    // ZsMin 污染物指定时间内最小折算值
    private $zsMin;

    // ZsAvg 污染物指定时间内平均折算值
    private $zsAvg;

    // ZsMax 污染物指定时间内最大折算值
    private $zsMax;

    // Flag 监测污染物实时数据标记
    private $flag;

    // EFlag 监测仪器扩充数据标记
    private $eFlag;

    // Cou 污染物指定时间内累计值
    private $cou;

    // RS 设备运行状态的实时采样值
    private $rs;

    // RT 设备指定时间内的运行时间
    private $rt;

    // Ala 污染物报警期间内采样值
    private $ala;

    // UpValue 污染物报警上限值
    private $upValue;

    // LowValue 污染物报警下限值
    private $lowValue;

    // Data 噪声监测日历史数据
    private $zdata;

    // DayData 噪声昼间历史数据
    private $dayData;

    // NightData 噪声夜间历史数据
    private $nightData;

    public function Alarm()
    {
        return $this->hasOne(Alarm::class, 'code', 'code', [], 'LEFT')->setEagerlyType(0);

    }

    public function Info()
    {
        return $this->hasOne(PollutionCode::class, 'code', 'code')->setEagerlyType(0);

    }

    function getSampleTime()
    {
        return $this->sampleTime;
    }

    function setSampleTime($sampleTime)
    {
        $this->sampleTime = $sampleTime;
    }

    function getRtd()
    {
        return $this->rtd;
    }

    function setRtd($rtd)
    {
        $this->rtd = $rtd;
    }

    function getMin()
    {
        return $this->min;
    }

    function setMin($min)
    {
        $this->min = $min;
    }

    function getAvg()
    {
        return $this->avg;
    }

    function setAvg($avg)
    {
        $this->avg = $avg;
    }

    function getMax()
    {
        return $this->max;
    }

    function setMax($max)
    {
        $this->max = $max;
    }

    function getZsRtd()
    {
        return $this->zsRtd;
    }

    function setZsRtd($zsRtd)
    {
        $this->zsRtd = $zsRtd;
    }

    function getZsMin()
    {
        return $this->zsMin;
    }

    function setZsMin($zsMin)
    {
        $this->zsMin = $zsMin;
    }

    function getZsAvg()
    {
        return $this->zsAvg;
    }

    function setZsAvg($zsAvg)
    {
        $this->zsAvg = $zsAvg;
    }

    function getZsMax()
    {
        return $this->zsMax;
    }

    function setZsMax($zsMax)
    {
        $this->zsMax = $zsMax;
    }

    function getFlag()
    {
        return $this->flag;
    }

    function setFlag($flag)
    {
        $this->flag = $flag;
    }

    function geteFlag()
    {
        return $this->eFlag;
    }

    function seteFlag($eFlag)
    {
        $this->eFlag = $eFlag;
    }

    function getCou()
    {
        return $this->cou;
    }

    function setCou($cou)
    {
        $this->cou = $cou;
    }

    function getRs()
    {
        return $this->rs;
    }

    function setRs($rs)
    {
        $this->rs = $rs;
    }

    function getRt()
    {
        return $this->rt;
    }

    function setRt($rt)
    {
        $this->rt = $rt;
    }

    function getAla()
    {
        return $this->ala;
    }

    function setAla($ala)
    {
        $this->ala = $ala;
    }

    function getUpValue()
    {
        return $this->upValue;
    }

    function setUpValue($upValue)
    {
        $this->upValue = $upValue;
    }

    function getLowValue()
    {
        return $this->lowValue;
    }

    function setLowValue($lowValue)
    {
        $this->lowValue = $lowValue;
    }

    public function getZdata()
    {
        return $this->zdata;
    }

    function setData($data)
    {
        $this->zdata = $data;
    }

    function getDayData()
    {
        return $this->dayData;
    }

    function setDayData($dayData)
    {
        $this->dayData = $dayData;
    }

    function getNightData()
    {
        return $this->nightData;
    }

    function setNightData($nightData)
    {
        $this->nightData = $nightData;
    }
}
