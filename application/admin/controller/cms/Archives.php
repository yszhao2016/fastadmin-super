<?php

namespace app\admin\controller\cms;

use addons\cms\library\FulltextSearch;
use app\admin\model\cms\Channel;
use app\admin\model\cms\ChannelAdmin;
use app\admin\model\cms\Modelx;
use app\common\controller\Backend;
use app\common\model\User;
use fast\Tree;
use think\Db;
use think\db\Query;
use think\Hook;

/**
 * 内容表
 *
 * @icon fa fa-file-text-o
 */
class Archives extends Backend
{

    /**
     * Archives模型对象
     */
    protected $model = null;
    protected $noNeedRight = ['get_fields_html', 'check_element_available', 'suggestion', 'copy', 'special', 'tags', 'move', 'flag'];
    protected $channelIds = [];
    protected $isSuperAdmin = false;
    protected $searchFields = 'id,title';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\cms\Archives;
        $cms = get_addon_config('cms');
        if ($cms['archivesdatalimit'] != 'all') {
            $this->dataLimit = $cms['archivesdatalimit'];
        }

        //复制/加入专题/修改标签均检测编辑权限
        if (in_array($this->request->action(), ['copy', 'special', 'tags', 'move', 'flag']) && !$this->auth->check('cms/archives/edit')) {
            Hook::listen('admin_nopermission', $this);
            $this->error(__('You have no permission'), '');
        }

        //是否超级管理员
        $this->isSuperAdmin = $this->auth->isSuperAdmin();
        $channelList = [];
        $disabledIds = [];
        $all = collection(Channel::order("weigh desc,id desc")->select())->toArray();

        //允许的栏目
        $this->channelIds = $this->isSuperAdmin || !$cms['channelallocate'] ? Channel::column('id') : ChannelAdmin::getAdminChanneIds();
        $parentChannelIds = Channel::where('id', 'in', $this->channelIds)->column('parent_id');
        $parentChannelIds = array_unique($parentChannelIds);
        $parentChannelList = \think\Db::name('cms_channel')->where('id', 'in', $parentChannelIds)->where('parent_id', '<>', 0)->field('id,parent_id,name')->select();
        $tree = Tree::instance()->init($all, 'parent_id');
        foreach ($parentChannelList as $index => $channel) {
            $parentChannelIds = array_merge($parentChannelIds, $tree->getParentsIds($channel['parent_id'], true));
        }
        $this->channelIds = array_merge($parentChannelIds, $this->channelIds);
        foreach ($all as $k => $v) {
            $state = ['opened' => true];
            if ($v['type'] == 'link') {
                $disabledIds[] = $v['id'];
            }
            if ($v['type'] == 'link') {
                $state['checkbox_disabled'] = true;
            }
            if (!$this->isSuperAdmin) {
                if (!in_array($v['id'], $parentChannelIds) && !in_array($v['id'], $this->channelIds)) {
                    unset($all[$k]);
                    continue;
                }
            }
            $channelList[] = [
                'id'     => $v['id'],
                'parent' => $v['parent_id'] ? $v['parent_id'] : '#',
                'text'   => __($v['name']),
                'type'   => $v['type'],
                'state'  => $state
            ];
        }
        $tree = Tree::instance()->init($all, 'parent_id');
        $channelOptions = $tree->getTree(0, "<option model='@model_id' value=@id @selected @disabled>@spacer@name</option>", '', $disabledIds);
        $secondChannelOptions = $tree->getTree(0, "<option model='@model_id' value=@id disabled>@spacer@name</option>", '', $disabledIds);
        $this->view->assign('channelOptions', $channelOptions);
        $this->view->assign('secondChannelOptions', $secondChannelOptions);
        $this->assignconfig('channelList', $channelList);

        $this->assignconfig("flagList", $this->model->getFlagList());
        $this->view->assign("flagList", $this->model->getFlagList());
        $this->view->assign("statusList", $this->model->getStatusList());

