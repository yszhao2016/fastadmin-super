<?php

namespace app\admin\controller\fastflow\flow;

use app\admin\model\AuthRule;
use app\common\controller\Backend;
use think\Config;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Exception;
use think\Loader;
use fastflow\api;

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
    protected $noNeedRight = ['getBadge', 'getFieldsWithComment'];

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
            $must_fields = ['uid', 'status'];
            $table = $params['table'];
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
        $output = new Output();
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
        foreach ($temp_arr as $item) {
            $menu_id = $auth_rule->where(['name' => $item['level']])->find()['id'];
            if ($menu_id) {
                if ($item['level'] == 'fastflow') {
                    $data[] = ['id' => $menu_id, 'count' => $item['count'], 'type' => 'top', 'color' => $colors['top'], 'shape' => $shapes['top'], 'show' => $item['count']];
                }
                elseif ($item['level'] == 'fastflow/bill') {
                    $data[] = ['id' => $menu_id, 'count' => $item['count'], 'type' => 'second', 'color' => $colors['second'], 'shape' => $shapes['second'], 'show' => '单据待审'];
                }
                else {
                    $data[] = ['id' => $menu_id, 'count' => $item['count'], 'type' => 'bill', 'color' => $colors['bill'], 'shape' => $shapes['bill'], 'show' => $item['count']];
                }
            }
        }
        return ['code' => 1, 'msg' => '获取菜单角标数据成功', 'data' => $data];
    }


}
