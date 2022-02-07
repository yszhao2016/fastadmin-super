<?php

namespace app\admin\controller\flow;

use app\common\controller\Backend;
use app\common\library\FlowEngine;
use think\Config;
use think\Db;
use think\Exception;
use fast\Random;
use think\addons\Service;
use think\Cache;
use think\Lang;

/**
 * 分组管理
 *
 * @icon fa fa-circle-o
 */
class Formbuild extends Backend
{

    /**
     * Formbuild模型对象
     * @var \app\admin\model\flow\Formbuild
     */
    protected $model = null;
    protected $number = null;
    protected $flow = null;
    protected $modelTable = null;
    protected $cols = null;
    protected $scheme = null;
    protected $prefix = "";
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\flow\Scheme();
        $this->number = new \app\admin\model\flow\Number();
        $schemeId = $this->request->request('ids');
        $this->prefix = Config::get('database.prefix');
        $this->scheme = $this->model->get($schemeId);

        //如果发送的来源是Selectpage，则转发到Selectpage
        $dbname = Config::get('database.database');
        $prefix = Config::get('database.prefix');
        //从数据库中获取表字段信息
        $sql = "SELECT COLUMN_NAME FROM `information_schema`.`columns` "
            . "WHERE TABLE_SCHEMA = ? AND table_name = ? "
            . "ORDER BY ORDINAL_POSITION";
        //加载主表的列
        $columnList = Db::query($sql, [$dbname, $this->scheme['bizscheme']]);
        $data = [];
        foreach ($columnList as $t) {
            if ($t['COLUMN_NAME'] != 'id') {
                $data[] = $t['COLUMN_NAME'];
            }
        }
        $this->cols = $data;
    }

    public function index()
    {
        $this->view->assign("jsondata", $this->scheme['frmcode']);
        $this->view->assign("cols", json_encode($this->cols));
        $this->view->assign("schemeid", $this->request->request('ids'));
        $domain = $this->request->domain() . config('view_replace_str.__PUBLIC__');
        $this->view->assign("domain", $domain);
        return $this->view->fetch();
    }
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("frmcode");
            if ($params) {
                try {
                    $result = $row->allowField(true)->save(['frmcode' => $params,  'updatetime' => date("Y-m-d h:i:s"), 'updateuser' => $this->auth->id]);
                    if ($result !== false) {
                        $result = array('code' => 200, 'msg' => '表单保存成功');
                        return json($result);
                    } else {
                        $this->error($row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    public function form($ids = null)
    {
        $schemeId = $this->request->request('ids');
        $taskId = $this->request->request('taskid');
        $task = null;
        $mode = $this->request->request('mode');
        $bizobjectid = $this->request->request('bizobjectid');
        $result['schemeid'] = $schemeId;
        $result['frmJson'] = $this->scheme['frmcode'];
        $result['cols'] = $this->cols;
        $result['mode'] = $mode;
        $result['domain'] = $this->request->domain() . config('view_replace_str.__PUBLIC__');
        //获取历史记录
        if (($mode == 'edit' || $mode == 'view' || $mode == 'submit') && $bizobjectid != '') {
            $row = Db::table($this->scheme['bizscheme'])->where('id', $bizobjectid)->find();
            $task = Db::name('flow_task')->where('id', $taskId)->find();
            $result['frmValue'] = $row;
            $history = Db::name('flow_task')
                ->alias('main')
                ->join($this->prefix . 'admin admin', 'admin.id=main.receiveid', 'LEFT')
                ->where(['instanceid' => $task['instanceid'], 'main.status' => 2])
                ->field(["main.receiveid", "main.stepname", "main.comment", "admin.nickname", "main.completedtime"])
                ->order('main.createtime asc,main.completedtime asc')
                ->select();
            $result['history'] = $history;
        }
        //获取字段编辑权限
        if ($mode == 'edit' || $mode == 'start') {
            if($mode == 'edit'){
                $flow_id = $task['flowid'];
                $node_id = $task['stepid'];
            }
            else{
                //如果是开始节点获取开始阶段id
                $flow_id = $this->request->request('ids');
                $content = json_decode($this->scheme->flowcontent, true);
                $nodes = $content['nodes'];
                $rtn = array_search('start', array_column($nodes, 'type'));
                $node_id = $nodes[$rtn]['id'];
            }
            $right = $this->getNodeField($flow_id, $node_id);
            $json = json_decode($result['frmJson'], true);
            if ($right) {               
                for($index= 0;$index< count($json['list']);$index++){

                    $rightRow = $this->filter_by_value($right,"field",$json['list'][$index]['model']);
                    if($rightRow['read']==0){
                        unset($json['list'][$index]);
                    }
                    else if($rightRow['read']==1 && $rightRow['write']==0){
                        $json['list'][$index]['options']['disabled']=true;
                    }else{

                    }
                }
                $json['list'] =  array_values($json['list']);
            }
            $result['frmJson'] = json_encode($json) ;
        }
        if ($mode == 'start') {
            $result['serial_no'] = $this->getnumber($this->scheme['flowcode']);
        }

        $this->view->assign("result", json_encode($result));
        return $this->view->fetch();
    }

    public function start()
    {
        try {
            $ids = $this->request->request('ids');
            $data = $this->request->post();
            $data = $this->getFormField($data['data']);
            $this->flow = new FlowEngine($this->scheme['flowcode']);
            $this->flow->start($data);
            $result = array('code' => 200, 'msg' => '流程提交成功');
            return json($result);
        } catch (Exception $e) {
            $result = array('code' => 500, 'msg' => '流程提交失败请联系管理员', 'ex' => $e->getMessage());
            return json($result);
        }
    }

    public function save()
    {
        try {
            $ids = $this->request->request('ids');
            $data = $this->request->post();
            $data = $this->getFormField($data['data']);
            $this->flow = new FlowEngine($this->scheme['flowcode']);
            $this->flow->save($data);
            $result = array('code' => 200, 'msg' => '流程保存成功');
            return json($result);
        } catch (Exception $e) {
            $result = array('code' => 500, 'msg' => '流程保存失败请联系管理员');
            return json($result);
        }
    }

    public function agree()
    {
        try {
            $taskid = $this->request->request('taskid');
            $bizobjectid = $this->request->request('bizobjectid');
            $data = $this->request->post();
            $comment = $data['comment'] ? $data['comment'] : '同意';
            $data = $this->getFormField($data['data']);
            $data['id'] = $bizobjectid;
            $this->flow = new FlowEngine($this->scheme['flowcode']);
            $this->flow->next($taskid, $data, $comment);
            $result = array('code' => 200, 'msg' => '审批成功');
            return json($result);
        } catch (Exception $e) {
            $result = array('code' => 500, 'msg' => '流程审核失败请联系管理员', 'ex' => $e->getMessage());
            return json($result);
        }
    }

    public function cancel()
    {
        try {
            $taskid = $this->request->request('taskid');
            $comment = $this->request->post('comment') == '' ? '[取消]' : $this->request->post('comment');
            $this->flow = new FlowEngine($this->scheme['flowcode']);
            $this->flow->cancel($taskid, $comment);
            $result = array('code' => 200, 'msg' => '取消成功');
            return json($result);
        } catch (Exception $e) {
            $result = array('code' => 500, 'msg' => '流程审核失败请联系管理员');
            return json($result);
        }
    }

    public function refuse()
    {
        try {
            $this->flow = new FlowEngine($this->scheme['flowcode']);
            $taskid = $this->request->request('taskid');
            $comment = $this->request->post('comment') == '' ? '[拒绝]' : $this->request->post('comment');
            $this->flow->refuse($taskid, $comment);
            $result = array('code' => 200, 'msg' => '拒绝成功');
            return json($result);
        } catch (Exception $e) {
            $result = array('code' => 500, 'msg' => '流程审核失败请联系管理员');
            return json($result);
        }
    }
    /**
     * 获取流水号
     */
    public function getnumber($code)
    {
        $row = $this->number->where(['code' => $code])->find();
        if (!$row) {
            return time();
        }
        $serial_no = '';
        $serial_no .= $row['pre'];
        if ($row['year'] == 'Y') {
            $serial_no .= Date('Y');
        }
        if ($row['month'] == 'Y') {
            $serial_no .= Date('m');
        }
        $serial_no .= str_pad($row['index'], $row['lengh'], "0", STR_PAD_LEFT);
        $row->allowField(true)->save(['index' => ($row['index'] + 1)]);
        return $serial_no;
    }

    /**
     * 获取可写入的字段
     */
    public function getFormField($data)
    {
        $result = [];
        foreach ($this->cols as $item) {
            if ((array_key_exists($item, $data) && $data[$item] != '') || $item == 'instancecode') {
                if (is_array($data[$item])) {
                    $result[$item] = json_encode($data[$item]);
                } else {
                    $result[$item] = $data[$item];
                }
            }
        }
        if (array_key_exists('instancecode', $data)) {
            $result['instancecode'] = $data['instancecode'];
        }
        return $result;
    }
    public function filter_by_value($array, $index, $value)
    {
        $newarray = ['read'=>0,'write'=>0];
        if (is_array($array) && count($array) > 0) {
            foreach (array_keys($array) as $key) {
                $temp[$key] = $array[$key][$index];
                if ($temp[$key] == $value) {
                    $newarray = $array[$key];
                }
            }
        }
        return $newarray;
    }
    /**
     * 获取节点授权字段默认是全部读
     */
    public function getNodeField($ids, $node)
    {

        $fieldList = Db::name('flow_field')
            ->where(['node_id' => $node, 'flow_id' => $ids])
            ->select();
        return $fieldList;
    }
    /**
     * 上传文件
     */
    public function upload()
    {
        $domain = $this->request->domain() . config('view_replace_str.__PUBLIC__');
        Config::set('default_return_type', 'json');
        $file = $this->request->file('image');
        if (empty($file)) {

            $file = $this->request->file('file');
        }
        if (empty($file)) {
            $this->error(__('No file upload or server upload limit exceeded'));
        }
        //判断是否已经存在附件
        $sha1 = $file->hash();
        $extparam = $this->request->post();

        $upload = Config::get('upload');

        preg_match('/(\d+)(\w+)/', $upload['maxsize'], $matches);
        $type = strtolower($matches[2]);
        $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
        $size = (int)$upload['maxsize'] * pow(1024, isset($typeDict[$type]) ? $typeDict[$type] : 0);
        $fileInfo = $file->getInfo();
        $suffix = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        $suffix = $suffix ? $suffix : 'file';

        $mimetypeArr = explode(',', strtolower($upload['mimetype']));
        $typeArr = explode('/', $fileInfo['type']);

        //验证文件后缀
        if (
            $upload['mimetype'] !== '*' &&
            (!in_array($suffix, $mimetypeArr)
                || (stripos($typeArr[0] . '/', $upload['mimetype']) !== false && (!in_array($fileInfo['type'], $mimetypeArr) && !in_array($typeArr[0] . '/*', $mimetypeArr))))
        ) {
            $this->error(__('Uploaded file format is limited'));
        }
        $replaceArr = [
            '{year}'     => date("Y"),
            '{mon}'      => date("m"),
            '{day}'      => date("d"),
            '{hour}'     => date("H"),
            '{min}'      => date("i"),
            '{sec}'      => date("s"),
            '{random}'   => Random::alnum(16),
            '{random32}' => Random::alnum(32),
            '{filename}' => $suffix ? substr($fileInfo['name'], 0, strripos($fileInfo['name'], '.')) : $fileInfo['name'],
            '{suffix}'   => $suffix,
            '{.suffix}'  => $suffix ? '.' . $suffix : '',
            '{filemd5}'  => md5_file($fileInfo['tmp_name']),
        ];
        $savekey = $upload['savekey'];
        $savekey = str_replace(array_keys($replaceArr), array_values($replaceArr), $savekey);

        $uploadDir = substr($savekey, 0, strripos($savekey, '/') + 1);
        $fileName = substr($savekey, strripos($savekey, '/') + 1);
        //
        $splInfo = $file->validate(['size' => $size])->move(ROOT_PATH . '/public' . $uploadDir, $fileName);
        if ($splInfo) {
            $imagewidth = $imageheight = 0;
            if (in_array($suffix, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'])) {
                $imgInfo = getimagesize($splInfo->getPathname());
                $imagewidth = isset($imgInfo[0]) ? $imgInfo[0] : $imagewidth;
                $imageheight = isset($imgInfo[1]) ? $imgInfo[1] : $imageheight;
            }
            $params = array(
                'admin_id'    => (int)$this->auth->id,
                'user_id'     => 0,
                'filesize'    => $fileInfo['size'],
                'imagewidth'  => $imagewidth,
                'imageheight' => $imageheight,
                'imagetype'   => $suffix,
                'imageframes' => 0,
                'mimetype'    => $fileInfo['type'],
                'url'         => $uploadDir . $splInfo->getSaveName(),
                'uploadtime'  => time(),
                'storage'     => 'local',
                'sha1'        => $sha1,
                'extparam'    => json_encode($extparam),
            );
            $attachment = model("attachment");
            $attachment->data(array_filter($params));
            $attachment->save();
            \think\Hook::listen("upload_after", $attachment);
            $result = array('code' => 0, 'url' => $domain . $uploadDir . $splInfo->getSaveName(), 'data' => array('uid' => 'd', 'url' =>  $domain . $uploadDir . $splInfo->getSaveName()));
            return json($result);
        } else {
            // 上传失败获取错误信息
            $this->error($file->getError());
        }
    }
}
