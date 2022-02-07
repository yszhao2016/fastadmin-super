<?php

namespace app\common\library;

use app\admin\model\flow\Instance;
use app\admin\model\flow\Scheme;
use app\admin\model\flow\Task;
use think\Exception;
use think\Request;
use think\Db;
use think\Config;

class FlowEngine
{
    /**
     * 当前请求实例
     * @var Request
     */
    protected $request = null;
    protected $admin = null;
    protected $scheme = null;
    protected $instance = null;
    protected $task = null;
    protected $currentNode = null;
    protected $nextNode = null;
    protected $stepid = null;
    protected $prefix = "";

    public function __construct($name, $userid = '')
    {
        if ($userid) {
            $user = new \app\admin\model\Admin();
            $this->admin = $user->get($userid);
        } else {
            $this->admin = \think\Session::get('admin');
        }
        if (!$this->admin) {
            throw new Exception('用户不存在');
        }
        $this->task = new \app\admin\model\flow\Task();
        $this->instance = new \app\admin\model\flow\Instance();
        $scheme = new \app\admin\model\flow\Scheme();
        $this->scheme = $scheme->where(['flowcode' => $name])->find();
        if (!$this->scheme) {
            throw new Exception('流程不存在');
        }
        $this->prefix = Config::get('database.prefix');
    }

