<?php

namespace app\admin\controller\cms;

use addons\cms\library\Service;
use app\common\controller\Backend;

/**
 * 自定义表单数据表
 *
 * @icon fa fa-circle-o
 */
class Diydata extends Backend
{

    /**
     * 自定义表单模型对象
     */
    protected $diyform = null;
    /**
     * 定义表单数据表模型
     * @var null
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $diyform_id = $this->request->param('diyform_id');
        $this->diyform = \app\admin\model\cms\Diyform::get($diyform_id);
        if (!$this->diyform) {
            $this->error('未找到对应自定义表单');
        }
        $this->model = new \addons\cms\model\Diydata([], $this->diyform);
        $this->assignconfig('diyform_id', $diyform_id);
    }

    /**
     * 查看
     */
    public function index()
    {
        $fieldsList = \app\admin\model\cms\Fields::where('source', 'diyform')->where('source_id', $this->diyform['id'])->where('type', '<>', 'text')->select();
        $fields = [];
        foreach ($fieldsList as $index => $item) {
            $fields[] = ['field' => $item['name'], 'title' => $item['title'], 'type' => $item['type'], 'content' => $item['content_list']];
        }
        $this->assignconfig('fields', $fields);
        $where = [];


        $cms = get_addon_config('cms');
        if ($cms['diyformdatalimit'] != 'all') {
            $this->dataLimit = $cms['diyformdatalimit'];
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $where[$this->dataLimitField] = ['in', $adminIds];
            }
            $this->dataLimit = false;
        }

        $diyformList = \app\admin\model\cms\Diyform::where($where)->select();
        $this->view->assign('diyform', $this->diyform);
        $this->view->assign('diyformList', $diyformList);
        return parent::index();
    }

    /**
     * 添加
     */
    public function add()
    {
        $this->assignFields();
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try {
                    $result = $this->model->insert($params);
                    if ($result !== false) {
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
        $row = $this->model->where('id', $ids)->find();
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
                try {
                    $result = $this->model->where('id', $ids)->update($params);
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

        $this->assignFields($ids);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $count = $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $count = $this->model->where($pk, 'in', $ids)->delete();
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    private function assignFields($diydata_id = 0)
    {
        $values = [];
        if ($diydata_id) {
            $values = db($this->diyform['table'])->where('id', $diydata_id)->find();
        }
        $fields = Service::getCustomFields('diyform', $this->diyform['id'], $values);

        $this->view->assign('fields', $fields);
        $this->view->assign('values', $values);
    }

}
