<?php

namespace app\admin\controller\hj212;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Pollution extends Backend
{

    /**
     * Pollution模型对象
     * @var \app\admin\model\hj212\Pollution
     */
    protected $model = null;
    protected $codelist = array();

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\hj212\Pollution;
        $data_id = $this->request->param('data_id', 0);
        $this->assignconfig('data_id', $data_id);

        //检测因子信息
        $list = Db::name('hj212_pollution_code')->select();
        $codeArr = collection($list)->toArray();

        $arr = array();
        foreach($codeArr as $v){
           $arr[$v['code']] = $v['name'];
        }
        $this->codelist = $arr ;
        $this->assignconfig('codelist', $this->codelist);

        $ids = $this->request->param('ids',0);
        if($ids){
            //获取检测报警信息
            $alarmInfo =  $this->model->with('Alarm')->find($ids);
            $codeInfo =  $this->model->with('PollutionCode')->find($ids);

            $this->assign('alarm',$alarmInfo['alarm']);
            $this->assign('code',$alarmInfo['pollutioncode']);
        }
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        $data_id = $this->request->param('data_id', 0);
        $time = $this->request->param('time', date("Ym"));
        if(trim($time," ") =="null"){
            $time = date("Ym");
        }
        $suffix= substr($time, 0, 6);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
//            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = Db::name("hj212_pollution_".$suffix)
                ->alias("p")
                ->field("data_id,p.id,c.name,c.code,min,max,avg,is_alarm")
                ->join('hj212_pollution_code c', "p.code=c.code", "left")
                ->where('data_id',$data_id)
                ->order("id", "desc")
                ->paginate(50);
            $rows = $list->items();
            $result = array("total" => $list->total(), "rows" => $rows);
            
            return json($result);
        }
        return $this->view->fetch();
        parent::index();
    }
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $data_id = $this->request->param('data_id',0);
        $this->view->assign("data_id", $data_id);
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    if($result !== false){
                        //更新hj212_data
                        Db::name("hj212_data")
                            ->where(['id'=>$params['data_id']])
                            ->update(['is_change'=>1]);
                    }
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 获取报警信息
     * @return void
     */
    public function getAlarm()
    {
        try{
            if($this->request->isAjax()){
                $code = $this->request->param('code',0);
                $alarm = Db::name("hj212_alarm")->where(['code'=>$code])->find();
                $code = Db::name('hj212_pollution_code')->where(['code'=>$code])->find();
                if($code){
                    $alarm['measures'] = $code['measures'];
                }else{
                    $alarm['measures'] = '';
                }
                return json(['code'=>0, 'data'=>$alarm, 'msg'=>'']);
            }
        }catch(\Exception $e){
            return json(['code'=>1,'msg'=>$e->getMessage()]);
        }
    }
}
