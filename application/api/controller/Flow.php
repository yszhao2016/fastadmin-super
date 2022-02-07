<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\FlowEngine;
use think\Db;

/**
 * 流程接口
 */
class Flow extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

    protected $flow = null;
    /**
     * 发起流程
     *
     * @ApiTitle    (发起流程)
     * @ApiSummary  (发起流程)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/flow/start)
     * @ApiParams   (name="flowcode", type="string", required=true, description="流程编码")
     * @ApiParams   (name="userid", type="string", required=true, description="用户id")
     * @ApiParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据返回")
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function start()
    {
        try {
            $flowcode = $this->request->request("flowcode");
            $data = $this->request->post("data");
            $userid = $this->request->post("userid");
            $data = json_decode(html_entity_decode($data), true);
            $row = Db::name('flow_scheme')->where(['flowcode' => $flowcode])->find();
            if (!$row) {
                $this->error(__('流程代码不存在'));
            }
            if (!$data) {
                $this->error(__('数据不能为空'));
            }
            if (!$userid) {
                $this->error(__('用户id不能为空'));
            }
            $this->flow = new FlowEngine($flowcode,$userid);
            $ids =  $this->flow->start($data);
            $this->success('返回成功', $ids);
        } catch (\think\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 保存草稿
     *
     * @ApiTitle    (保存草稿)
     * @ApiSummary  (保存草稿)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/flow/save)
     * @ApiParams   (name="flowcode", type="string", required=true, description="流程编码")
     * ApiParams   (name="userid", type="string", required=true, description="用户id")
     * @ApiParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据返回")
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function save()
    {
        try {
            $flowcode = $this->request->request("flowcode");
            $data = $this->request->request("data");
            $data = json_decode(html_entity_decode($data), true);
            $userid = $this->request->post("userid");
            $row = Db::name('flow_scheme')->where(['flowcode' => $flowcode])->find();
            if (!$row) {
                $this->error(__('流程代码不存在'));
            }
            if (!$data) {
                $this->error(__('数据不能为空'));
            }
            if (!$userid) {
                $this->error(__('用户id不能为空'));
            }
            $this->flow = new FlowEngine($flowcode,$userid);
            $ids = $this->flow->save($data);
            $this->success('返回成功', $ids);
        } catch (\think\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 同意
     *
     * @ApiTitle    (同意)
     * @ApiSummary  (同意)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/flow/agree)
     * @ApiParams   (name="flowcode", type="string", required=true, description="流程编码")
     * @ApiParams   (name="taskid", type="string", required=true, description="任务id")
     * ApiParams   (name="userid", type="string", required=true, description="用户id")s
     * @ApiParams   (name="comment", type="string", required=true, description="审批意见")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据返回")
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function agree()
    {
        try {
            $flowcode = $this->request->request("flowcode");
            $data = $this->request->post("data");
            $taskid = $this->request->request("taskid");
            $comment = $this->request->request("comment");
            $data = json_decode(html_entity_decode($data), true);
            $userid = $this->request->post("userid");
            $row = Db::name('flow_scheme')->where(['flowcode' => $flowcode])->find();
            if (!$row) {
                $this->error(__('流程代码不存在'));
            }
            if (!$userid) {
                $this->error(__('用户id不能为空'));
            }
            $this->flow = new FlowEngine($flowcode,$userid);
            $this->flow->next($taskid, $data, '',$comment);
            $this->success('返回成功', ['action' => 'agree']);
        } catch (\think\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 拒绝
     *
     * @ApiTitle    (拒绝)
     * @ApiSummary  (拒绝)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/flow/refuse)
     * @ApiParams   (name="flowcode", type="string", required=true, description="流程编码")
     * @ApiParams   (name="taskid", type="string", required=true, description="任务id")
     * ApiParams   (name="userid", type="string", required=true, description="用户id")
     * @ApiParams   (name="comment", type="string", required=true, description="审批意见")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function refuse()
    {
        try {
            $flowcode = $this->request->request("flowcode");
            $taskid = $this->request->request("taskid");
            $comment = $this->request->request("comment");
            $userid = $this->request->post("userid");
            $row = Db::name('flow_scheme')->where(['flowcode' => $flowcode])->find();
            if (!$row) {
                $this->error(__('流程代码不存在'));
            }
            if (!$userid) {
                $this->error(__('用户id不能为空'));
            }
            $this->flow = new FlowEngine($flowcode,$userid);
            $this->flow->refuse($taskid, $comment);
            $this->success('返回成功', ['action' => 'refuse']);
        } catch (\think\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 取消
     *
     * @ApiTitle    (取消)
     * @ApiSummary  (取消)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/flow/cancel)
     * @ApiParams   (name="flowcode", type="string", required=true, description="流程编码")
     * @ApiParams   (name="taskid", type="string", required=true, description="任务id")
     * ApiParams   (name="userid", type="string", required=true, description="用户id")
     * @ApiParams   (name="comment", type="string", required=true, description="审批意见")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function cancel()
    {
        try {
            $flowcode = $this->request->request("flowcode");
            $taskid = $this->request->request("taskid");
            $comment = $this->request->request("comment");
            $row = Db::name('flow_scheme')->where(['flowcode' => $flowcode])->find();
            $userid = $this->request->post("userid");
            if (!$row) {
                $this->error(__('流程代码不存在'));
            }
            if (!$userid) {
                $this->error(__('用户id不能为空'));
            }
            $this->flow = new FlowEngine($flowcode,$userid);
            $this->flow->cancel($taskid, $comment);
            $this->success('返回成功', ['action' => 'cancel']);
        } catch (\think\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取流程实例
     *
     * @ApiTitle    (获取流程实例)
     * @ApiSummary  (获取流程实例)
     * @ApiMethod   (GET)
     * @ApiRoute    (/api/flow/getInstance)
     * @ApiParams   (name="flowcode", type="string", required=true, description="流程编码")
     * @ApiParams   (name="offset", type="int", required=true, description="当前页")
     * @ApiParams   (name="limit", type="int", required=true, description="条数")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function getInstance()
    {
        $flowcode = $this->request->request("flowcode");
        $offset = $this->request->request("offset") ? $this->request->request("offset") : 0;
        $limit = $this->request->request("limit") ? $this->request->request("limit") : 10;
        $row = Db::name('flow_scheme')->where(['flowcode' => $flowcode])->find();
        $where = [];
        if ($row) {
            $where = ['scheme' => $row['id']];
        }
        $sort = 'createtime';
        $order = 'desc';
        $total = Db::name('view_flow_instance')
            ->where($where)
            ->order($sort, $order)
            ->count();

        $list = Db::name('view_flow_instance')
            ->where($where)
            ->order($sort, $order)
            ->limit($offset, $limit)
            ->select();

        $result = array("total" => $total, "rows" => $list);

        $this->success('返回成功', $result);
    }

    /**
     * 获取流程任务
     *
     * @ApiTitle    (获取流程任务)
     * @ApiSummary  (获取流程任务)
     * @ApiMethod   (GET)
     * @ApiRoute    (/api/flow/getTask)
     * @ApiParams   (name="flowcode", type="string", required=true, description="流程编码")
     * @ApiParams   (name="offset", type="int", required=true, description="当前页")
     * @ApiParams   (name="limit", type="int", required=true, description="条数")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}", description="扩展数据返回")
     * @ApiReturn   ({
         'code':'1',
         'msg':'返回成功'
        })
     */
    public function getTask()
    {
        $flowcode = $this->request->request("flowcode");
        $offset = $this->request->request("offset") ? $this->request->request("offset") : 0;
        $limit = $this->request->request("limit") ? $this->request->request("limit") : 10;
        $row = Db::name('flow_scheme')->where(['flowcode' => $flowcode])->find();
        $where = [];
        if ($row) {
            $where = ['scheme' => $row['id']];
        }
        $where = ['scheme' => $row['id']];
        $sort = 'createtime';
        $order = 'desc';
        $total = Db::name('view_flow_workitem')
            ->where($where)
            ->order($sort, $order)
            ->count();

        $list = Db::name('view_flow_workitem')
            ->where($where)
            ->order($sort, $order)
            ->limit($offset, $limit)
            ->select();

        $result = array("total" => $total, "rows" => $list);
        $this->success('返回成功', $result);
    }
}
