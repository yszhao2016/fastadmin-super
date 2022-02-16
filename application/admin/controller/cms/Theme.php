<?php

namespace app\admin\controller\cms;

use app\common\controller\Backend;
use think\Session;

/**
 * 移动端主题
 *
 * @icon fa fa-gears
 */
class Theme extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isPost()) {
            $preview = $this->request->post('preview');
            $navbar = $this->request->post('navbar/a', []);
            $theme = $this->request->post('theme/a', []);
            $tabbar = $this->request->post('tabbar/a', []);

            $tabbar['midButton'] = (bool)$tabbar['midButton'];
            $tabbar['borderTop'] = (bool)$tabbar['borderTop'];
            if (isset($tabbar['list'])) {
                foreach ($tabbar['list'] as $index => &$item) {
                    $item['midButton'] = isset($item['midButton']) && $item['midButton'] ? true : false;
                    $item = array_merge($item, [
                        'count'        => 0,
                        'isDot'        => false,
                        'badgeColor'   => $theme['color'], //字体颜色
                        'badgeBgColor' => $theme['bgColor'], //背景颜色
                    ]);
                }
                $tabbar['list'] = array_values($tabbar['list']);
            }
            $navbar['isshow'] = true;
            $tabbar['isshow'] = true;
            $config = [
                'navbar' => $navbar,
                'theme'  => $theme,
                'tabbar' => $tabbar,
            ];

            //如果是预览模式则写入session
            if ($preview) {
                Session::set("previewtheme", $config);
            } else {
                \addons\cms\library\Theme::set($config);
            }
            $this->success();
        }
        $config = \addons\cms\library\Theme::get();
        $this->view->assign("themeConfig", $config);
        $this->assignconfig("themeConfig", $config);
        return $this->view->fetch();
    }

}
