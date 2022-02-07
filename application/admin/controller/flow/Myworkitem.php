<?php

namespace app\admin\controller\flow;

use app\common\controller\Backend;
use think\Db;

/**
 * 待办任务
 *
 * @icon fa fa-circle-o
 */
class Myworkitem extends Backend
{
    protected $noNeedRight = ['*'];
    protected $searchFields = 'id,bizobjectid,schemeid,flowcode,instancecode,flowname';

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        list($where, $sort, $order, $offset, $limit) = $this->buildparams();
        if ($this->request->isAjax()) {
            $total = Db::name('view_flow_workitem')
                ->where('status', '0')
                ->where($where)
                ->where('receiveid='.$this->auth->id.' or delegateid='.$this->auth->id)
                ->order($sort, $order)
                ->count();
            $list = Db::name('view_flow_workitem')
                ->where('status', '0')
                ->where($where)
                ->where('receiveid='.$this->auth->id.' or delegateid='.$this->auth->id)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assignconfig('adminId', $this->auth->id);
        return $this->view->fetch();
    }
}
