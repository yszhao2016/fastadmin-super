<?php

namespace app\index\controller\cms;

use app\common\controller\Frontend;

/**
 * 我发表的评论
 */
class Comment extends Frontend
{
    protected $layout = 'default';
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 我的评论
     */
    public function index()
    {
        $user_id = $this->auth->id;
        $commentList = \addons\cms\model\Comment::where('user_id', $user_id)
            ->where('status', 'normal')
            ->order('id', 'desc')
            ->paginate(10, null);

        $this->view->assign('config', array_merge($this->view->config, ['jsname' => '']));
        $this->view->assign('commentList', $commentList);
        $this->view->assign('title', '我发表的评论');
        return $this->view->fetch();
    }

}
