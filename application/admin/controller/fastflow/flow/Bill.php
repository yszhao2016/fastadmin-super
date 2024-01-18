<?php

namespace app\admin\controller\fastflow\flow;

use app\admin\model\AuthRule;
use app\admin\model\fastflow\Carbon;
use app\admin\model\fastflow\flow\BillAuth;
use app\common\controller\Backend;
use think\Config;
use think\console\Input;
use think\Db;
use think\Exception;
use think\Loader;
use fastflow\api;
use think\Session;

/**
 * 单据管理
 *
 * @icon fa fa-clipboard
 */
class Bill extends Backend
{

    /**
     * Bill模型对象
     * @var \app\admin\model\fastflow\flow\Bill
     */
    protected $model = null;
    protected $noNeedRight = ['getBadge', 'getFieldsWithComment', 'getAuthSelectOptionData', 'getSelectpageWorkers'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\fastflow\flow\Bill;

    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isGet()) {
            $tables = Db::query("select TABLE_NAME as name,TABLE_COMMENT as comment from information_schema.tables where table_schema='" . Config::get('database')['database'] . "'");
            $this->view->assign("tables", $tables);
            return $this->view->fetch();
        }
        elseif ($this->request->isPost()) {
            $params = input('');
            $must_fields = ['admin_id', 'status'];
            $table = $params['table'];
            if ($this->model->where(['bill_table' => $table])->find()) {
                return ['code' => 0, 'msg' => $table . '表已存在对应单据，不能重复创建'];
            }
            $table_fields_width_comment = (new api())->getFieldsWithComment($table);
            $table_fields = [];
            foreach ($table_fields_width_comment as $fc) {
                $table_fields[] = $fc['field'];
            }
            foreach ($must_fields as $must_field) {
                if (!in_array($must_field, $table_fields)) {
                    return ['code' => 0, 'msg' => '数据表字段必须包含' . $must_field . '字段'];
                }
            }

            $params['isrelation'] = 0;
            if (isset($params['relation'])) {
                $params['isrelation'] = 1;
            }
            $params['local'] = 1;
            $params['delete'] = 0;
            $params['force'] = 0;
            $prefix = Config::get('database')['prefix'];
            if ($params['controller'] == '') {
                $params['controller'] = 'fastflow/bill/' . Loader::parseName(str_replace($prefix, '', $params['table']), 1);
            }
            if (!$this->command($params, 'execute')) {
                return ['code' => 0, 'msg' => '执行错误，请检查是否已经存在对应控制器'];
            }
            $menu_params = [
                'commandtype' => 'menu',
                'allcontroller' => 0,
                'delete' => 0,
                'force' => 0,
                'controllerfile_text' => '',
                'controllerfile' => $params['controller'],
                'icon' => $params['icon'],
            ];
            if (!$this->command($menu_params, 'execute')) {
                return ['code' => 0, 'msg' => '生成菜单错误'];
            }
            $bill_rule = (new AuthRule())->where('name', 'fastflow/bill')->find();
            if ($bill_rule) {
                $bill_rule->ismenu = 1;
                $bill_rule->status = 'normal';
                $bill_rule->save();
            }
            $tables_comments = Db::query("select TABLE_NAME as name,TABLE_COMMENT as title from information_schema.tables where table_schema='" . Config::get('database')['database'] . "'");
            $bill_name = '';
            for ($i = 0; $i < count($tables_comments); $i++) {
                if ($tables_comments[$i]['name'] == $params['table']) {
                    $bill_name = $tables_comments[$i]['title'];
                    break;
                }
            }
            $this->model->save(['bill_name' => $bill_name, 'bill_table' => $params['table'], 'controller' => $params['controller'], 'params' => json_encode($params), 'createtime' => time(), 'updatetime' => time()]);
            return ['code' => 1, 'msg' => '新建单据成功'];
        }

    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            return ['code' => 0, 'msg' => '不允许直接对该表进行编辑'];
        }
        return $this->view->fetch('cannotedit');
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        $bill = $this->model->find($ids);
        if ($bill) {
            $params = [];
            $params['commandtype'] = 'crud';
            $params['delete'] = 1;
            $params['menu'] = 1;
            $params['table'] = $bill['bill_table'];
            $params['controller'] = $bill['controller'];

            if (!$this->command($params, 'execute')) {
                return ['code' => 0, 'msg' => '删除单据失败'];
            }
            unset($params['controller']);
            $params['force'] = 1;
            $params['commandtype'] = 'menu';
            $params['controllerfile'] = $bill['controller'];
            if (!$this->command($params, 'execute')) {
                return ['code' => 0, 'msg' => '删除菜单失败'];
            }
            $bill->delete();
            if (count($this->model->select()) == 0) {
                $bill_rule = (new AuthRule())->where('name', 'fastflow/bill')->find();
                if ($bill_rule) {
                    $bill_rule->status = 'hidden';
                    $bill_rule->save();
                }
            }
            return ['code' => 1, 'msg' => '删除单据成功'];
        }
        else {
            return ['code' => 0, 'msg' => '删除单据失败'];
        }
    }

    public function getFieldsWithComment()
    {
        $table = input('table');
        $fileds = (new api())->getFieldsWithComment($table);
        return ['code' => 1, 'msg' => '获取成功', 'data' => $fileds];
    }


    private function command($params, $action = '')
    {
        $commandtype = $params['commandtype'];
        $allowfields = [
            'crud' => 'table,controller,model,fields,force,local,delete,menu',
            'menu' => 'controller,delete,icon',
        ];
        $argv = [];
        $allowfields = isset($allowfields[$commandtype]) ? explode(',', $allowfields[$commandtype]) : [];
        $allowfields = array_filter(array_intersect_key($params, array_flip($allowfields)));
        if (isset($params['local']) && !$params['local']) {
            $allowfields['local'] = $params['local'];
        }
        else {
            unset($allowfields['local']);
        }
        foreach ($allowfields as $key => $param) {
            $argv[] = "--{$key}=" . (is_array($param) ? implode(',', $param) : $param);
        }
        if ($commandtype == 'crud') {
            if (isset($params['isrelation'])) {
                $isrelation = $params['isrelation'];
                if ($isrelation && isset($params['relation'])) {
                    foreach ($params['relation'] as $index => $relation) {
                        foreach ($relation as $key => $value) {
                            $argv[] = "--{$key}=" . (is_array($value) ? implode(',', $value) : $value);
                        }
                    }
                }
            }
        }
        elseif ($commandtype == 'menu') {
            foreach (explode(',', $params['controllerfile']) as $index => $param) {
                if ($param) {
                    $argv[] = "--controller=" . $param;
                }
            }
        }
        if ($action == 'execute') {
            return $this->doexecute($commandtype, $argv);
        }
        return;
    }

    private function doexecute($commandtype, $argv)
    {
        $commandName = "\\addons\\fastflow\\command\\" . ucfirst($commandtype);
        $input = new Input($argv);
        $output = new \addons\fastflow\command\Output();
        $command = new $commandName($commandtype);
        try {
            $command->run($input, $output);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }


    public function getBadge()
    {
        $colors = ['top' => 'bg-red', 'second' => 'bg-orange', 'bill' => 'bg-red'];
        $shapes = ['top' => 'label', 'second' => 'badge', 'bill' => 'badge'];
        $auth_rule = new AuthRule();
        $bills = $this->model->select();
        $temp_arr = [];
        foreach ($bills as $bill) {
            $count = (new api())->getBillCanCheckThreadCount($bill['bill_table']);
            $level_arr = explode('/', $bill['controller']);
            $controllerName = Loader::parseName(end($level_arr), 0);
            if ($controllerName[0] == '_') {
                $controllerName[0] = '';
            }
            array_pop($level_arr);
            array_push($level_arr, $controllerName);
            for ($i = 0; $i < count($level_arr); $i++) {
                $level_str = implode('/', array_slice($level_arr, 0, $i + 1));
                $in_array = false;
                $index = 0;
                for ($j = 0; $j < count($temp_arr); $j++) {
                    if ($temp_arr[$j]['level'] == $level_str) {
                        $in_array = true;
                        $index = $j;
                        break;
                    }
                }

                if ($in_array) {
                    $temp_arr[$index]['count'] += $count;
                }
                else {
                    $temp_arr[] = ['level' => $level_str, 'count' => $count];
                }
            }
        }
        $data = [];
        $carbon_count = Carbon::where(['receiver_id' => Session::get()['admin']['id'], 'is_read' => 0])->count();
        $temp_arr[] = ['level' => 'fastflow/carbon', 'count' => $carbon_count];
        foreach ($temp_arr as $item) {
            $rule = $auth_rule->where(['name' => $item['level']])->find();
            if ($rule) {
                $menu_id = $rule['id'];
                if ($menu_id) {
                    if ($item['level'] == 'fastflow') {
                        $data[] = ['id' => $menu_id, 'count' => ($item['count'] + $carbon_count), 'type' => 'top', 'color' => $colors['top'], 'shape' => $shapes['top'], 'show' => ($item['count'] + $carbon_count)];
                    }
                    elseif ($item['level'] == 'fastflow/bill') {
                        $data[] = ['id' => $menu_id, 'count' => $item['count'], 'type' => 'second', 'color' => $colors['second'], 'shape' => $shapes['second'], 'show' => '单据待审'];
                    }
                    else {
                        $data[] = ['id' => $menu_id, 'count' => $item['count'], 'type' => 'bill', 'color' => $colors['bill'], 'shape' => $shapes['bill'], 'show' => $item['count']];
                    }
                }
            }
        }
        return ['code' => 1, 'msg' => '获取菜单角标数据成功', 'data' => $data];
    }

    public function auth($bill = null)
    {
        if ($this->request->isGet()) {
            if (!$bill) {
                $this->error('不能直接访问该页面', '');
            }
            else {
                $bill_row = (new \app\admin\model\fastflow\flow\Bill())->where(['bill_table' => $bill])->find();
                if (!$bill_row) {
                    $this->error('参数错误，未找到该单据', '');
                }
                $flow_rows = (new \app\admin\model\fastflow\flow\Flow())->field(['id', 'name'])->where(['bill' => $bill_row['bill_table']])->select();
                if (count($flow_rows) == 0) {
                    $this->error('请先添加该单据流程', '');
                }
                $flowids = [];
                foreach ($flow_rows as $flow_row) {
                    $flowids[] = $flow_row['id'];
                }
                $this->view->assign(['bill_table' => $bill_row['bill_table'], 'bill_name' => $bill_row['bill_name'], 'flows' => $flow_rows]);
                $rules = [];
                $auth_row = (new BillAuth())->where(['bill' => $bill_row['bill_table']])->find();
                if ($auth_row && $auth_row['rules'] != '') {
                    $rules = json_decode($auth_row['rules'], true);
                    foreach ($rules as $flow_id => &$flow_rules) {
                        if (is_array($flow_rules)) {
                            foreach ($flow_rules as &$flow_rule) {
                                $flow_rule['fields'] = implode(',', $flow_rule['fields']);
                                $flow_rule['steps'] = implode(',', $flow_rule['steps']);
                            }
                        }
                    }
                }
                $this->assignconfig(['bill_table' => $bill_row['bill_table'], 'flowids' => $flowids, 'rules' => $rules]);
                return $this->view->fetch();
            }
        }
        elseif ($this->request->isPost()) {
            $bill = input('bill');
            $rules = input('rule/a');

            if (!isset($bill) || $bill == '') {
                return ['code' => 0, 'msg' => '参数错误，请联系管理员'];
            }
            if (isset($rules)) {
                foreach ($rules as $flow_id => $flow_rules) {
                    if (is_array($flow_rules)) {
                        foreach ($flow_rules as $flow_rule) {
                            if ($flow_rule['behavior'] == '' || $flow_rule['control'] == '') {
                                return ['code' => 0, 'msg' => '参数错误，行为或操作不能为空'];
                            }
                        }
                    }
                    else {
                        unset($rules[$flow_id]);
                    }
                }
                if (count($rules) == 0) {
                    $rules = '';
                }
                else {
                    $rules = json_encode($rules);
                }
                $billAuthModel = new BillAuth();
                $auth_row = $billAuthModel->where(['bill' => $bill])->find();
                if (!$auth_row) {
                    $billAuthModel->save(['bill' => $bill, 'rules' => $rules]);
                }
                else {
                    $auth_row['rules'] = $rules;
                    $auth_row->save();
                }
                return ['code' => 1, 'msg' => '单据权限配置成功'];
            }
            return ['code' => 1];
        }
    }

    public function getAuthSelectOptionData($flowid)
    {
        $behavior = [
            ['value' => 1, 'name' => '允许']
        ];
        $control = [
            ['value' => 1, 'name' => '编辑']
        ];

        $all_steps = (new api())->getAllSteps($flowid);
        $steps = [];
        foreach ($all_steps as $step) {
            if ($step['data']['type'] != 'start' && $step['data']['type'] != 'end') {
                $steps[] = ['value'=>$step['id'],'name'=>$step['data']['name']];
            }
        }

        $data = ['behavior' => $behavior, 'steps' => $steps, 'control' => $control];
        return ['code' => 1, 'msg' => '获取成功', 'data' => $data];
    }

    public function getSelectpageWorkers()
    {
        return (new api())->getSelectpageWorkers();
    }

}
