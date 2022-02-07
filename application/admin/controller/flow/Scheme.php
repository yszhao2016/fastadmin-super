<?php

namespace app\admin\controller\flow;

use app\common\controller\Backend;
use fast\Tree;
use app\admin\model\AuthGroup;
use think\Db;
use think\Config;
use think\console\Input;
use app\admin\model\Command;
use app\common\library\FlowEngine;

/**
 * 流程设计
 *
 * @icon fa fa-database
 */
class Scheme extends Backend
{

    /**
     * Scheme模型对象
     * @var \app\admin\model\flow\Scheme
     */
    protected $model = null;
    protected $number = null;
    protected $bizscheme = null;
    protected $command = null;
    protected $runtime = null;
    protected $dept = null;
    protected $task = null;
    protected $prefix = "";
    protected $noNeedRight = ['*'];
    protected $noNeedLogin = ['sendmail'];

    public function _initialize()
    {
        $this->prefix = Config::get('database.prefix');
        parent::_initialize();
        $this->model = new \app\admin\model\flow\Scheme;
        $this->bizscheme = new \app\admin\model\flow\Bizscheme;
        $this->number = new \app\admin\model\flow\Number;
        $this->dept = new \app\admin\model\flow\Department;
        $grouplist = collection(model('AuthGroup')->select())->toArray();
        //获取角色下拉框
        Tree::instance()->init($grouplist);
        $groupdata = [];
        $result = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
        foreach ($result as $k => $v) {
            $groupdata[$v['id']] = $v['name'];
        }
        //获取部门数据
        $deptList = collection($this->dept->select())->toArray();
        Tree::instance()->init($deptList);
        $deptresult = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
        $groupName = [];
        foreach ($deptresult as $k => $v) {
            $groupName[$v['id']] = $v['name'];
        }
        $this->view->assign('depts', $groupName);
        $this->view->assign('groupdata', $groupdata);
    }

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
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $row) {
                $row->visible(['id', 'flowcode', 'flowname', 'flowversion', 'weight', 'url', 'isenable']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $depts = $this->request->post("departments/a");
            $flowcode = $params['flowcode'];
            $prefix = Config::get('database.prefix');
            $bizScheme = $prefix . 'flow_' . $flowcode;
            $params['bizscheme'] = $bizScheme;
            $params['createtime'] = date("Y-m-d h:i:s");
            $params['createuser'] = $this->auth->id;
            $params['updatetime'] = date("Y-m-d h:i:s");
            $params['updateuser'] = $this->auth->id;
            $params['isenable'] = 0;
            $params['status']='normal';
            //$params = $this->request->post("row/a");
            if ($params) {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try {
                    $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                    $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                    $this->model->validate($validate);
                    $dataset =implode(',',$depts);
                    $params['department_id'] = $dataset;
                    $result = $this->model->allowField(true)->save($params);
                    $result = $this->number->allowField(true)->save(['code' => $flowcode, 'year' => $params['year'], 'month' => $params['month'], 'lengh' => $params['lengh'], 'pre' => $params['pre'], 'index' => 0]);
                    if ($result !== false) {
                        $sql = "DROP TABLE IF EXISTS $bizScheme";
                        Db::execute($sql);
                        $sql = "CREATE TABLE $bizScheme ( id char(36) primary key ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT";
                        Db::execute($sql);
                        $this->success();
                    } else {
                        $this->error($this->model->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $number = $this->number->where(['code' => $row['flowcode']])->find();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $depts = $this->request->post("departments/a");
            if ($params) {
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $dataset =implode(',',$depts);                   
                    $result = $row->allowField(true)->save(['flowname' => $params['flowname'], 'flowcanuser' => $params['flowcanuser'], 
                    'frmtype'=>$params['frmtype'],'status'=>$params['status'],'department_id'=>$dataset,
                    'updatetime' => date("Y-m-d h:i:s"), 'updateuser' => $this->auth->id]);
                    $result = $this->number->where(['code' => $row['flowcode']])->update(['year' => $params['year'], 'month' => $params['month'], 'lengh' => $params['lengh'], 'pre' => $params['pre']]);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $typeList=array('2'=>'自定义表单');
        $this->view->assign("typeList", $typeList);
        $this->view->assign("row", $row);
        $this->view->assign("number", $number);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $count = $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();
            foreach ($list as $k => $v) {
                $this->model->where('id', $v['id'])->delete();

                $flowcode = $v['flowcode'];;
                $table = $v['bizscheme'];
                Db::name('flow_number')->where(['code' => $flowcode])->delete();
                if ($v['isenable'] == '1') {

                    //生成curd
                    $commandtype = 'FlowCrud';
                    $table = $v['bizscheme'];
                    $argv = array("--table=$table", "--delete=1", "-force=1");
                    $commandName = "\\app\\admin\\command\\" . ucfirst($commandtype);
                    $input = new Input($argv);
                    $output = new \app\admin\model\flow\Output();
                    $command = new $commandName($commandtype);
                    $data = [
                        'type'        => $commandtype,
                        'params'      => json_encode($argv),
                        'command'     => "php think {$commandtype} " . implode(' ', $argv),
                        'executetime' => time(),
                    ];

                    $command->run($input, $output);
                    $result = implode("\n", $output->getMessage());
                }

                if ($table) {
                    $sql = "DROP TABLE IF EXISTS $table";
                    Db::execute($sql);
                }
            }
        }
        $this->success();
    }

    public function flow($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
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
                $params['updatetime'] = date("Y-m-d H:i:s");
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
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
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        $this->assignconfig("ids", $ids);
        $this->assignconfig("tableName", $row['bizscheme']);
        $this->assignconfig("flowcontent", $row->flowcontent);
        return $this->view->fetch();
    }
    public function line()
    {
        $ids = $this->request->request('ids');
        if ($this->request->isAjax()) {
            $scheme = $this->model->get($ids);
            //从数据库中获取表字段信息
            $sql = "SELECT COLUMN_KEY,COLUMN_NAME,COLUMN_TYPE,COLUMN_COMMENT,COLUMN_DEFAULT FROM `information_schema`.`columns` "
                . "WHERE TABLE_SCHEMA = ? AND table_name = ? "
                . "ORDER BY ORDINAL_POSITION";
            //加载主表的列
            $dbname = Config::get('database.database');
            $columnList = Db::query($sql, [$dbname, $scheme['bizscheme']]);
            $list = collection($columnList)->toArray();
            foreach ($list as $k => $v) {
                $state = ['opened' => true];

                $channelList[] = [
                    'id' => $v['COLUMN_NAME'],
                    'parent' => '1',
                    'text' => $v['COLUMN_NAME'],
                    'type' => 'list',
                    'state' => $state
                ];
            }
            $channelList[] = [
                'id' => '1',
                'parent' => '#',
                'text' => '列表',
                'type' => 'list',
                'state' => $state
            ];
            return json($channelList);
        }
        $this->assignconfig("ids", $ids);
        return $this->view->fetch();
    }
    /**
     * 浏览
     */
    public function browser()
    {
        $flowcode = $this->request->request('flowcode');
        $ids = $this->request->request('ids');
        $id = Model($flowcode)->where('instanceid', $ids)->value('id');
        $this->redirect('/admin/flow/' . $flowcode . '/edit', ['ids' => $id]);
    }

    public function model()
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->bizscheme
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->bizscheme
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $row) {
                $row->visible(['id', 'flowcode', 'flowname', 'flowversion', 'weight', 'url']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function flowchart($ids = null)
    {
        $this->task = new \app\admin\model\flow\Task;
        $this->scheme = new \app\admin\model\flow\Scheme;
        $taskid = $this->request->request('taskid');
        $task = $this->task->get($taskid);
        $activityId = $task['stepid'];
        $flowcode = $this->request->request('flowcode');
        if (!$flowcode) {
            $this->error(__('No Results were found'));
        }
        $row = $this->scheme->where('flowcode', $flowcode)->find();
        $taskList = Db::name('flow_task')
            ->alias('main')
            ->join('__ADMIN__ admin', 'admin.id=main.receiveid', 'LEFT')
            ->where('instanceid', $task['instanceid'])
            ->field(["main.instanceid", "main.stepid", "main.status", "admin.nickname", "main.completedtime", "main.createtime"])
            ->order('main.createtime', 'desc')
            ->select();
        $this->assignconfig('taskList', $taskList);
        $this->assignconfig("activityId", $activityId);
        $this->assignconfig("flowcontent", $row['flowcontent']);
        $this->view->assign("flowContent", $row['flowcontent']);
        return $this->view->fetch();
    }

    public function node()
    {
        return $this->view->fetch();
    }

    public function trans()
    {
        if ($this->request->isPost()) {
            $code = $this->request->request('flowcode');
            $taskid = $this->request->request('taskid');
            $userid = $this->request->request("userid");
            $engine = new FlowEngine($code);
            if(!$code || !$taskid || !$userid){
                $this->error(__('参数不能为空'));
            }
            $engine->trans($taskid,$userid);
            $this->success();
        }
        return $this->view->fetch();
    }

    public function next()
    {
        if ($this->request->isPost()) {
            $code = $this->request->request('code');
            $data = $this->request->request('data');
            $taskid = $this->request->request('taskid');
            $stepid = $this->request->request("stepid");
            $engine = new FlowEngine($code);
            if(!$code || !$taskid){
                $this->error(__('参数不能为空'));
            }
            $engine->next($taskid,$data,$stepid);
            $this->success();
        }
        return $this->view->fetch();
    }

    /**
     * 导出
     */
    public function export()
    {
        if ($this->request->isPost()) {
            set_time_limit(0);
            $search = $this->request->post('search');
            $ids = $this->request->post('ids');
            $filter = $this->request->post('filter');
            $op = $this->request->post('op');

            $whereIds = $ids == 'all' ? '1=1' : ['id' => ['in', explode(',', $ids)]];
            $this->request->get(['search' => $search, 'ids' => $ids, 'filter' => $filter, 'op' => $op]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $line = 1;
            $list = [];
            $item = Db::name('flow_scheme')
                ->field(['flowcode', 'flowname', 'flowcontent', 'bizscheme','frmtype','frmcode'])
                ->where($where)
                ->where($whereIds)
                ->find();
            $rule = Db::name('flow_number')
                ->where(['code' => $item['flowcode']])
                ->find();

            $sql = "show create table " . $item['bizscheme']; //Create Table
            $struct = Db::query($sql);
            $insertSql = $struct[0]['Create Table'].';';
            if ($rule) {
                $insertSql = $insertSql . " insert into ". $this->prefix ."flow_number (code,year,month,`index`,lengh,pre) values('" . $rule['code'] . "','" . $rule['year'] . "','" . $rule['month'] . "','" . $rule['index'] . "','" . $rule['lengh'] . "','" . $rule['pre'] . "');";
            }
            //获取节点权限
            $right = Db::name('flow_field')
                ->where(['flow_id'=>$ids])
                ->select();
            if($right){
                $insertSql = $insertSql." insert into ". $this->prefix ."flow_field ('flow_id','node_id','field','read','write') values ";
                foreach($right as $row){
                    $insertSql = $insertSql."('".$row["flow_id"]."','".$row["node_id"]."','".$row["field"]."',".$row["read"].",".$row["write"]."),";
                }
                $insertSql = rtrim($insertSql, ",");
            }

            $item['bizscemesql'] = $insertSql;
            $json = str_replace('\n', '', json_encode($item, JSON_UNESCAPED_UNICODE));
            // Redirect output to a client’s web browser (Excel2007)
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $item['flowcode'] . '.json"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0
            echo $json;
            return;
        }
    }

    /**
     * 导入
     */
    public function import()
    {
        $file = $this->request->request('file');
        if (!$file) {
            return json(array("code" => 0, "msg" => __('Parameter %s can not be empty', 'file'), "data" => "", "wait" => 3));
        }
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        if (!is_file($filePath)) {
            return json(array("code" => 0, "msg" => __('No results were found', 'file'), "data" => "", "wait" => 3));
        }
        if (strpos($file, 'json') == false) {
            unlink($filePath);
            return json(array("code" => 0, "msg" => __('文件格式不正确', 'file'), "data" => "", "wait" => 3));
        }
        try {
            $json_string = file_get_contents($filePath);
            $data = json_decode($json_string, true);
            if ($data['flowcode'] == '' || $data['flowname'] == '' || $data['bizscemesql'] == '') {
                return json(array("code" => 0, "msg" => __('数据格式不正确!!!', 'file'), "data" => "", "wait" => 3));
            }
            $isExist = Db::name('flow_scheme')->where('flowcode', $data['flowcode'])->find();
            if ($isExist) {
                return json(array("code" => 0, "msg" => __('流程代码已存在,请先删除再导入.', 'file'), "data" => "", "wait" => 3));
            }
            $this->model->insert(['flowcode' => $data['flowcode'], 'flowname' => $data['flowname'], 
            'frmtype'=>$data['frmtype'],'frmcode'=>$data['frmcode'],
            'bizscheme' => strtolower($data['bizscheme']), 'flowcontent' => $data['flowcontent'], 'isenable' => '1']);
            $sqlList = explode(";",rtrim($data['bizscemesql'],";"));
            foreach($sqlList as $sqlItem)
            {
                Db::execute($sqlItem);
            }
            
        } catch (\think\exception\PDOException $exception) {
            $this->error($exception->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success();
    }

    /**
     * 选择管理员
     *
     * @internal
     */
    public function selectuserpage()
    {
        $this->model = new \app\admin\model\Admin();
        return parent::selectpage();
    }

    /**
     * 选择角色组
     */
    public function selectrolepage()
    {
        $this->model = new \app\admin\model\AuthGroup();
        return parent::selectpage();
    }

    /***
     * 发送邮件
     */
    public function  sendmail()
    {
        \app\common\library\FlowMail::send();
    }

     /**
     * 获取节点授权字段默认是全部读
     */
    public function getNodeField($ids,$node)
    {
        $node = $this->request->request('node');
        $code = $this->request->request('code');
        $ids = $this->request->request('ids');
        $fieldListAll = Db::name('view_flow_field_default')
        ->where(['table_name'=>$code,'TABLE_SCHEMA'=>Config::get('database.database')])
        ->select();


        foreach($fieldListAll as $key=> $item)
        {
            $field = Db::name('flow_field')
            ->where(['node_id'=>$node,'flow_id'=>$ids,'field'=>$item['field']])
            ->find();
            if($field)
            {
                $fieldListAll[$key]['read']=$field['read'];
                $fieldListAll[$key]['write']=$field['write'];
                $fieldListAll[$key]['id']=$field['id'];
            }
            
        }
        return $fieldListAll;
    }
    /**
     * 插入更新字段权限
     */
    public function updateNodeField()
    {
        try {
            $params = json_decode($this->request->post('content'),true);
            $ids = $this->request->request('ids');
            $node = $this->request->request('node');
            foreach($params as $item)
            {
                if(empty($item['id'])){
                    $result =  Db::name('flow_field')->insert(['field'=>$item['field'],'read'=>$item['read'],'write'=>$item['write'],'flow_id'=>$ids,'node_id'=>$node]);
                }
                else{
                    $result =  Db::name('flow_field')->where(['id'=>$item['id']])
                    ->update(['read'=>$item['read'],'write'=>$item['write'],'flow_id'=>$ids,'node_id'=>$node]);
                }
                
            }   
            $this->success();        
        }
        catch (\Exception $e) {
            $this->success(); 
        }
        
    }
}
