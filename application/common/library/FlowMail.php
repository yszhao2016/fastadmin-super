<?php

namespace app\common\library;

use app\common\library\Email;

/**
 * 流程邮件发送
 */
class FlowMail
{
    public static function send()
    {
        $mail = new \app\admin\model\flow\FlowMail();
        $list = $mail->where(['issend' => '0'])
            ->order('id', 'DESC')
            ->select();
        $email = new Email;
        foreach ($list as $item) {
            $result = $email
                ->to($item['address'])
                ->subject($item['subject'])
                ->message($item['content'])
                ->send();
            if ($result) {
                $mail->where(['id' => $item['id']])->update(['issend' => '1', 'message' => '发送成功', 'senddate' => date('Y-m-d H:i:s')]);
            } else {
                $mail->where(['id' => $item['id']])->update(['message' => $email->getError(), 'senddate' => date('Y-m-d H:i:s')]);
            }
        }
        return true;
    }
}
