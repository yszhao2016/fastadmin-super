<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

//return [
//    'connector' => 'Sync'
//];

return [
//    'connector' => 'Database',   // 数据库驱动
//    'expire' => 60,           // 任务的过期时间，默认为60秒; 若要禁用，则设置为 null
//    'default' => 'default',    // 默认的队列名称
//    'table' => 'jobs',       // 存储消息的表名，不带前缀
//    'retry_times' => '1'

    'connector'=>'redis',
    'expire'     => 0,
    'default'    => 'default',
    'host'       => '127.0.0.1',
    'port'       => 6379,
    'password'   => '',
//    'select'     => 0,
//    'timeout'    => 0,
//    'persistent' => false

];