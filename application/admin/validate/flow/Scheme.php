<?php

namespace app\admin\validate\flow;

use think\Validate;

class Scheme extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'flowcode|流程代码' => 'require|unique:flow_scheme',
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'flowcode.require' => '流程代码必须',
        'flowcode.unique'  => '流程代码已存在',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => ['flowcode'],
        'edit' => ['flowcode'],
    ];

}
