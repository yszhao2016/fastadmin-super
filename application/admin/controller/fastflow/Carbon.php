<?php

namespace app\admin\controller\fastflow;

use app\common\controller\Backend;
use fastflow\api;
use think\Loader;
use think\Session;

/**
 * 我的抄送
 *
 * @icon fa fa-circle-o
 */
class Carbon extends Backend
{

    protected $noNeedRight = ['bill', 'detail'];
    /**
     * Carbon模型对象
     * @var \app\admin\model\fastflow\Carbon
     */
    protected $model = null;

    public function _initialize()
    {
        $this->model = new \app\admin\model\fastflow\Carbon;
        parent::_initialize();
    }

    public function bill()
    {
        $billModel = new \app\admin\model\fastflow\flow\Bill;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $billModel
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $billModel
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as &$row) {
                $row['carbons'] = 0;
            }

            if ($total > 0) {
                $carbon_list = $this->model->where(['receiver_id' => Session::get()['admin']['id'], 'is_read' => 0])->select();
                foreach ($carbon_list as $item) {
                    for ($i = 0; $i < count($list); $i++) {
                        if ($item['bill'] == $list[$i]['bill_table']) {
                            $list[$i]['carbons'] += 1;
                            break;
                        }
                    }
                }
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch('index');
    }

    /**
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
                ->where('receiver_id', Session::get()['admin']['id'])
                ->order($sort, $order)
                ->paginate($limit);

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 查看详情
     */
    public function detail()
    {
        $params = input('');
        if (!isset($params['id'])) {
            return '参数错误';
        }
        $id = $params['id'];
        $row = $this->model->find($id);
        if (!$row){
            return '未查询到抄送数据';
        }

        $row['is_read'] = 1;
        $row->save();

        $api = new api();
        $thread_logs = $api->getLogByIds($row['bill'], $row['bill_id'], explode(',', $row['thread_log_ids']));
        if (!$thread_logs){
            return '未查询到审批数据';
        }
        $this->assignconfig([
            'bill_id' => $row['bill_id'],
            'controller_url' => $this->getBillControllerUrl($row['bill']),
        ]);
        $this->view->assign(['logs' => $thread_logs]);
        return $this->view->fetch();
    }

    private function getBillControllerUrl($bill)
    {
        $flowBillRow = (new \app\admin\model\fastflow\flow\Bill())->where(['bill_table' => $bill])->find();
        $url = Loader::parseName($flowBillRow['controller'], 0);
        $url = str_replace('/_', '/', $url);
        return $url;
    }


}
