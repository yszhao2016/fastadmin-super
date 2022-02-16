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
class Archives extends Frontend
{
    protected $layout = 'default';
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 发表文章
     */
    public function post()
    {
        $config = get_addon_config('cms');
        $id = $this->request->get('id');
        $archives = $id ? \app\admin\model\cms\Archives::get($id) : null;

        if ($archives) {
            $channel = Channel::get($archives['channel_id']);
            if (!$channel) {
                $this->error(__('未找到指定栏目'));
            }
            $model = \addons\cms\model\Modelx::get($channel['model_id']);
            if (!$model) {
                $this->error(__('未找到指定模型'));
            }
            if ($archives['user_id'] != $this->auth->id) {
                $this->error("无法进行越权操作！");
            }
        } else {
            $model = null;
            $model_id = $this->request->request('model_id');
            // 如果有model_id则调用指定模型
            if ($model_id) {
                $model = Modelx::get($model_id);
            }
        }

        // 如果来源于提交
        if ($this->request->isPost()) {
            $this->token();
            if ($this->auth->score < $config['limitscore']['postarchives']) {
                $this->error("积分必须大于{$config['limitscore']['postarchives']}才可以发布文章");
            }

            $row = $this->request->post('row/a', '', 'trim,xss_clean');

            $rule = [
                'title|标题'      => 'require|length:3,100',
                'channel_id|栏目' => 'require|integer',
            ];

            $msg = [
                'title.require'   => '标题不能为空',
                'title.length'    => '标题长度限制在3~100个字符',
                'channel_id'      => '栏目不能为空',
                'content.require' => '内容不能为空',
            ];
            $validate = new Validate($rule, $msg);
            $result = $validate->check($row);
            if (!$result) {
                $this->error($validate->getError());
            }

            $channelIds = isset($row['channel_ids']) ? (is_array($row['channel_ids']) ? $row['channel_ids'] : explode(',', $row['channel_ids'])) : [];
            $channelIds = array_merge([$row['channel_id']], $channelIds);
            $channelIds = array_filter($channelIds);
            $count = Channel::where('id', 'in', $channelIds)->where('iscontribute', 0)->count();
            if ($count > 0) {
                $this->error("栏目不允许投稿");
            }

            //审核状态
            $status = 'normal';
            if ($config['isarchivesaudit'] == 1) {
                $status = 'hidden';
            } elseif ($config['isarchivesaudit'] == 0) {
                $status = 'normal';
            } else {
                $textArr = array_map(function ($item) {
                    return is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE) : $item;
                }, $row);
                if (!Service::isContentLegal(implode(' ', $textArr))) {
                    $status = 'hidden';
                }
            }

            $row['user_id'] = $this->auth->id;
            $row['status'] = $status;
            $row['publishtime'] = time();

            Db::startTrans();
            try {
                if ($archives) {
                    $archives->allowField(true)->save($row);
                } else {
                    (new \app\admin\model\cms\Archives)->allowField(true)->save($row);
                }
                //增加积分
                $status == 'normal' && User::score($config['score']['postarchives'], $this->auth->id, '发布文章');
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error("发生错误:" . $e->getMessage());
            }
            if ($status === 'hidden') {
                //发送通知
                $status === 'hidden' && Service::notice('CMS收到一篇新的文章审核', $config['auditnotice'], $config['noticetemplateid']);
                $this->success("发布成功！请等待审核!");
            } else {
                $this->success("发布成功！");
            }
        }


        // 合并主副表
        if ($archives) {
            $addon = db($model['table'])->where('id', $archives['id'])->find();
            if ($addon) {
                $archives->setData($addon);
            }
        }

        if (!$archives && !$model) {
            $firstChannel = Channel::where('status', 'normal')->where('iscontribute', 1)->order('weigh DESC,id DESC')->find();
            $model = Modelx::get($firstChannel['model_id']);
        }

        list($channelList, $disabledIds) = Channel::getContributeInfo($archives, $model);

        $tree = Tree::instance()->init($channelList, 'parent_id');
        $channelOptions = $tree->getTree(0, "<option model='@model_id' value=@id @selected @disabled>@spacer@name</option>", $archives ? $archives['channel_id'] : '', $disabledIds);
        $this->view->assign('channelOptions', $channelOptions);
        $this->view->assign([
            'archives'       => $archives,
            'channelOptions' => $channelOptions,
            'categoryList'   => '',
            'extendHtml'     => $this->get_model_extend_html($archives, $model)
        ]);
        $this->assignconfig('archives_id', $archives ? $archives['id'] : 0);

        $tree = Tree::instance()->init($channelList, 'parent_id');
        $secondChannelOptions = $tree->getTree(0, "<option model='@model_id' value=@id @selected @disabled>@spacer@name</option>", explode(',', $archives ? $archives['channel_ids'] : ''), $disabledIds);
        $this->view->assign('secondChannelOptions', $secondChannelOptions);