        $this->assignconfig('cms', ['archiveseditmode' => $cms['archiveseditmode']]);
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $this->relationSearch = true;
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if (!$this->auth->isSuperAdmin()) {
                $this->model->where('channel_id', 'in', $this->channelIds);
            }
            $total = $this->model
                ->with('Channel')
                ->where($where)
                ->order($sort, $order)
                ->count();
            if (!$this->auth->isSuperAdmin()) {
                $this->model->where('channel_id', 'in', $this->channelIds);
            }
            $list = $this->model
                ->with(['Channel'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        $modelList = \app\admin\model\cms\Modelx::all();
        $specialList = \app\admin\model\cms\Special::where('status', 'normal')->select();
        $this->view->assign('modelList', $modelList);
        $this->view->assign('specialList', $specialList);
        return $this->view->fetch();
    }

    /**
     * 副表内容
     */
    public function content($model_id = null)
    {
        $model = \app\admin\model\cms\Modelx::get($model_id);
        if (!$model) {
            $this->error('未找到对应模型');
        }
        $fieldsList = \app\admin\model\cms\Fields::where('source', 'model')->where('source_id', $model['id'])->where('type', '<>', 'text')->select();

        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $fields = [];
            foreach ($fieldsList as $index => $item) {
                $fields[] = "addon." . $item['name'];
            }
            $filter = $this->request->request('filter');
            $op = $this->request->request('op');
            if ($filter && $op) {
                $filterArr = json_decode($filter, true);
                $opArr = json_decode($op, true);
                foreach ($filterArr as $index => $item) {
                    if (in_array("addon." . $index, $fields)) {
                        $filterArr["addon." . $index] = $item;
                        $opArr["addon." . $index] = $opArr[$index];
                        unset($filterArr[$index], $opArr[$index]);
                    }
                }
                $this->request->get(['filter' => json_encode($filterArr), 'op' => json_encode($opArr)]);
            }

            $this->searchFields = "archives.id,archives.title";
            $this->relationSearch = true;
            $table = $this->model->getTable();
            list($where, $sort, $order, $offset, $limit, $page, $alias) = $this->buildparams();
            $sort = 'archives.id';
            $isSuperAdmin = $this->isSuperAdmin;
            $channelIds = $this->channelIds;
            $customWhere = function ($query) use ($isSuperAdmin, $channelIds, $model_id) {
                if (!$isSuperAdmin) {
                    $query->where('archives.channel_id', 'in', $channelIds);
                }
                if ($model_id) {
                    $query->where('archives.model_id', $model_id);
                }
            };

            $list = $this->model
                ->alias($alias)
                ->alias('archives')
                ->join('cms_channel channel', 'channel.id=archives.channel_id', 'LEFT')
                ->join($model['table'] . ' addon', 'addon.id=archives.id', 'LEFT')
                ->field('archives.*,channel.name as channel_name,addon.id as aid' . ($fields ? ',' . implode(',', $fields) : ''))
                ->where($customWhere)
                ->whereNull('deletetime')
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        $fields = [];
        foreach ($fieldsList as $index => $item) {
            $fields[] = ['field' => $item['name'], 'title' => $item['title'], 'type' => $item['type'], 'content' => $item['content_list']];
        }
        $this->assignconfig('fields', $fields);
        $this->view->assign('fieldsList', $fieldsList);
        $this->view->assign('model', $model);
        $this->assignconfig('model_id', $model_id);
        $modelList = \app\admin\model\cms\Modelx::all();
        $this->view->assign('modelList', $modelList);
        return $this->view->fetch();
    }

    /**
     * 编辑
     *
     * @param mixed $ids
     * @return string
     */
    public function edit($ids = null)
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
        if (!$this->isSuperAdmin && !in_array($row['channel_id'], $this->channelIds)) {
            $this->error(__('You have no permission'));
        }
        if ($this->request->isPost()) {
            return parent::edit($ids);
        }
        $channel = Channel::get($row['channel_id']);
        if (!$channel) {
            $this->error(__('No specified channel found'));
        }
        $model = \app\admin\model\cms\Modelx::get($channel['model_id']);
        if (!$model) {
            $this->error(__('No specified model found'));
        }
        $addon = db($model['table'])->where('id', $row['id'])->find();
        if ($addon) {
            $row->setData($addon);
        }

        $disabledIds = [];
        $all = collection(Channel::order("weigh desc,id desc")->select())->toArray();
        foreach ($all as $k => $v) {
            if ($v['type'] == 'link' || $v['model_id'] != $channel['model_id']) {
                $disabledIds[] = $v['id'];
            }
        }
        $disabledIds = array_diff($disabledIds, [$row['channel_id']]);
        $tree = Tree::instance()->init($all, 'parent_id');
        $channelOptions = $tree->getTree(0, "<option model='@model_id' value=@id @selected @disabled>@spacer@name</option>", $row['channel_id'], $disabledIds);
        $secondChannelOptions = $tree->getTree(0, "<option model='@model_id' value=@id @selected @disabled>@spacer@name</option>", explode(',', $row['channel_ids']), $disabledIds);
        $this->view->assign('channelOptions', $channelOptions);
        $this->view->assign('secondChannelOptions', $secondChannelOptions);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     * @param mixed $ids
     */
    public function del($ids = "")
    {
        \app\admin\model\cms\Archives::event('after_delete', function ($row) {
            Channel::where('id', $row['channel_id'])->where('items', '>', 0)->setDec('items');
        });
        parent::del($ids);
    }

    /**
     * 销毁
     * @param string $ids
     */
    public function destroy($ids = "")
    {
        \app\admin\model\cms\Archives::event('after_delete', function ($row) {
            //删除副表
            $channel = Channel::get($row->channel_id);
            if ($channel) {
                $model = Modelx::get($channel['model_id']);
                if ($model) {
                    db($model['table'])->where("id", $row['id'])->delete();
                }
            }
        });
        parent::destroy($ids);
    }

    /**
     * 还原
     * @param mixed $ids
     */
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
                    Channel::where('id', $item['id'])->setInc('items');
                    User::score($config['score']['postarchives'], $item['user_id'], '发布文章');
                }
                $ids[] = $item['id'];
            }
            $this->model->where('id', 'in', $ids);
            $this->model->restore('1=1');
            $this->success();
        }
        $this->error(__('No rows were updated'));
    }

    /**
     * 移动
     * @param string $ids
     */
    public function move($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        if ($ids) {
            if (!$this->request->isPost()) {
                $this->error(__("Invalid parameters"));
            }
            $channel_id = $this->request->post('channel_id');
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $this->model->where($pk, 'in', $ids);
            $channel = Channel::get($channel_id);
            if ($channel && $channel['type'] === 'list') {
                $channelNums = \app\admin\model\cms\Archives::
                with('channel')
                    ->where('archives.' . $pk, 'in', $ids)
                    ->where('channel_id', '<>', $channel['id'])
                    ->field('channel_id,COUNT(*) AS nums')
                    ->group('channel_id')
                    ->select();
                $result = $this->model
                    ->where('model_id', '=', $channel['model_id'])
                    ->where('channel_id', '<>', $channel['id'])
                    ->update(['channel_id' => $channel_id]);
                if ($result) {
                    $count = 0;
                    foreach ($channelNums as $k => $v) {
                        if ($v['channel']) {
                            Channel::where('id', $v['channel_id'])->where('items', '>', 0)->setDec('items', min($v['channel']['items'], $v['nums']));
                        }
                        $count += $v['nums'];
                    }
                    Channel::where('id', $channel_id)->setInc('items', $count);
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            } else {
                $this->error(__('No rows were updated'));
            }
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
    }

    /**
     * 复制选择行
     * @param string $ids
     */
    public function copy($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $archivesList = $this->model->where('id', 'in', $ids)->select();
            foreach ($archivesList as $index => $item) {
                try {
                    $model = Modelx::get($item['model_id']);
                    $addon = \think\Db::name($model['table'])->find($item['id']);
                    $data = $item->toArray();
                    $data = array_merge($data, $addon ?? []);
                    $data['title'] = $data['title'] . "_copy";
                    $data['status'] = 'hidden';
                    unset($data['id']);
                    \app\admin\model\cms\Archives::create($data, true);
                } catch (\Exception $e) {
                    //
                }
            }
            $this->success();
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
    }

    /**
     * 加入专题
     * @param string $ids
     */
    public function special($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        if ($ids) {
            $special_id = $this->request->post('special_id');
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $special = \app\admin\model\cms\Special::get($special_id);
            if ($special) {
                $archivesList = $this->model->where($pk, 'in', $ids)->select();
                foreach ($archivesList as $index => $item) {
                    $special_ids = explode(',', $item['special_ids']);
                    if (!in_array($special['id'], $special_ids)) {
                        $special_ids[] = $special['id'];
                        $item->save(['special_ids' => implode(',', array_unique(array_filter($special_ids)))]);
                    }
                }
                $this->success();
            } else {
                $this->error(__('No rows were updated'));
            }
        }
        $this->error(__('Please select at least one row'));
    }

    /**
     * 加入标签
     * @param string $ids
     */
    public function tags($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        if ($ids) {
            $tags = $this->request->post('tags');
            $newTagsArr = array_filter(explode(',', $tags));
            if ($newTagsArr) {
                $pk = $this->model->getPk();
                $adminIds = $this->getDataLimitAdminIds();
                if (is_array($adminIds)) {
                    $this->model->where($this->dataLimitField, 'in', $adminIds);
                }
                $archivesList = $this->model->where($pk, 'in', $ids)->select();
                foreach ($archivesList as $index => $item) {
                    $tagsArr = explode(',', $item['tags']);
                    $tagsArr = array_merge($tagsArr, $newTagsArr);
                    $item->save(['tags' => implode(',', array_unique(array_filter($tagsArr)))]);
                }
                $this->success();
            } else {
                $this->error(__('标签数据不能为空'));
            }
        }
        $this->error(__('Please select at least one row'));
    }

    /**
     * 修改标志
     * @param string $ids
     */
    public function flag($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        if ($ids) {
            $type = $this->request->post('type');
            $flag = $this->request->post('flag');
            $changeFlagArr = array_filter(explode(',', $flag));
            if ($changeFlagArr) {
                $pk = $this->model->getPk();
                $adminIds = $this->getDataLimitAdminIds();
                if (is_array($adminIds)) {
                    $this->model->where($this->dataLimitField, 'in', $adminIds);
                }
                $archivesList = $this->model->where($pk, 'in', $ids)->select();
                foreach ($archivesList as $index => $item) {
                    $flagArr = explode(',', $item['flag']);
                    if ($type == 'add') {
                        $flagArr = array_merge($flagArr, $changeFlagArr);
                    } else {
                        $flagArr = array_diff($flagArr, $changeFlagArr);
                    }
                    $item->save(['flag' => implode(',', array_unique(array_filter($flagArr)))]);
                }
                $this->success();
            } else {
                $this->error(__('标志数据不能为空'));
            }
        }
        $this->error(__('Please select at least one row'));
    }

    /**
     * 获取栏目列表
     * @internal
     */
    public function get_fields_html()
    {
        $this->view->engine->layout(false);
        $channel_id = $this->request->post('channel_id');
        $archives_id = $this->request->post('archives_id');
        $channel = Channel::get($channel_id, 'model');
        if ($channel) {
            $model_id = $channel['model_id'];
            $values = [];
            if ($archives_id) {
                $values = db($channel['model']['table'])->where('id', $archives_id)->find();

                //优先从栏目获取模型ID，再从文档获取
                $archives = \app\admin\model\cms\Archives::get($archives_id);
                $model_id = $archives ? $archives['model_id'] : $model_id;
            }

            $fields = \addons\cms\library\Service::getCustomFields('model', $model_id, $values);

            $model = Modelx::get($model_id);

            $setting = $model['setting'];
            $publishfields = isset($setting['publishfields']) ? $setting['publishfields'] : [];

            $this->view->assign('channel', $channel);
            $this->view->assign('fields', $fields);
            $this->view->assign('values', $values);
            $this->success('', null, ['html' => $this->view->fetch('cms/common/fields'), 'publishfields' => $publishfields]);
        } else {
            $this->error(__('Please select channel'));
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 检测元素是否可用
     * @internal
     */
    public function check_element_available()
    {
        $id = $this->request->request('id');
        $name = $this->request->request('name');
        $value = $this->request->request('value');
        $name = substr($name, 4, -1);
        if (!$name) {
            $this->error(__('Parameter %s can not be empty', 'name'));
        }
        if ($id) {
            $this->model->where('id', '<>', $id);
        }
        $exist = $this->model->where($name, $value)->find();
        if ($exist) {
            $this->error(__('The data already exist'));
        } else {
            $this->success();
        }
    }

    /**
     * 搜索建议
     * @internal
     */
    public function suggestion()
    {
        $config = get_addon_config('cms');
        $q = trim($this->request->request("q"));
        $id = trim($this->request->request("id/d"));
        $list = [];
        if ($config['searchtype'] == 'xunsearch') {
            $result = FulltextSearch::search($q, 1, 10);
        } else {
            $result = $this->model->where("title|keywords|description", "like", "%{$q}%")->where('id', '<>', $id)->limit(10)->order("id", "desc")->select();
            foreach ($result as $index => $item) {
                $item['image'] = $item['image'] ? $item['image'] : '/assets/addons/cms/img/noimage.png';
                $list[] = ['id' => $item['id'], 'url' => $item['fullurl'], 'image' => cdnurl($item['image']), 'title' => $item['title'], 'create_date' => datetime($item['createtime']), 'status' => $item['status'], 'status_text' => $item['status_text'], 'deletetime' => $item['deletetime']];
            }
        }
        return json($list);
    }
}
