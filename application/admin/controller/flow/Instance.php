<?php

namespace app\admin\controller\flow;

use app\common\controller\Backend;
use think\Db;

/**
 * 实例管理
 *
 * @icon   fa fa-apple
 */
class Instance extends Backend
{
    protected $model = null;
    protected $noNeedRight = ['*'];
    protected $searchFields = 'id,bizobjectid,instancecode,nickname';

    public function _initialize()
    {
        $this->model = new \app\admin\model\flow\Instance();
        parent::_initialize();
    }

    /**
     * 查看
     */
    public function index()
    {
        $this->loadlang('general/attachment');
        $this->loadlang('general/crontab');
        return $this->view->fetch();
    }

    /**
     * 实例管理
     */
    public function table1()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('view_flow_instance')
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = Db::name('view_flow_instance')
                ->where($where)
                ->order("createtime", "desc")
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch('index');
    }

    /**
     * 任务转移
     */
    public function table2()
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('view_flow_workitem')
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = Db::name('view_flow_workitem')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch('index');
    }

    /**
     * 转移
     */
    public function trans()
    {
        $id = $this->request->request('ids');
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                Db::name('flow_task')->where('id', $id)
                    ->update(["receiveid" => $params['userid']]);
            }
            $this->success();
        }
        $this->assignconfig("taskid", $id);
        return $this->view->fetch();
    }
}
