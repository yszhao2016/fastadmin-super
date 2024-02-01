<?php
/**
 * Created by PhpStorm
 * USER:  Zhaoys
 * Date:  2024/1/19
 */

namespace app\api\controller;

use app\admin\model\hj212\PollutionSite;
use app\common\controller\Api;
use app\common\library\Utils;

/**
 *
 * Class MonitorSite
 * @package app\api\controller
 */
class MonitorSite extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = [""];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ["*"];

    /**
     * 监测站点列表
     * @method GET
     * @page       页数
     * @pagesize   每页数量
     * @throws \think\exception\DbException
     */
    public function  list()
    {

        $page = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', 10);
        $search = $this->request->get('search');

        $model = PollutionSite::order('id desc');
        if ($search) {
            $model = $model->where('site_name', "like", "%$search%");
        }
        $list = $model->paginate($pagesize, false, ['page' => $page]);
        $this->success("成功", $list);
    }

    /**
     * 站点列表接口  加报警字段
     */
    public function list1()
    {

        $page = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', 10);
        $search = $this->request->get('search');

        $model = PollutionSite::order('id desc');
        if ($search) {
            $model = $model->where('site_name', "like", "%$search%");
        }
        $list = $model->paginate($pagesize, false, ['page' => $page]);
        $res = $list->toArray();
        foreach ($res['data'] as $k => $site) {
            $isAlarm = Utils::getSiteIsAlarm($site['id']);
            $res['data'][$k]['is_alarm'] = $isAlarm ? 1 : 0;
        }
        $this->success("成功", $res);
    }

    /**
     * 监测点详情
     * @method GET
     * @param id
     */
    public function detail()
    {
        $id = $this->request->get('id');
        if (!$id) {
            $this->error("缺少必要参数");
        }
        $data = PollutionSite::get($id);
        $this->success("成功", $data);
    }

    /**
     * 检查站点是否报警接口
     */
    public function checkSiteIsAlarm()
    {
        $site_id = $this->request->get('site_id');
        $isAlarm = Utils::getSiteIsAlarm($site_id);
        $res["is_alarm"] = $isAlarm ? 1 : 0;
        $this->success("成功", $res);
    }

}