        $modelName = $model ? $model['name'] : '文章';
        $this->view->assign('title', $archives ? "修改{$modelName}" : "发布{$modelName}");
        $this->view->assign('model', $model);
        return $this->view->fetch();
    }

    /**
     * 我的发布
     */
    public function my()
    {
        $archives = new \addons\cms\model\Archives;
        $model = null;
        $model_id = (int)$this->request->request('model_id');
        $channel_id = (int)$this->request->request('channel_id');
        $q = $this->request->request('q');
        $config = ['query' => []];

        // 指定模型
        if ($model_id) {
            $model = Modelx::get($model_id);
            if ($model) {
                $archives->where('model_id', $model_id);
                $config['query']['model_id'] = $model_id;
            }
        }

        // 搜索关键字
        if ($q) {
            $archives->where('title|keywords|description', 'like', '%' . $q . '%');
            $config['query']['q'] = $q;
        }

        // 栏目
        if ($channel_id) {
            $archives->where('channel_id', $channel_id);
            $config['query']['channel_id'] = $channel_id;
        }

        $user_id = $this->auth->id;
        $archivesList = $archives->where('user_id', $user_id)
            ->order('id', 'desc')
            ->paginate(10, null, $config);

        $channelList = Channel::where('id', 'in', function ($query) use ($user_id) {
            $query->name('cms_archives')->where('user_id', $user_id)->field('channel_id');
        })->where('status', 'normal')->select();
        $this->view->assign('archivesList', $archivesList);
        $this->view->assign('channelList', $channelList);
        $this->view->assign('title', '我发布的' . ($model ? $model['name'] : '文档'));
        $this->view->assign('model', $model);
        return $this->view->fetch();
    }

    /**
     * 删除文档
     */
    public function delete()
    {
        $id = (int)$this->request->request('id/d');
        if (!$id) {
            $this->error("参数不正确");
        }
        $archives = \addons\cms\model\Archives::where('id', $id)->where('user_id', $this->auth->id)->find();
        if (!$archives) {
            $this->error("未找到指定的文档");
        }
        Db::startTrans();
        try {
            $archives->delete();
            if ($archives->channel->items > 0) {
                $archives->channel->setDec("items");
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error("删除文档失败");
        }
        $this->success("删除文档成功");
    }

    /**
     * 获取栏目列表
     * @internal
     */
    public function get_channel_fields()
    {
        $this->view->engine->layout(false);
        $channel_id = $this->request->post('channel_id');
        $archives_id = $this->request->post('archives_id');
        $channel = Channel::get($channel_id, 'model');
        if ($channel && $channel['type'] != 'link') {
            $archives = null;
            $model = null;
            $model_id = $channel['model_id'];
            if ($archives_id) {
                $archives = \app\admin\model\cms\Archives::get($archives_id);
                $model_id = $archives ? $archives['model_id'] : $model_id;
            }
            $model = Modelx::get($model_id);

            $setting = $model->setting ?? [];
            $html = $this->get_model_extend_html($archives, $model);
            $this->success('', null, ['contributefields' => $setting['contributefields'] ?? [], 'html' => $html]);
        } else {
            $this->error(__('请选择栏目'));
        }
        $this->error(__('参数不能为空', 'ids'));
    }

    /**
     * 获取模型的扩展HTML
     * @internal
     */
    protected function get_model_extend_html($archives, $model)
    {
        if (!$archives && !$model) {
            return '';
        }
        $fields = [];
        $values = [];
        if ($archives) {
            $model = $model ? $model : Modelx::get($archives['model_id']);
            $values = db($model['table'])->where('id', $archives['id'])->find();
        }
        $fields = Service::getCustomFields('model', $model['id'], $values, ['iscontribute' => 1]);

        return $this->view->fetch('cms/common/fields', ['fields' => $fields, 'values' => $values]);
    }

    /**
     * 标签自动完成
     * @internal
     */
    public function tags_autocomplete()
    {
        $q = $this->request->request('q');
        $list = \addons\cms\model\Tag::where('name', 'like', '%' . $q . '%')->limit(10)->column('name');
        echo json_encode($list);
        return;
    }

    /**
     * 搜索建议
     * @internal
     */
    public function suggestion()
    {
        $q = trim($this->request->request("q"));
        $id = trim($this->request->request("id/d"));
        $list = [];
        $result = \addons\cms\model\Archives::where("title|keywords|description", "like", "%{$q}%")->where('id', '<>', $id)->limit(10)->order("id", "desc")->select();
        foreach ($result as $index => $item) {
            $list[] = ['id' => $item['id'], 'url' => $item['fullurl'], 'image' => cdnurl($item['image']), 'title' => $item['title'], 'create_date' => datetime($item['createtime'])];
        }
        return json($list);
    }
}