    /**保存流程
     * @param array $data 业务表数据
     * @param string $instance 实例id
     * @return array 返回实例id,业务表id
     * @throws Exception
     */
    public function save($data = [], $instance = '')
    {
        $ids = [];
        $instanceid = \fast\Random::uuid();
        $bizobjectid = \fast\Random::uuid();
        $instancecode = '';
        if (array_key_exists('instancecode', $data)) {
            $instancecode = $data['instancecode'];
            unset($data['instancecode']);
        } else {
            $instancecode = time();
        }
        $ids['instanceid'] = $instanceid;
        $ids['bizobjectid'] = $bizobjectid;
        try {
            $instanceid = \fast\Random::uuid();
            $bizobjectid = \fast\Random::uuid();
            $flowTmp = $this->scheme;
            if ($instance == '') {
                //新建流程实例
                Db::name('flow_instance')->insert(array(
                    'id' => $instanceid,
                    'originator' => $this->admin['id'],
                    'scheme' => $flowTmp['id'],
                    'createtime' => date("Y-m-d h:i:s"),
                    'completedtime' => '1990-01-01 00:00:00',
                    'instancecode' => $instancecode,
                    'bizobjectid' => $bizobjectid,
                    'instancestatus' => 0
                ));
                $content = json_decode($flowTmp->flowcontent, true);
                //所有连线信息
                $lines = $content['lines'];
                //所有节点信息
                $nodes = $content['nodes'];
                $rtnNext = null;
                $nextNodeIndex = null;
                //如果是开始节点需要保存开始数据和下一个节点的代办数据 根据条件筛选
                $rtn = array_search('start', array_column($nodes, 'type'));
                $this->currentNode = $nodes[$rtn];
                $this->stepid = $this->currentNode['id'];
                Db::name('flow_task')->insert(array(
                    'id' => \fast\Random::uuid(),
                    'flowid' => $flowTmp['id'],
                    'stepname' => $this->currentNode['name'],
                    'stepid' => $this->currentNode['id'],
                    'receiveid' => $this->admin['id'],
                    'instanceid' => $instanceid,
                    'senderid' => $this->admin['id'],
                    'completedtime' => date("Y-m-d h:i:s"),
                    'status' => 0,
                    'createtime' => date("Y-m-d H:i:s"),
                    'comment' => '提交'
                ));
                $params = $data;
                $params['id'] = $bizobjectid;
                if ($data) {
                    Db::table($flowTmp['bizscheme'])->insert($params);
                }
            } else {
                if ($data) {
                    Db::table($flowTmp['bizscheme'])->update($data);
                }
            }
        } catch (\think\exception\PDOException $e) {
            throw new Exception($e->getMessage());
        } catch (\think\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return $ids;
    }

    /**直接提交流程
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function start($data)
    {
        $instanceid = \fast\Random::uuid();
        $bizobjectid = \fast\Random::uuid();
        $ids['instanceid'] = $instanceid;
        $ids['bizobjectid'] = $bizobjectid;
        $flowTmp = $this->scheme;
        $originator = $this->admin['id'];
        $instancecode = '';
        if (!$flowTmp->isenable) {
            throw new Exception('请先发布流程');
        }
        if (!$flowTmp->flowcontent) {
            throw new Exception('请先设置流程图');
        }

        if (array_key_exists('instancecode', $data)) {
            $instancecode = $data['instancecode'];
            unset($data['instancecode']);
        } else {
            $instancecode = time();
        }

        //新建流程实例
        $this->instance->isUpdate(false)->data(array(
            'id' => $instanceid,
            'originator' => $originator,
            'scheme' => $flowTmp['id'],
            'createtime' => date("Y-m-d h:i:s"),
            'instancecode' => $instancecode,
            'bizobjectid' => $bizobjectid,
            'instancestatus' => 1,
            'completedtime' => '1990-01-01 00:00:00',
        ), true)->save();
        $content = json_decode($flowTmp->flowcontent, true);
        //所有连线信息
        $lines = $content['lines'];
        //所有节点信息
        $nodes = $content['nodes'];
        $rtnNext = null;
        $nextNodeIndex = null;
        //如果是开始节点需要保存开始数据和下一个节点的代办数据
        $rtn = array_search('start', array_column($nodes, 'type'));
        $this->currentNode = $nodes[$rtn];
        $this->stepid = $this->currentNode['id'];
        $this->task->isUpdate(false)->data(array(
            'id' => \fast\Random::uuid(),
            'flowid' => $flowTmp['id'],
            'stepname' => $this->currentNode['name'],
            'stepid' => $this->currentNode['id'],
            'receiveid' => $this->admin['id'],
            'instanceid' => $instanceid,
            'senderid' => $this->admin['id'],
            'status' => '2',
            'createtime' => date("Y-m-d H:i:s"),
            'completedtime' => date("Y-m-d H:i:s"),
            'comment' => '提交'
        ), true)->save();
        //find lines where from is stepid
        $nextNodeArray = array_filter($lines, function ($t) {
            return $t['from'] == $this->stepid;
        });
        $all = count((array)$nextNodeArray);
        $normal = 0;
        foreach ($nextNodeArray as $line) {
            //judging condition
            if (array_key_exists('setInfo', $line['data'])) {
                try {
                    $express = $line['data']['setInfo']['express'] == '' ? '1=1' : $line['data']['setInfo']['express'];
                    $sql = "select * from (select * from " . $flowTmp->bizscheme . " where id = '" . $bizobjectid . "') a where  " . $express;
                    $table = Db::query($sql);
                    if (count((array)$table) < 1) {
                        $normal++;
                        continue;
                    }
                } catch (\think\Exception $e) {
                    continue;
                    Db::name('admin_log')->insert([
                        'admin_id' => $this->admin['id'], 'url' => '', 'title' => '流程条件异常', 'content' => $e->getMessage(), 'createtime' => time()
                    ]);
                    throw new Exception('流程条件异常,请联系管理员');
                }
            }
            // can not find next node
            if ($normal == $all) {
                Db::name('admin_log')->insert([
                    'admin_id' => $this->admin['id'], 'url' => '', 'title' => '流程异常', 'content' => '流程无法找到下一个节点'
                ]);
                throw new Exception('流程无法找到下一个节点');
            }
            //find node where id is line's id 
            $this->nextNode = array_filter($nodes, function ($t) use ($line) {
                return $t['id'] == $line['to'];
            });
            $this->nextNode = array_values($this->nextNode)[0];
            $stepName = $this->nextNode['name'];
            if ($stepName == "结束") {
                if (count((array)$nextNodeArray) == 1 && $this->nextNode['name'] == '结束') {
                    //更改当前实例为结束
                    $this->instance->where('id', $instanceid)->update(['instancestatus' => 2, 'completedtime' => date("Y-m-d H:i:s")]);
                    //插入结束节点task
                    Db::name("flow_task")->insert([
                        'id' => \fast\Random::uuid(),
                        'flowid' => $flowTmp['id'],
                        'stepname' => $this->nextNode['name'],
                        'stepid' => $this->nextNode['id'],
                        'receiveid' => '',
                        'instanceid' => $instanceid,
                        'senderid' => $this->admin['id'],
                        'status' => '2',
                        'createtime' => date("Y-m-d H:i:s"),
                        'completedtime' => date("Y-m-d H:i:s"),
                        'comment' => '结束'
                    ]);
                }
                break;
            }
            $nodeType = $this->nextNode['setInfo']['nodeDesignate'];
            $dataset = [];
            $userList = [];
            $userId = '';
            if ($nodeType == 'user') {
                if (is_array($this->nextNode['setInfo']['NodeDesignateData']['users'])) {
                    $userList = $this->nextNode['setInfo']['NodeDesignateData']['users'];
                } else {
                    $userList = strlen($this->nextNode['setInfo']['NodeDesignateData']['users']) > 0 ? explode(',', $this->nextNode['setInfo']['NodeDesignateData']['users']) : [];
                }
                foreach ($userList as $user) {
                    $userId = $this->delegate($user);
                    $dataset[] = [
                        'id' => \fast\Random::uuid(),
                        'flowid' => $flowTmp['id'],
                        'stepname' => $this->nextNode['name'],
                        'stepid' => $this->nextNode['id'],
                        'receiveid' => $user,
                        'delegateid' => $userId,
                        'instanceid' => $instanceid,
                        'senderid' => $this->admin['id'],
                        'status' => '0',
                        'createtime' => date("Y-m-d H:i:s")
                    ];
                }
            }
            if ($nodeType == 'dept') {
                $userList = $this->getdeptmanager($originator);
                foreach ($userList as $user => $v) {
                    $userId = $this->delegate($v['manager']);
                    $dataset[] = [
                        'id' => \fast\Random::uuid(),
                        'flowid' => $flowTmp['id'],
                        'stepname' => $this->nextNode['name'],
                        'stepid' => $this->nextNode['id'],
                        'receiveid' => $v['manager'],
                        'delegateid' => $userId,
                        'instanceid' => $instanceid,
                        'senderid' => $this->admin['id'],
                        'status' => '0',
                        'createtime' => date("Y-m-d H:i:s")
                    ];
                }
            }
            if ($nodeType == 'role') {
                $role = '';
                if (is_array($this->nextNode['setInfo']['NodeDesignateData']['role'])) {
                    $role = $this->nextNode['setInfo']['NodeDesignateData']['role'];
                    if (!$role) {
                        throw new Exception('找不到角色');
                    } else {
                        $role = $role[0];
                    }
                } else {
                    $role = $this->nextNode['setInfo']['NodeDesignateData']['role'];
                }
                $userList = $this->getuserbyrole($role);
                foreach ($userList as $user => $v) {
                    $userId = $this->delegate($v['id']);
                    $isHaveRight = $this->rightToRole($originator, $v['id']);
                    if ($isHaveRight) {
                        $dataset[] = [
                            'id' => \fast\Random::uuid(),
                            'flowid' => $flowTmp['id'],
                            'stepname' => $this->nextNode['name'],
                            'stepid' => $this->nextNode['id'],
                            'receiveid' => $v['id'],
                            'delegateid' => $userId,
                            'instanceid' => $instanceid,
                            'senderid' => $this->admin['id'],
                            'status' => '0',
                            'createtime' => date("Y-m-d H:i:s")
                        ];
                    }
                }
            }
            if ($nodeType == 'customize') {
                $customize = $this->nextNode['setInfo']['NodeDesignateData']['customize'];
                if (trim($customize) == 'originator') {
                    $userId = $this->delegate($originator);
                    $userList[] = ['id' => $userId];
                    $dataset[] = [
                        'id' => \fast\Random::uuid(),
                        'flowid' => $flowTmp['id'],
                        'stepname' => $this->nextNode['name'],
                        'stepid' => $this->nextNode['id'],
                        'receiveid' => $originator,
                        'delegateid' => $userId,
                        'instanceid' => $instanceid,
                        'senderid' => $this->admin['id'],
                        'status' => '0',
                        'createtime' => date("Y-m-d H:i:s")
                    ];
                }
            }
            if (count((array)$userList) == 0) {
                $dataset[] = [
                    'id' => \fast\Random::uuid(),
                    'flowid' => $flowTmp['id'],
                    'stepname' => $this->nextNode['name'],
                    'stepid' => $this->nextNode['id'],
                    'receiveid' => '',
                    'instanceid' => $instanceid,
                    'senderid' => $this->admin['id'],
                    'status' => '0',
                    'createtime' => date("Y-m-d H:i:s")
                ];
                Db::name('flow_mail')->insert([
                    'subject' => '无审批人',
                    'content' => '实例id ' . $instanceid . ' 无审批人请及时处理',
                    'createdtime' => date('Y-m-d H:i:s'),
                    'issend' => '0',
                    'senddate' => '1990-01-01',
                    'address' => $this->getemail($flowTmp->flowcanuser), 'message' => ''
                ]);
            }

            $this->task->isUpdate(false)->saveAll($dataset, false);
        }
        $params = $data;
        $params['id'] = $bizobjectid;
        if ($data) {
            Db::table($flowTmp['bizscheme'])->insert($params);
        }
        return $ids;
    }

    /** 审批流程
     * @param $taskid
     * @param string $data
     * @param string $comment
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function next($taskid, $data = [], $stepid, $comment = '')
    {
        $res = true;
        $confluence = null;
        $task = $this->task->where(['id' => $taskid])->where('status', 0)->find();
        if (!$task)
            throw new Exception('找不到当前任务');
        $instance = $this->instance->get($task['instanceid']);
        $originator = $instance->originator;
        $bizobjectid = $instance->bizobjectid;
        $originator = $instance['originator'];
        $flowTmp = $this->scheme;
        $this->stepid = $task['stepid'];
        //更改当前任务为完成
        $comment = $comment == '' ? '[同意]' : $comment;
        $instanceid = $task['instanceid'];
        $this->task->where('id', $taskid)->update(['status' => 2, 'completedtime' => date("Y-m-d H:i:s"), 'comment' => $comment]);
        //判断当前节点还是否有其他任务
        $unfinishList = $this->task->where(['instanceid' => $instanceid, 'stepid' => $this->stepid])->where('status', 'in', [0, 1])->find();
        //找到当前节点
        //寻找下一个待办任务
        $content = json_decode($flowTmp->flowcontent, true);
        //所有连线信息
        $lines = $content['lines'];
        //所有节点信息
        $nodes = $content['nodes'];
        $rtnNext = null;
        $rtn = array_search($this->stepid, array_column($nodes, 'id'));
        $this->currentNode = $nodes[$rtn];
        $confluence = isset($this->currentNode['setInfo']['confluence']) ? $this->currentNode['setInfo']['confluence'] : 'all';
        //如果是第一个节点  先保存数据
        if ($task['stepname'] == '开始') {
            $this->instance->where('id', $task['instanceid'])->update(['instancestatus' => 1]);
            if (!$data['id']) {

                Db::table($flowTmp['bizscheme'])->insert($data);
            } else {
                Db::table($flowTmp['bizscheme'])->where(['id' => $data['id']])->update($data);
            }
        }
        $nextNodeIndex = null;
        $nextNodeArray = array_filter($lines, function ($t) {
            return $t['from'] == $this->stepid;
        });
        $all = count((array)$nextNodeArray);
        $checkNode = count((array)$nextNodeArray);

        if($unfinishList && $confluence != 'any'){
           return;
        }

        if (!$unfinishList || ($unfinishList && $confluence == 'any') || ($all > 0)) {
            if ($unfinishList) {
                $this->task->where(['instanceid' => $instanceid, 'stepid' => $this->stepid])->where('status', 'in', [0, 1])->update(['status' => 2]);
            }

            $normal = 0;
            foreach ($nextNodeArray as $line) {

                $this->nextNode = array_filter($nodes, function ($t) use ($line) {
                    return $t['id'] == $line['to'];
                });
                $this->nextNode = array_values($this->nextNode)[0]; //0表示获取他的value
                $nodeType = null;
                $dataset = [];
                $userList = null;
                $userId = '';
                //judging condition
                if (array_key_exists('setInfo', $line['data'])) {
                    try {
                        $express = $line['data']['setInfo']['express'] == '' ? '1=1' : $line['data']['setInfo']['express'];
                        $sql = "select * from (select * from " . $flowTmp->bizscheme . " where id = '" . $bizobjectid . "') a where " . $express;
                        $table = Db::query($sql);
                        if (count((array)$table) < 1) {
                            $normal++;
                            $checkNode--;
                            continue;
                        } else {
                            if ($this->nextNode['name'] == '结束') {
                                //更改当前实例为结束
                                $this->instance->where('id', $task['instanceid'])->update(['instancestatus' => 2, 'completedtime' => date("Y-m-d H:i:s")]);
                                //插入结束节点task
                                Db::name("flow_task")->insert([
                                    'id' => \fast\Random::uuid(),
                                    'flowid' => $flowTmp['id'],
                                    'stepname' => $this->nextNode['name'],
                                    'stepid' => $this->nextNode['id'],
                                    'receiveid' => '',
                                    'instanceid' => $instanceid,
                                    'senderid' => $this->admin['id'],
                                    'status' => '2',
                                    'createtime' => date("Y-m-d H:i:s"),
                                    'completedtime' => date("Y-m-d H:i:s"),
                                    'comment' => '结束'
                                ]);
                                return;
                            }
                        }
                    } catch (\think\Exception $e) {
                        Db::name('admin_log')->insert([
                            'admin_id' => $this->admin['id'], 'url' => '', 'title' => '流程条件异常', 'content' => $e->getMessage(), 'createtime' => time()
                        ]);
                        throw new Exception('流程条件异常,请联系管理员');
                    }
                }
                // can not find next node
                if ($normal == $all) {
                    Db::name('admin_log')->insert([
                        'admin_id' => $this->admin['id'], 'url' => '', 'title' => '流程异常', 'content' => '流程无法找到下一个节点'
                    ]);
                    throw new Exception('流程无法找到下一个节点');
                }


                if (($checkNode == 1 && $this->nextNode['name'] == '结束') || ($all == 1 && $this->nextNode['name'] == '结束')) {
                    //更改当前实例为结束
                    $this->instance->where('id', $task['instanceid'])->update(['instancestatus' => 2, 'completedtime' => date("Y-m-d H:i:s")]);
                    //插入结束节点task
                    Db::name("flow_task")->insert([
                        'id' => \fast\Random::uuid(),
                        'flowid' => $flowTmp['id'],
                        'stepname' => $this->nextNode['name'],
                        'stepid' => $this->nextNode['id'],
                        'receiveid' => '',
                        'instanceid' => $instanceid,
                        'senderid' => $this->admin['id'],
                        'status' => '2',
                        'createtime' => date("Y-m-d H:i:s"),
                        'completedtime' => date("Y-m-d H:i:s"),
                        'comment' => '结束'
                    ]);
                }
                if ($this->nextNode['name'] != '结束') {
                    $nodeType = $this->nextNode['setInfo']['nodeDesignate'];
                    if ($nodeType == 'user') {
                        if (is_array($this->nextNode['setInfo']['NodeDesignateData']['users'])) {
                            $userList = $this->nextNode['setInfo']['NodeDesignateData']['users'];
                        } else {
                            $userList = explode(',', $this->nextNode['setInfo']['NodeDesignateData']['users']);
                        }
                        foreach ($userList as $user) {
                            $userId = $this->delegate($user);
                            $dataset[] = [
                                'id' => \fast\Random::uuid(),
                                'flowid' => $flowTmp['id'],
                                'stepname' => $this->nextNode['name'],
                                'stepid' => $this->nextNode['id'],
                                'receiveid' => $stepid ? $stepid : $user,
                                'delegateid' => $userId,
                                'instanceid' => $instanceid,
                                'senderid' => $this->admin['id'],
                                'status' => '0',
                                'createtime' => date("Y-m-d H:i:s")
                            ];
                        }
                    }
                    if ($nodeType == 'dept') {
                        $userList = $this->getdeptmanager($originator);
                        foreach ($userList as $user => $v) {
                            $userId = $this->delegate($v['manager']);
                            $dataset[] = [
                                'id' => \fast\Random::uuid(),
                                'flowid' => $flowTmp['id'],
                                'stepname' => $this->nextNode['name'],
                                'stepid' => $this->nextNode['id'],
                                'receiveid' => $stepid ? $stepid : $v['manager'],
                                'delegateid' => $userId,
                                'instanceid' => $instanceid,
                                'senderid' => $this->admin['id'],
                                'status' => '0',
                                'createtime' => date("Y-m-d H:i:s")
                            ];
                        }
                    }
                    if ($nodeType == 'role') {
                        $role = '';
                        if (is_array($this->nextNode['setInfo']['NodeDesignateData']['role'])) {
                            $role = $this->nextNode['setInfo']['NodeDesignateData']['role'];
                            if (!$role) {
                                throw new Exception('找不到角色');
                            } else {
                                $role = $role[0];
                            }
                        } else {
                            $role = $this->nextNode['setInfo']['NodeDesignateData']['role'];
                        }
                        $userList = $this->getuserbyrole($role);
                        foreach ($userList as $user => $v) {
                            $userId = $this->delegate($v['id']);
                            $isHaveRight = $this->rightToRole($originator, $v['id']);
                            if ($isHaveRight) {
                                $dataset[] = [
                                    'id' => \fast\Random::uuid(),
                                    'flowid' => $flowTmp['id'],
                                    'stepname' => $this->nextNode['name'],
                                    'stepid' => $this->nextNode['id'],
                                    'receiveid' => $stepid ? $stepid : $v['id'],
                                    'delegateid' => $userId,
                                    'instanceid' => $instanceid,
                                    'senderid' => $this->admin['id'],
                                    'status' => '0',
                                    'createtime' => date("Y-m-d H:i:s")
                                ];
                            }
                        }
                    }
                    if ($nodeType == 'customize') {
                        $customize = $this->nextNode['setInfo']['NodeDesignateData']['customize'];
                        if (trim($customize) == 'originator') {
                            $userId = $this->delegate($originator);
                            $userList = ['id' => $userId];
                            $dataset[] = [
                                'id' => \fast\Random::uuid(),
                                'flowid' => $flowTmp['id'],
                                'stepname' => $this->nextNode['name'],
                                'stepid' => $this->nextNode['id'],
                                'receiveid' => $originator,
                                'delegateid' => $userId,
                                'instanceid' => $instanceid,
                                'senderid' => $this->admin['id'],
                                'status' => '0',
                                'createtime' => date("Y-m-d H:i:s")
                            ];
                        }
                    }
                    if (count((array)$userList) == 0) {
                        $dataset[] = [
                            'id' => \fast\Random::uuid(),
                            'flowid' => $flowTmp['id'],
                            'stepname' => $this->nextNode['name'],
                            'stepid' => $this->nextNode['id'],
                            'receiveid' => '',
                            'instanceid' => $instanceid,
                            'senderid' => $this->admin['id'],
                            'status' => '0',
                            'createtime' => date("Y-m-d H:i:s")
                        ];
                        Db::name('flow_mail')->insert([
                            'subject' => '无审批人',
                            'content' => '实例id' . $instanceid . ' 无审批人请及时处理',
                            'createdtime' => date('Y-m-d H:i:s'),
                            'issend' => '0',
                            'senddate' => '0000-00-00 00:00:00',
                            'address' => $this->getemail($flowTmp->flowcanuser),
                            'message' => ''
                        ]);
                    }
                    $this->task->isUpdate(false)->saveAll($dataset, false);
                }
            }
        }
        return $res;
    }

    /**拒绝流程
     * @param $taskid
     * @param string $comment
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function refuse($taskid, $comment = '')
    {
        $res = true;
        $task = $this->task->where(['id' => $taskid, 'status' => 0])->find();
        if (!$task)
            throw new Exception('找不到当前任务,或已处理，请联系管理员');
        //更改当前流程为拒绝状态
        $comment = $comment == '' ? '[拒绝]' : $comment;
        Db::name('flow_task')->where('id', $taskid)
            ->update(['status' => 2, 'completedtime' => date("Y-m-d H:i:s"), 'comment' => $comment]);
        //取消其他流程
        Db::name('flow_task')->where(['instanceid' => $task['instanceid'], 'status' => 0])
            ->update(['status' => 3, 'completedtime' => date("Y-m-d H:i:s")]);
        //更改流程实例为草稿状态
        Db::name('flow_instance')->where(['id' => $task['instanceid']])
            ->update(['instancestatus' => 0]);
        //寻找下一个待办任务
        $startNode = $this->task->where(['instanceid' => $task['instanceid'], 'stepname' => '开始'])->find();
        //$startNode->status = 0;
        $this->task->insert([
            'id' => \fast\Random::uuid(),
            'flowid' => $startNode['flowid'],
            'stepname' => $startNode['stepname'],
            'stepid' => $startNode['stepid'],
            'receiveid' => $startNode['receiveid'],
            'instanceid' => $startNode['instanceid'],
            'senderid' => $startNode['senderid'],
            'status' => '0',
            'createtime' => date("Y-m-d H:i:s")
        ]);
        return $res;
    }

    /**取消流程
     * @param $taskid
     * @param string $comment
     * @return bool
     * @throws \think\exception\DbException
     */
    public function cancel($taskid, $comment = '')
    {
        $res = true;
        $task = $this->task->get($taskid);
        $comment = $comment == '' ? '[取消]' : $comment;
        //更改当前流程为取消状态
        $this->task->where(['instanceid' => $task['instanceid']])->where('status', 'in', [0, 1])->update(['status' => 3, 'completedtime' => date("Y-m-d H:i:s"), 'comment' => $comment]);
        $this->instance->where(['id' => $task['instanceid']])->update(['instancestatus' => 3]);
        return $res;
    }

    /**根据角色获取用户
     * @param $role
     * @return mixed
     */
    public function getuserbyrole($role)
    {
        $sql = "SELECT a.id FROM " . $this->prefix . "admin a 
                LEFT JOIN " . $this->prefix . "auth_group_access b ON a.id = b.uid
                LEFT JOIN " . $this->prefix . "auth_group c ON b.group_id=c.id
                WHERE c.`id`='" . $role . "'";
        $user = Db::query($sql);
        return $user;
    }

    /**
     * 获取发起人当前部门负责人
     */
    public function getdeptmanager($originator)
    {
        $sql = "select manager from " . $this->prefix . "admin a 
               left join " . $this->prefix . "flow_department b on a.department_id=b.id where a.id=" . $originator;
        $user = Db::query($sql);
        return $user;
    }
    /**
     * 获取用户邮件地址
     */
    public function getemail($user)
    {
        $result = Db::name('admin')->where(['id' => $user])->find();
        return $result['email'];
    }
    /**
     * 获取用户代理
     */
    public function delegate($id)
    {
        $return = '';
        if (!$id) {
            return '';
        }
        $result = Db::name('flow_delegate')
            ->where(['admin_id' => $id])
            ->where('begin_date', '<=', date("Y-m-d H:i:s"))
            ->where('end_date', '>=', date("Y-m-d H:i:s"))
            ->find();
        if (count((array)$result) > 0) {
            $return = $result['delegate_id'];
        }
        return $return;
    }
    public function rightToRole($originator, $userid)
    {
        $sql = "select b.code from " . $this->prefix . "admin a 
               left join " . $this->prefix . "flow_department b on a.department_id=b.id where a.id=" . $originator;
        $code = Db::query($sql)[0]['code'];
        if (!$code) {
            return true;
        }
        $sql = "select b.code from " . $this->prefix . "admin a 
        left join " . $this->prefix . "flow_department b on a.department_id=b.id where a.id=" . $userid;
        $usercode = Db::query($sql)[0]['code'];
        if (!$usercode) {
            return false;
        }
        if (strpos($code, $usercode) !== false) {
            return true;
        }
        $rightSql = "SELECT GROUP_CONCAT(b.code ) code FROM (select value from " . $this->prefix . "flow_right where type='dept' and `key` = " . $userid . ") a
        LEFT JOIN " . $this->prefix . "flow_department b ON FIND_IN_SET(b.id,a.value) ";

        $list = Db::query($rightSql);
        foreach ($list as $i => $l) {
            if (!empty($l['code']) && strpos($code, $l['code']) !== false) {
                return true;
            }
        }
        return false;
    }


    public function trans($taskid, $userId, $comment = '')
    {
        $res = true;
        $task = $this->task->where(['id' => $taskid, 'status' => 0])->find();
        if (!$task)
            throw new Exception('找不到当前任务,或已处理，请联系管理员');
        //处理当前任务
        Db::name('flow_task')->where('id', $taskid)
            ->update(['status' => 2, 'completedtime' => date("Y-m-d H:i:s"), 'comment' => '已转发']);

        $this->task->insert([
            'id' => \fast\Random::uuid(),
            'flowid' => $task['flowid'],
            'stepname' => $task['stepname'],
            'stepid' => $task['stepid'],
            'receiveid' => $userId,
            'instanceid' => $task['instanceid'],
            'senderid' => $task['senderid'],
            'status' => '0',
            'createtime' => date("Y-m-d H:i:s")
        ]);
        return $res;
    }
}
