<?php

namespace app\admin\model\fastflow\bill;


use app\admin\model\Admin;
use think\Config;
use think\Model;
use fastflow\api;

class Common extends Model
{
    // 追加属性
    protected $append = [
        'createusername',
        'bill',
        'flow_auth',
        'runthread_info',
        'flow_progress',
    ];

    public function getName()
    {
        return $this->name;
    }

    public function getCreateUserNameAttr($value, $data)
    {
        $createUserId = $data['admin_id'];
        $user = Admin::find($createUserId);
        if ($user) {
            return $user['nickname'];
        }
        return '';
    }

    public function getBillAttr($value, $data)
    {
        return Config::get('database')['prefix'] . $this->name;
    }

    public function getFlowAuthAttr($value, $data)
    {
        $bill = Config::get('database')['prefix'] . $this->name;
        return (new api())->getBillAuthInfo($data['admin_id'], $bill, $data['id']);
    }

    public function getRunthreadInfoAttr($value, $data)
    {
        $bill = Config::get('database')['prefix'] . $this->name;
        return (new api())->getRunThreadInfo($bill, $data['id']);
    }

    public function getFlowProgressAttr($value, $data)
    {
        $bill = Config::get('database')['prefix'] . $this->name;
        return (new api())->getFlowProgress($bill, $data['id']);
    }
}