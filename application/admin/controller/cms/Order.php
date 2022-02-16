<?php

namespace app\admin\controller\cms;

use app\common\controller\Backend;

/**
 * 订单管理
 *
 * @icon fa fa-cny
 */
class Order extends Backend
{

    /**
     * Order模型对象
     * @var \app\admin\model\cms\Order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\cms\Order;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $this->relationSearch = true;
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['user', 'archives'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['user', 'archives'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $item) {
                $item->user->visible(['id', 'username', 'nickname', 'avatar']);
                $item->archives->visible(['id', 'title', 'diyname']);
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }
}
