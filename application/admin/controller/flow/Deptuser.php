<?php

namespace app\admin\controller\flow;

use app\common\controller\Backend;
use fast\Tree;
use think\Db;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Deptuser extends Backend
{
    /**
     * Department模型对象
     * @var \app\admin\model\flow\Department
     */
    protected $model = null;
    protected $userModel = null;
    //当前组别列表数据
    protected $groupdata = [];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\flow\Department;
        $this->userModel = new \app\admin\model\Admin;
        $all = collection($this->model->select())->toArray();
        foreach ($all as $k => $v) {
            $state = ['opened' => true];

            $channelList[] = [
                'id' => $v['id'],
                'parent' => $v['pid'] ? $v['pid'] : '#',
                'text' => __($v['name']),
                'type' => 'dept',
                'state' => $state
            ];
        }
        $this->assignconfig('deptList', $channelList);
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    public function getuserbydept()
    {
        if ($this->request->isAjax()) {
            $dept_id = $this->request->request('department_id');

            $deptList=$this->model->select();
            $deptName = [];
            foreach ($deptList as $k => $v)
            {
                $deptName[$v['id']] =$v['name'];
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->userModel
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $total = $this->userModel
                ->where($where)
                ->order($sort, $order)
                ->count();
            foreach ($list as $k => $v)
            {
                 $v['dept_name']=isset($deptName[$v['department_id']])?$deptName[$v['department_id']]:'';
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
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
                    $result = $this->userModel->allowField(true)->save(['department_id' => $params['department_id']], ['id' => $params['id']]);                  
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

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->userModel->get($ids);      
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $depts = $this->request->post("departments/a");
            if ($params) {
                $result = false;
                Db::startTrans();
                try {
                    $result = $row->allowField(true)->save($params);
                    $isExsit= Db::name('flow_right')->where(['type'=>'dept','key'=>$ids])->find();
                    $admin = $this->userModel->get($ids);
                    $deptId= $admin['department_id'];

                    $dataset = '';
                    foreach ($depts as $value)
                    {
                        $dataset = $dataset.$value.',';
                    }
                    if($deptId && $dataset){
                        if(strpos($dataset,strval($deptId))<0){
                            $dataset= $dataset.$deptId;
                        }                      
                    }
                    if($isExsit){
                        Db::name('flow_right')->where(['type'=>'dept','key'=>$ids])->update(['value'=>$dataset]);
                    }else{
                        Db::name('flow_right')->insert(['type'=>'dept','key'=>$ids,'value'=>$dataset]);
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
        //获取部门生成树
        $groupList = collection($this->model->select())->toArray();
        Tree::instance()->init($groupList);
        $result = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
        $groupName = [];
        foreach ($result as $k => $v) {
            $groupName[$v['id']] = $v['name'];
        }
        //获取已授权部门
        $already = Db::name('flow_right')->where(['key'=>$ids,'type'=>'dept'])->find();
        //$depts = [];
        //foreach ($already as $k => $v)
        //{
        //    $depts[] = $v['value'];
        //}
        $this->groupdata = $groupName;
        $this->view->assign('depts', $already['value']);
        $this->view->assign('groupdata', $groupName);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
