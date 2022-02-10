<?php
namespace app\hj212\segment\converter;

use ReflectionProperty;
use Symfony\Component\VarExporter\Internal\Reference;

class DataConverter
{
    private $data;
    
    public function __construct($data)
    {
        //数据段
        $this->data = $data;
    }  
    /**
                 * 分割 数据段
     */
    public function explodeData()
    {
        //切割CP数据区
        $result = substr($this->data, 0, strrpos($this->data, 'CP')-1);
        //根据分号分割数据
        $fields = explode(';',$result);
        $data = array();
        foreach($fields as $item){
            //以等号进行分割
            $info = explode('=',$item);
            $list = $info[1];
            if(strtolower($info[0]) == 'flag'){
                $list = $this->explodeFlag($list);
            }
            $data[strtolower($info[0])] = $list;
        }
        return $data;
    }
    
    /**
             * 分割数据段标志
     */
    public function explodeFlag($list)
    {
        //反射拿到Pollution类
        $class = new \ReflectionClass('app\hj212\model\DataFlag');
        
        //获取对象实例
        $obj = $class->newInstance();
        
        //获取属性对象
        $attribute = $class->getProperty('bit');
        //获取方法
        $methods = $class->getMethod('isMarked');
        
        $methods->setAccessible(true);
        $attribute->setAccessible(true);
        //D
        $attribute->setValue($obj, 2);
        
        $flag['D'] = $methods->invokeArgs($obj, [$list]) ? '1' : '0';
        //A
        $attribute->setValue($obj, 1);
        $flag['A'] = $methods->invokeArgs($obj, [$list]) ? '1' : '0';
        
        return json_encode($flag);
    }
    
    /**
              * 分割数据区
     */
    public function explodeCpData()
    {
        //获取指令参数CP数据区
        $result = substr($this->data, stripos($this->data,'CP=&&')+5, -2);
        //根据分号分割数据区
        $fields = explode(';',$result);
        $cpData = array();
        $pollution = array();
        foreach($fields as $item){
            //以逗号进行分割
            $arr= explode(',',$item);
            foreach($arr as $v1){
                $info = explode('=',$v1);
                if(strpos($info[0],'-') !== false){
                    //污染因子
                    $pollution[$info[0]] = $info[1];
                }else{
                    $cpData[strtolower($info[0])] = $info[1];
                }
                
            }
        }
        //污染因子
        $pollutions = $this->explodePollution($pollution);
        
        return [
            //数据区
            'cpData' => $cpData,
            //污染因子
            'pollution'=>$pollutions['pollution'],
            //设备
            'device' => $pollutions['device'],
            //现场端
            'liveSide' => $pollutions['liveside'],
        ];
    }
    
    /**
             * 对污染因子进行分割
     */
    public function explodePollution($data)
    {
        $pollution = array();
        $liveSide = array();
        $device = array();
        foreach($data as $k=>$v){
            //根据-进行分割
            $info = explode('-',$k);
                               
            if($info[1] == 'Info'){
                //现场端 LiveSide
                $liveSide[$info[0]]['info'] = $v; 
            }else if($info[1] == 'SN'){
                //在线监控（监测）仪器仪表编码 
                $liveSide[$info[0]]['sn'] = $v;
            }else if(substr($info[0],0,2) == 'SB' && $info[1] == 'RS'){
                //RS 污染治理设施运行状态的实时采样值
                $device[$info[0]]['rs'] = $v;
            }else if(substr($info[0],0,2) == 'SB' && $info[1] == 'RT'){
                //RT 污染治理设施一日内的运行时间
                $device[$info[0]]['rt'] = $v;
            }else{
                $pollution[$info[0]][strtolower($info[1])] = $v;
            }
        }
        
        return [
            'pollution'=>$pollution,
            'liveside' => $liveSide,
            'device' => $device,
        ];
    }
}