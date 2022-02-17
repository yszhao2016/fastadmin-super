<?php

namespace app\admin\controller\hj212;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Pollutionsite extends Backend
{

    /**
     * Site模型对象
     * @var \app\admin\model\hj212\Pollutionsite
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\hj212\PollutionSite;
        $siteId= $this->request->param('site_id', 0);
        $this->assignconfig('id',$siteId);
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
   
    public function siteinfo()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        $siteId= $this->request->param('site_id', 0);
        
        $list = $this->model
        ->where(function ($query) use ($siteId) {
            if ($siteId) {
                $query->where('id', $siteId);
            }
        })
        ->find();
        
        $this->view->assign('pollutionsite',$list);
        return $this->view->fetch();
        
    }
    

}
