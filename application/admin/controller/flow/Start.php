<?php

namespace app\admin\controller\flow;

use app\common\controller\Backend;
use think\Db;
/**
 * 发起流程
 *
 * @icon fa fa-arrow-circle-o-right
 */
class Start extends Backend
{

    /**
     * Scheme模型对象
     * @var \app\admin\model\flow\Scheme
     */
    protected $model = null;
    protected $admin = null;
    protected $noNeedRight = ['*'];
    protected $runtime = null;
    protected $searchFields = 'id,flowcode,flowname';
    protected $resultSetType = 'collection';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\flow\Scheme;
        $this->admin = model('Admin');
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
        //当前是否为关联查询
        $this->relationSearch = false;
        $adminModel =$this->admin->get($this->auth->id);
        
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('flow_scheme')
                ->where($where)
                ->where("isenable", "1")
                ->where("status", "normal")
                //->where('FIND_IN_SET(:id,department_id)',['id'=>$adminModel["department_id"]])
                ->order($sort, $order)
                ->count();

            $list = Db::name('flow_scheme')
                ->field('id,flowcode,flowname,flowversion,weight,frmtype,department_id')
                ->where($where)
                ->where("isenable", "1")
                ->where("status", "normal")
                //->where('FIND_IN_SET(:id,department_id)',['id'=>$adminModel["department_id"]])
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
           
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 发起
     */
    public function start()
    {
        $this->redirect('/admin/flow/leave/add', ['ids' => 1]);
    }
}
