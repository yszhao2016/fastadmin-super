<?php

namespace app\admin\controller\flow;

use app\common\controller\Backend;
use think\Db;

/**
 * 已办任务
 *
 * @icon fa fa-gavel
 */
class Finishworkitem extends Backend
{
    protected $noNeedRight = ['*'];
    protected $searchFields = 'id,bizobjectid,flowcode,instancecode,flowname';

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            //这里不做通用查询需要一个一个解析条件
            $total = Db::name('view_flow_workitem')
                ->where('status', '2')
                ->where('receiveid', $this->auth->id)
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = Db::name('view_flow_workitem')
                ->where('status', '2')
                ->where('receiveid', $this->auth->id)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}
