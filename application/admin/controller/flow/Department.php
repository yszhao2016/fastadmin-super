<?php

namespace app\admin\controller\flow;

use app\common\controller\Backend;
use fast\Tree;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Department extends Backend
{

    /**
     * Department模型对象
     * @var \app\admin\model\flow\Department
     */
    protected $model = null;
    protected $userModel = null;
    protected $noNeedRight = ['*'];
    //当前组别列表数据
    protected $groupdata = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\flow\Department;
        $this->userModel = new \app\admin\model\Admin;
        $groupList = collection($this->model->select())->toArray();

        Tree::instance()->init($groupList);
        $result = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
        $groupName = [];
        foreach ($result as $k => $v) {
            $groupName[$v['id']] = $v['name'];
        }
        $this->groupdata = $groupName;
        $this->view->assign('groupdata', $groupName);
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

        if ($this->request->isAjax()) {

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();


            $groupList = [];
            foreach ($list as $k => $v) {
                $groupList[$v['id']] = $v;
            }
            $list = [];
            foreach ($this->groupdata as $k => $v) {
                if (isset($groupList[$k])) {
                    $groupList[$k]['name'] = $v;
                    $list[] = $groupList[$k];
                }
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $result = false;
                Db::startTrans();
                try {
                    if ($params['manager']) {
                        $manager = $this->userModel->get($params['manager']);
                        $params['managername'] = $manager->username;
                    }
                    else{
                        $params['managername'] = '';
                    }
                    $res = Db::name('flow_department')->where('pid ='.$params['pid'])->order('code desc')->find();
                    if(count($res)>0){
                        $arr =explode(".",$res['code']);
                        $arr[count($arr)-1] = $arr[count($arr)-1] +1;
                        $params['code'] = implode('.',$arr);
                    }else{
                        $params['code']=$params['pid'].'.1';
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
        $groupList = collection($this->model->select())->toArray();

        Tree::instance()->init($groupList);
        $result = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
        $groupName = [];
        foreach ($result as $k => $v) {
            $groupName[$v['id']] = $v['name'];
        }
        $this->view->assign('groupdata', $groupName);
        return $this->view->fetch();
    }

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
                    if ($params['manager']) {
                        $manager = $this->userModel->get($params['manager']);
                        $params['managername'] = $manager->username;
                    }
                    else{
                        $params['managername'] = '';
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
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
