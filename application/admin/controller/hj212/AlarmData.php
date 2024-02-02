<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/2/1
 */

namespace app\admin\controller\hj212;


use app\common\controller\Backend;

class AlarmData extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\hj212\AlarmData;

    }


    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                ->alias('a')
                ->field('a.id,a.data_id,qn,cn,mn,cp_datatime,site_name,a.created_at as created_at')
                ->join('hj212_device d', 'a.mn=d.device_code', 'left')
                ->join('hj212_site s', 'd.site_id=s.id', 'left')
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

}