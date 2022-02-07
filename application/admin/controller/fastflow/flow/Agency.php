<?php

namespace app\admin\controller\fastflow\flow;

use app\common\controller\Backend;
use fastflow\api;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 委托代理
 *
 * @icon fa fa-handshake-o
 */
class Agency extends Backend
{

    /**
     * Agency模型对象
     * @var \app\admin\model\fastflow\flow\Agency
     */
    protected $model = null;
    protected $noNeedRight = ['getBillIds', 'getSelectpageWorkers'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\fastflow\flow\Agency;
        $bills = (new \app\admin\model\fastflow\flow\Bill())->select();
        $this->view->assign("rangeList", $this->model->getRangeList());
        $this->view->assign("ScopeList", $this->model->getScopeList());
        $this->view->assign(['bills' => $bills]);
    }

    public function import()
    {
        parent::import();
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
                if ($params['principal_id'] == $params['agent_id']) {
                    return ['code' => 0, 'msg' => '被代理人(分组)和代理人(分组)不能相同'];
                }
                if ($params['range'] == 1) {
                    if (!isset($params['bill'])) {
                        return ['code' => 0, 'msg' => '单据不能为空'];
                    }
                    if (!isset($params['bill_id'])) {
                        return ['code' => 0, 'msg' => '单据id不能为空'];
                    }
                }
                if ($params['range'] == 2) {
                    if (!isset($params['bill'])) {
                        return ['code' => 0, 'msg' => '单据不能为空'];
                    }
                    $params['bill_id'] = '';
                }
                if ($params['range'] == 3) {
                    $params['bill'] = '';
                    $params['bill_id'] = '';
                }

                $where = [
                    'range' => $params['range'],
                    'bill' => $params['bill'],
                    'bill_id' => $params['bill_id'],
                    'principal_id' => $params['principal_id'],
                    'scope' => $params['scope'],
                ];
                if (count($this->model->where($where)->select()) > 0) {
                    return ['code' => 0, 'msg' => '被代理人已经存在代理，不能重复添加'];
                }

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
                }
                else {
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
                if ($params['range'] == 1) {
                    if (!isset($params['bill'])) {
                        return ['code' => 0, 'msg' => '单据不能为空'];
                    }
                    if (!isset($params['bill_id'])) {
                        return ['code' => 0, 'msg' => '单据id不能为空'];
                    }
                }
                if ($params['range'] == 2) {
                    if (!isset($params['bill'])) {
                        return ['code' => 0, 'msg' => '单据不能为空'];
                    }
                    $params['bill_id'] = '';
                }
                if ($params['range'] == 3) {
                    $params['bill'] = '';
                    $params['bill_id'] = '';
                }

                $where = [
                    'range' => $params['range'],
                    'bill' => $params['bill'],
                    'bill_id' => $params['bill_id'],
                    'principal_id' => $params['principal_id'],
                    'scope' => $params['scope'],
                ];
                $rows = $this->model->where($where)->select();
                for ($i = 0; $i < count($rows); $i++) {
                    if ($rows[$i]['id'] == $row['id']) {
                        unset($rows[$i]);
                        break;
                    }
                }
                if (count($rows) > 0) {
                    return ['code' => 0, 'msg' => '被代理人已经存在代理，不能重复添加'];
                }
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
                }
                else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        $this->assignconfig('bill_id', $row['bill_id']);
        return $this->view->fetch();
    }

    /*
    * 获取单据所有数据ID
    */
    public function getBillIds($bill)
    {
        if ($bill == '') {
            return ['code' => 0, 'msg' => '单据为空，请先创建单据'];
        }
        $rows = (new api())->getRunningBillRows($bill);
        $ids = [];
        if ($rows) {
            foreach ($rows as $row) {
                $name = '';
                if (isset($row['name'])) {
                    $name = $row['name'];
                }
                elseif (isset($row['titile'])){
                    $name = $row['titile'];
                }
                $ids[] = ['id' => $row['id'], 'name' => $name];
            }
        }
        return ['code' => 1, 'msg' => '获取成功', 'data' => $ids];
    }

    public function getSelectpageWorkers()
    {
        return (new api())->getSelectpageWorkers();
    }


}
