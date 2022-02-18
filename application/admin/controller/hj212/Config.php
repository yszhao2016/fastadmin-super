<?php
/**
 * Created by PhpStorm
 * USER: Zhaoys
 * Date: 2022/2/18 9:29
 */

namespace app\admin\controller\hj212;


use app\common\controller\Backend;
use app\common\model\Config as SysConfig;
use think\Db;
use think\Exception;
use think\exception\PDOException;

class Config extends Backend
{
    public function index()
    {
        $config = SysConfig::where(['group' => 'hj212'])
            ->select();
        $this->assign('hjconfig', $config);
        return $this->view->fetch();
    }


    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            Db::startTrans();
            if (!$params) {
                return $this->error("必须提交参数");
            }
            try {
                foreach ($params as $key => $value) {
                    $model = SysConfig::where('name', $key)
                        ->where('group', 'hj212')
                        ->find();

                    if ($model->type == 'array') {
                        $val = json_encode($value);
                        $model->value = $val;
                    }
                    $result = $model->save();
                }
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            Db::commit();
            if ($result !== false) {
                $this->success();
            } else {
                $this->error(__('No rows were inserted'));
            }

        }
    }
}