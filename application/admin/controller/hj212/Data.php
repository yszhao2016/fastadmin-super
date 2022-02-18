<?php

namespace app\admin\controller\hj212;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\hj212\segment\converter\DataReverseConverter;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Data extends Backend
{

    /**
     * Data模型对象
     * @var \app\admin\model\hj212\Data
     */
    protected $model = null;

    public function _initialize()
    {

        parent::_initialize();
        $this->model = new \app\admin\model\hj212\Data;
        $this->view->assign("statusList", $this->model->getStatusList());
        $data_id = $this->request->param('data_id', 0);
        $this->assignconfig('data_id', $data_id);


    }


    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
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
                    //获取设备密码
                    $device = \app\admin\model\hj212\Device::where('device_code',$params['mn'])->field('device_pwd')->find();
                    $params['pw'] = isset($device->device_pwd) ? $device->device_pwd : '';
                    //获取当前时间
                    $currentDate = date('YmdHis',time());
                    $params['cp_datatime'] = $currentDate;
                    
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
                    $data = new DataReverseConverter();
                    
                    //获取设备密码
                    $device = \app\admin\model\hj212\Device::where('device_code',$params['mn'])->field('device_pwd')->find();
                    print_r($params);die;
                    $params['pw'] = isset($device->device_pwd) ? $device->device_pwd : '';
                    //获取当前时间
                    $currentDate = date('YmdHis',time());
                    $params['cp_datatime'] = $currentDate;
                    
                    $result = $row->allowField(true)->save($params);
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
              * 数据分析
     */
    public function analysisdata()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        $Id = $this->request->param('data_id', 0);
        
        $list =$this->model
        ->where(function ($query) use ($Id) {
            if ($Id) {
                $query->where('id', $Id);
            }
        })
        ->find();
        $site = '';
        $pollutionInfo = array();
        if($list){
            $device_code = $list['mn'];
            $site = Db::name('hj212_device')->alias("device")
            ->join("fa_hj212_site site", "site.id = device.site_id")
            ->where(['device.device_code'=>$device_code])
            ->find();
            if($site){
                $site['mn'] = $device_code;
            }
            
            //获取检测因子信息
            $pollutionInfo = Db::name('hj212_pollution')->alias('p')
            ->join("hj212_pollution_code c","c.code = p.code","LEFT")
            ->join("hj212_alarm a","a.code = c.code","LEFT")
            ->where(['p.data_id'=>$list['id']])
            ->select();
            
        }
        
        $this->view->assign('pollutionsite', $site);
        $this->view->assign('pollutionInfo', $pollutionInfo);
        
        return $this->view->fetch();
    }

}
