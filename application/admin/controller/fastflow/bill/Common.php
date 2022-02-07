<?php


namespace app\admin\controller\fastflow\bill;

use app\admin\controller\fastflow\flow\CustomMessage;
use app\common\controller\Backend;
use fastflow\api;
use think\Config;
use think\Session;

class Common extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->view->assign('uid', Session::get()['admin']['id']);
    }

    /*
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            $total = $list->total();
            $bill = Config::get('database')['prefix'] . $this->model->getName();
            $items = $list->items();
            if ($this->request->request('show')) {
                foreach ($items as $key => $item) {
                    $bill_auth = (new api())->getBillAuthInfo($item['uid'], $bill, $item['id'])['code'];
                    if ($bill_auth != 0 && $bill_auth != 1) {
                        unset($items[$key]);
                        $total -= 1;
                    }
                }
                $items = array_values($items);
            }
            $result = array("total" => $total, "rows" => $items);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        $bill = Config::get('database')['prefix'] . $this->model->getName();
        (new api())->delBill($bill, explode(',', $ids));
        parent::del($ids);
    }


}