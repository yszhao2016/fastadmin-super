<?php

namespace app\index\controller\cms;

use app\common\controller\Frontend;

/**
 * 我的消费订单
 */
class Order extends Frontend
{
    protected $layout = 'default';
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 我的消费订单
     */
    public function index()
    {
        $user_id = $this->auth->id;
        $orderList = \addons\cms\model\Order::with(['archives'])->where('user_id', $user_id)
            ->where('status', 'paid')
            ->order('id', 'desc')
            ->paginate(10, null);

        $this->view->assign('config', array_merge($this->view->config, ['jsname' => '']));
        $this->view->assign('orderList', $orderList);
        $this->view->assign('title', '我的消费订单');
        return $this->view->fetch();
    }

}
