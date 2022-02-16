<?php

namespace app\admin\controller\cms;

use app\common\controller\Backend;

/**
 * 标签表
 *
 * @icon fa fa-tags
 */
class Tag extends Backend
{

    /**
     * Tag模型对象
     */
    protected $model = null;
    protected $noNeedRight = ['selectpage', 'autocomplete'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\cms\Tag;
    }

    public function selectpage()
    {
        $response = parent::selectpage();
        $word = (array)$this->request->request("q_word/a");
        if (array_filter($word)) {
            $result = $response->getData();
            $list = [];
            foreach ($result['list'] as $index => $item) {
                $list[] = strtolower($item['name']);
            }
            foreach ($word as $k => $v) {
                if (!in_array(strtolower($v), $list)) {
                    array_unshift($result['list'], ['id' => $v, 'name' => $v]);
                }
                $result['total']++;
            }
            $response->data($result);
        }
        return $response;
    }

    public function autocomplete()
    {
        $q = $this->request->request('q');
        $list = \app\admin\model\cms\Tag::where('name', 'like', '%' . $q . '%')->column('name');
        echo json_encode($list);
        return;
    }

}
