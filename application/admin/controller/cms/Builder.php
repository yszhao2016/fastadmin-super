<?php

namespace app\admin\controller\cms;

use addons\cms\library\Service;
use app\admin\model\cms\Channel;
use app\admin\model\cms\Modelx;
use app\common\controller\Backend;
use app\common\model\User;
use fast\Tree;
use think\Db;
use think\db\Query;

/**
 * 标签生成器
 *
 * @icon fa fa-file-text-o
 */
class Builder extends Backend
{

    protected $model = null;
    protected $noNeedRight = [];
    protected $channelIds = [];
    protected $isSuperAdmin = false;
    protected $searchFields = 'id,title';

    /**
     * 查看
     */
    public function index()
    {
        $tree = Tree::instance();
        $tree->init(collection(Channel::where('status', 'normal')->order('weigh desc,id desc')->select())->toArray(), 'parent_id');
        $channelList = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $modelList = \app\admin\model\cms\Modelx::order('id asc')->select();

        $prefix = \think\Config::get('database.prefix');
        $fieldList = Service::getTableFields("{$prefix}cms_archives");
        $channelFieldList = Service::getTableFields("{$prefix}cms_channel");
        $userFieldList = Service::getTableFields("{$prefix}user");
        $specialFieldList = Service::getTableFields("{$prefix}cms_special");
        $pageFieldList = Service::getTableFields("{$prefix}cms_page");
        $pageTypeList = \app\admin\model\cms\Page::distinct('type')->column("type");
        $blockTypeList = \app\admin\model\cms\Block::distinct('type')->column("type");
        $blockNameList = \app\admin\model\cms\Block::distinct('name')->column("name");

        $blockFieldList = Service::getTableFields("{$prefix}cms_block");
        $diyformList = \app\admin\model\cms\Diyform::all();
        $diyformFieldList = [];
        foreach ($diyformList as $index => $item) {
            $diyformFieldList[$item['id']] = Service::getTableFields($prefix . $item['table']);
        }

        $this->view->assign("configList", get_addon_fullconfig("cms"));
        $this->view->assign("fieldList", $fieldList);
        $this->view->assign("channelFieldList", $channelFieldList);
        $this->view->assign("pageFieldList", $pageFieldList);
        $this->view->assign("pageTypeList", $pageTypeList);
        $this->view->assign("specialFieldList", $specialFieldList);
        $this->view->assign("blockFieldList", $blockFieldList);
        $this->view->assign("blockTypeList", $blockTypeList);
        $this->view->assign("blockNameList", $blockNameList);
        $this->view->assign("userFieldList", $userFieldList);
        $this->view->assign("diyformList", $diyformList);
        $this->view->assign("diyformFieldList", $diyformFieldList);
        $this->view->assign("channelList", $channelList);
        $this->view->assign("modelList", $modelList);
        return $this->view->fetch();
    }

    /**
     * 解析模板标签
     * @return string
     */
    public function parse()
    {
        $this->view->engine->layout(false);
        $tag = $this->request->post('tag');
        if (!config('app_debug')) {
            $this->error("只在开发模式下才可渲染");
        }
        $html = '';
        try {
            $html = $this->view->display($tag);
        } catch (\Exception $e) {
            $this->error("模板标签解析错误：" . $e->getMessage());
        }
        $this->success("", null, $html);
        return $this->view->fetch();
    }

    /**
     * 获取自定义字段列表HTML
     * @internal
     */
    public function get_model_fields()
    {
        $this->view->engine->layout(false);
        $id = $this->request->post('id/d');
        $model = Modelx::get($id);
        if ($model) {
            $fields = \app\admin\model\cms\Fields::where('source', 'model')->where('source_id', $model['id'])->column("id,name,title");
            $this->success('', null, ['fields' => array_values($fields)]);
        } else {
            $this->error(__('Please select model'));
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

}
