<?php

namespace app\admin\controller\cms;

use app\common\controller\Backend;
use app\common\model\User;
use think\Db;
use think\exception\PDOException;

/**
 * 评论管理
 *
 * @icon fa fa-comment
 */
class Comment extends Backend
{

    /**
     * Comment模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\cms\Comment;
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 查看
     */
    public function index()
    {
        $this->relationSearch = true;
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

            foreach ($list as $index => $item) {
                $item->user->visible(['id', 'username', 'nickname', 'avatar']);
                $item->source = $item->source;
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig("typeList", $this->model->getTypeList());
        return $this->view->fetch();
    }

    public function recyclebin()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $this->relationSearch = true;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->onlyTrashed()
                ->with(['archives', 'spage', 'user'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->onlyTrashed()
                ->with(['archives', 'spage', 'user'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $index => $item) {
                $item->user->visible(['id', 'username', 'nickname', 'avatar']);
                $type = $item['type'] == 'page' ? 'spage' : $item['type'];
                $item->url = $item->{$type} ? $item->{$type}->url : 'javascript:';
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function restore($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        if ($ids) {
            $this->model->where($pk, 'in', $ids);
        }
        $config = get_addon_config('cms');
        $list = $this->model->onlyTrashed()->select();
        if ($list) {
            $ids = [];
            foreach ($list as $index => $item) {
                if ($item['status'] == 'normal') {
                    Db::name("cms_{$item['type']}")->where('id', $item['aid'])->setInc("comments");
                    User::score($config['score']['postcomment'], $item['user_id'], '发表评论');
                }
                $ids[] = $item['id'];
            }
            $this->model->where('id', 'in', $ids);
            $this->model->restore('1=1');
            $this->success();
        }
        $this->error(__('No rows were updated'));
    }

}
