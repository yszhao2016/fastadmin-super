<?php

namespace app\index\controller\cms;

use addons\cms\library\FulltextSearch;
use addons\cms\library\Service;
use addons\cms\model\Channel;
use addons\cms\model\Modelx;
use addons\cms\model\Tag;
use app\common\controller\Frontend;
use app\common\model\User;
use fast\Tree;
use think\Db;
use think\Exception;
use think\Validate;

/**
 * 会员文档
 */
class Collection extends Frontend
{
    protected $layout = 'default';
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 我的收藏
     */
    public function index()
    {
        $collection = new \addons\cms\model\Collection;
        $model = null;
        $type = (int)$this->request->request('type');
        $q = $this->request->request('q');
        $config = ['query' => []];

        // 指定模型
        if ($type) {
            $model = Modelx::get($type);
            if ($model) {
                $collection->where('type', $type);
                $config['query']['type'] = $type;
            }
        }

        // 搜索关键字
        if ($q) {
            $collection->where('title|keywords|description', 'like', '%' . $q . '%');
            $config['query']['q'] = $q;
        }

        $user_id = $this->auth->id;
        $collectionList = $collection->where('user_id', $user_id)
            ->order('id', 'desc')
            ->paginate(10, null, $config);

        $this->view->assign('collectionList', $collectionList);
        $this->view->assign('title', '我收藏的' . ($model ? $model['name'] : '文档'));
        $this->view->assign('model', $model);
        return $this->view->fetch();
    }

    /**
     * 删除收藏
     */
    public function delete()
    {
        $id = (int)$this->request->request('id/d');
        if (!$id) {
            $this->error("参数不正确");
        }
        $collection = \addons\cms\model\Collection::where('id', $id)->where('user_id', $this->auth->id)->find();
        if (!$collection) {
            $this->error("未找到指定的收藏");
        }
        Db::startTrans();
        try {
            $collection->delete();
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error("移除收藏失败");
        }
        $this->success("移除收藏成功");
    }

}
