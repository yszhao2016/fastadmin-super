<?php


namespace app\admin\controller\fastflow\bill;
use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use app\common\controller\Backend;
use fastflow\api;
use think\Config;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Session;

class Common extends Backend
{
    protected $noNeedRight = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->view->assign('admin_id', \think\Session::get()['admin']['id']);
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
            $items = $list->items();

            $bill = \think\Config::get('database')['prefix'] . $this->model->getName();
            if ($this->request->request('show') == 'cancheck') {
                foreach ($items as $key => $item) {
                    $bill_auth = (new \fastflow\api)->getBillAuthInfo($item['admin_id'], $bill, $item['id'])['code'];
                    if ($bill_auth != 0 && $bill_auth != 1) {
                        unset($items[$key]);
                        $total -= 1;
                    }
                }
                $items = array_values($items);
            }
            if ($this->request->request('show') == 'mine') {
                foreach ($items as $key => $item) {
                    if ($item['admin_id'] != \think\Session::get()['admin']['id']) {
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
        $api = new api();
        $bill = Config::get('database')['prefix'] . $this->model->getName();
        $groupAccess = AuthGroupAccess::where('uid', Session::get()['admin']['id'])->find();
        $groupId = $groupAccess['group_id'];
        $group = AuthGroup::find($groupId);
        foreach (explode(',', $ids) as $id) {
            $billRow = $api->getBillRow($bill, $id);
            if ($billRow) {
                if ($billRow['admin_id'] != Session::get()['admin']['id'] && $group['pid'] != 0) {
                    return ['code' => 0, 'msg' => '您没有权限删除该单据'];
                }
            }
        }
        $api->delBill($bill, explode(',', $ids));
        parent::del($ids);
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $can_edit_fields = [];
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $api = new api();
        $isSuperManager = false;
        $admin_id = Session::get()['admin']['id'];
        $groupAccess = AuthGroupAccess::where('uid', $admin_id)->select();
        foreach ($groupAccess as $access) {
            $groupId = $access['group_id'];
            $group = AuthGroup::find($groupId);
            if ($group['pid'] == 0) {
                $isSuperManager = true;
            }
        }
        $bill = Config::get('database')['prefix'] . $this->model->getName();
        if(input('threadid') == null){
            if (!$isSuperManager){
                $bill_process = $api->getProcessByBill($bill, $row['id']);
                if ($bill_process) {
                    if(input('way') == 'detail'){
                        $can_edit_fields = [];
                    }
                    else{
                        $this->error('流程审批中，您不能直接编辑该单据', '');
                    }
                }
                else{
                    $agency_info = $api->checkAgency($bill, $row['id'], 1, [$row['admin_id']]);
                    if ($agency_info) {
                        if (!in_array($admin_id, $agency_info['agency_workers']) && !$isSuperManager) {
                            $this->error('您不是超级管理员或代理人，无权限编辑该单据', '');
                        }
                        else {
                            $can_edit_fields = true;
                        }
                    }
                    else {
                        if ($row['admin_id'] != Session::get()['admin']['id'] && !$isSuperManager) {
                            $this->error('您不是超级管理员或流程发起人，暂无权限编辑该单据', '');
                        }
                        else {
                            $can_edit_fields = true;
                        }
                    }
                }
            }
            else{
                $can_edit_fields = true;
            }
        }
        else{
            $can_edit_fields = (new \fastflow\fa\BillAuth())->getCanEditFields($bill, $row['id'],input('threadid'));
        }

        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }

                    $result = $row->allowField($can_edit_fields === true ? true : implode(',', $can_edit_fields))->save($params);
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
                }
                else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $bill_fileds = $api->getFieldsWithComment($bill);
        $fields = [];
        foreach ($bill_fileds as $bill_filed) {
            $fields[] = $bill_filed['field'];
        }
        if ($can_edit_fields === true) {
           $can_edit_fields = $fields;
        }
        $this->assignconfig(['billFileds' => $fields, 'canEditFields' => $can_edit_fields]);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

}