<?php

namespace app\admin\controller\hj212;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Pollution extends Backend
{

    /**
     * Pollution模型对象
     * @var \app\admin\model\hj212\Pollution
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\hj212\Pollution;
        $data_id = $this->request->param('data_id', 0);
        $this->assignconfig('data_id', $data_id);

    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        $data_id = $this->request->param('data_id', 0);
        
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $list = $this->model
            ->where(function($query) use($data_id){
                if($data_id){
                    $query->where('data_id',$data_id);
                }
            })
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
            
            $rows = $list->items();
            foreach($rows as $v){
                //获取检测因子信息
                $code = \app\admin\model\hj212\PollutionCode::where(['code'=>$v['code']])->find();
                if($code){
                    $v['code_nm'] = $code['name'];
                }else{
                    $v['code_nm'] = $v['code'];
                }
            }
            
            $result = array("total" => $list->total(), "rows" => $rows);
            
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
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
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
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $data_id = $this->request->param('data_id',0);
        $this->view->assign("data_id", $data_id);
        return $this->view->fetch();
    }
}
