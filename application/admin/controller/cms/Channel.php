<?php

namespace app\admin\controller\cms;

use addons\vip\library\Service;
use app\admin\model\Admin;
use app\admin\model\AuthGroupAccess;
use app\admin\model\cms\ChannelAdmin;
use app\common\controller\Backend;
use app\admin\model\cms\Channel as ChannelModel;
use fast\Tree;
use think\Exception;

/**
 * 栏目表
 *
 * @icon fa fa-list
 */
class Channel extends Backend
{
    protected $channelList = [];
    protected $modelList = [];
    protected $multiFields = ['weigh', 'status', 'iscontribute', 'isnav'];

    /**
     * Channel模型对象
     */
    protected $model = null;
    protected $noNeedRight = ['get_fields_html', 'check_element_available'];
    /**
     * @var Tree
     */
    protected $tree = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\cms\Channel;

        $this->tree = Tree::instance();
        $this->tree->init(collection($this->model->order('weigh desc,id desc')->select())->toArray(), 'parent_id');
        $this->channelList = $this->tree->getTreeList($this->tree->getTreeArray(0), 'name');
        $this->modelList = \app\admin\model\cms\Modelx::order('id asc')->select();

        $this->view->assign("modelList", $this->modelList);
        $this->view->assign("channelList", $this->channelList);
        $this->view->assign("typeList", ChannelModel::getTypeList());
        $this->view->assign("statusList", ChannelModel::getStatusList());
        $this->view->assign("listtypeList", ChannelModel::getListtypeList());
        $this->view->assign("vipList", get_addon_info('vip') ? Service::getVipList() : []);
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $search = $this->request->request("search");
            $model_id = $this->request->request("model_id");
            //构造父类select列表选项数据
            $list = [];
            if ($search) {
                foreach ($this->channelList as $k => $v) {
                    if (stripos($v['name'], $search) !== false || stripos($v['nickname'], $search) !== false) {
                        $list[] = $v;
                    }
                }
            } else {
                $list = $this->channelList;
            }
            foreach ($list as $index => $item) {
                if ($model_id && $model_id != $item['model_id']) {
                    unset($list[$index]);
                }
            }
            $list = array_values($list);
            $modelNameArr = [];
            foreach ($this->modelList as $k => $v) {
                $modelNameArr[$v['id']] = $v['name'];
            }
            foreach ($list as $k => &$v) {
                $v['pid'] = $v['parent_id'];
                $v['model_name'] = $v['model_id'] && isset($modelNameArr[$v['model_id']]) ? $modelNameArr[$v['model_id']] : __('None');
            }
            $total = count($list);
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
            if ($params) {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : true) : $this->modelValidate;
                        $this->model->validate($validate);
                    }
                    $nameArr = array_filter(explode("\n", str_replace("\r\n", "\n", $params['name'])));
                    if (count($nameArr) > 1) {
                        foreach ($nameArr as $index => $item) {
                            $itemArr = array_filter(explode('|', $item));
                            $params['name'] = $itemArr[0];
                            $params['diyname'] = isset($itemArr[1]) ? $itemArr[1] : '';
                            $result = $this->model->allowField(true)->isUpdate(false)->data($params)->save();
                        }
                    } else {
                        $result = $this->model->allowField(true)->save($params);
                    }
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($this->model->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $values = [];
        $fields = \addons\cms\library\Service::getCustomFields('channel', 0, $values);

        $this->view->assign('fields', $fields);
        $this->view->assign('values', $values);
        return $this->view->fetch();
    }

    public function edit($ids = null)
    {
        $channel = \app\admin\model\cms\Channel::get($ids);
        if (!$channel) {
            $this->error(__('No Results were found'));
        }
        $linkdata = $channel['linkdata'];
        $channel = $channel->toArray();
        $fields = \addons\cms\library\Service::getCustomFields('channel', 0, $channel);

        $childrenIds = $this->tree->getChildrenIds($channel['id'], true);
        $hasArchives = \app\admin\model\cms\Archives::withTrashed()->where('channel_id', $channel['id'])->whereOr('FIND_IN_SET(:id, `channel_ids`)', ['id' => $channel['id']])->count();
        $this->view->assign('hasArchives', $hasArchives);
        $this->view->assign('fields', $fields);
        $this->view->assign('values', $channel);
        $this->view->assign('childrenIds', $childrenIds);
        $this->assignconfig('linkdata', $linkdata);
        return parent::edit($ids);
    }

    /**
     * 栏目授权
     */
    public function admin()
    {
        $act = $this->request->param('act');
        $ids = $this->request->param('ids');
        if ($act && $ids) {
            if (!$this->request->isPost()) {
                $this->error(__("Invalid parameters"));
            }
            if ($act == 'remove') {
                ChannelAdmin::where('admin_id', $ids)->delete();
                $this->success('删除成功！');
            } elseif ($act == 'authorization') {
                $selected = ChannelAdmin::getAdminChanneIds($ids);
                $all = collection(ChannelModel::order("weigh desc,id desc")->select())->toArray();
                foreach ($all as $k => $v) {
                    $state = ['opened' => true];
                    if ($v['type'] == 'link') {
                        $disabledIds[] = $v['id'];
                    }
                    if ($v['type'] == 'link') {
                        $state['checkbox_disabled'] = true;
                    }
                    $state['selected'] = in_array($v['id'], $selected);
                    $channelList[] = [
                        'id'     => $v['id'],
                        'parent' => $v['parent_id'] ? $v['parent_id'] : '#',
                        'text'   => __($v['name']),
                        'type'   => $v['type'],
                        'state'  => $state
                    ];
                }
                $this->success('成功', '', $channelList);
            } elseif ($act == 'save') {
                \think\Db::startTrans();
                try {
                    ChannelAdmin::where('admin_id', $ids)->delete();
                    $channelIds = explode(",", $this->request->post("ids"));
                    if ($channelIds) {
                        $listChannelIds = ChannelModel::where('type', 'list')->column('id');
                        $channelIds = array_intersect($channelIds, $listChannelIds);
                        $data = [];
                        foreach ($channelIds as $key => $item) {
                            $data[] = ['admin_id' => $ids, 'channel_id' => $item];
                        }
                        $model = new ChannelAdmin();
                        $model->saveAll($data, true);
                    }
                    \think\Db::commit();
                } catch (Exception $e) {
                    \think\Db::rollback();
                    $this->error($e->getMessage());
                }
                $this->success("保存成功!");
            }
        }

        if ($this->request->isAjax()) {
            $list = \think\Db::name("cms_channel_admin")
                ->group("admin_id")
                ->field("COUNT(*) as channels,admin_id")
                ->select();
            $adminChannelList = [];
            foreach ($list as $index => $item) {
                $adminChannelList[$item['admin_id']] = $item['channels'];
            }

            $superAdminIds = AuthGroupAccess::where('group_id', 1)->column('uid');

            $adminList = Admin::order('id', 'desc')->field('id,username,nickname')->select();
            foreach ($adminList as $index => $item) {
                $item->channels = isset($adminChannelList[$item['id']]) ? $adminChannelList[$item['id']] : 0;
                $item->superadmin = in_array($item['id'], $superAdminIds);
            }
            $total = count($adminList);
            $result = array("total" => $total, "rows" => $adminList);

            return json($result);
        }
        $config = get_addon_config('cms');
        $this->view->assign("isChannelAllocate", $config['channelallocate']);
        return $this->view->fetch();
    }

    /**
     * Selectpage搜索
     *
     * @internal
     */
    public function selectpage()
    {
        return parent::selectpage();
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
        if ($name == 'diyname') {
            if ($id) {
                $this->model->where('id', '<>', $id);
            }
            $exist = $this->model->where($name, $value)->find();
            if ($exist) {
                $this->error(__('The data already exist'));
            } else {
                $this->success();
            }
        } elseif ($name == 'name') {
            $nameArr = array_filter(explode("\n", str_replace("\r\n", "\n", $value)));
            if (count($nameArr) > 1) {
                foreach ($nameArr as $index => $item) {
                    $itemArr = array_filter(explode('|', $item));
                    if (!isset($itemArr[1])) {
                        $this->error('格式:分类名称|自定义名称');
                    }
                    $exist = \app\admin\model\cms\Channel::getByDiyname($itemArr[1]);
                    if ($exist) {
                        $this->error('自定义名称[' . $itemArr[1] . ']已经存在');
                    }
                }
                $this->success();
            } else {
                $this->success();
            }
        }
    }
}
