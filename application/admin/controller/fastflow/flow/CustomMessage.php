<?php


namespace app\admin\controller\fastflow\flow;


use app\admin\model\Admin;
use app\admin\model\AuthGroupAccess;
use app\common\controller\Backend;
use fastflow\api;
use Tx\Mailer;

class CustomMessage extends Backend
{
    //flowName          流程名称
    //flowDescription   流程描述
    //scope             域 1=人员,2=分组
    //scopeName         域名称
    //workerIds         审批人id
    //workerNames       审批人名称
    //checkmode         审批模式
    //stepName          步骤名称
    //preStepName       上一步骤名称
    //bill              单据表名
    //billId            单据id
    //billName          单据注释
    //agency            是否为代理
    //agentId           代理人id
    //agentName         代理人名称
    //principalIds      被代理人id
    //principalName     被代理人名称
    //receivers         接收人

    protected $noNeedRight = ['sendMessageAsync'];

    public function __construct()
    {

    }

    public function sendMessage($params)
    {
        $messageModel = new \app\admin\model\fastflow\flow\Message();
        $enabledWays = $messageModel->getEnabledWays();
        if (count($enabledWays) == 0) {
            return;
        }
        $fo = fsockopen('localhost', 80, $error, $errstr, 3);
        if (!$fo) {
            return ['code' => 0, 'msg' => '消息发送失败'];
        }
        $head = "GET " . url('/fastflow/flow/custom_message/sendmessageasync', $params) . " HTTP/1.0\r\n";
        $head .= "Host: " . $_SERVER['HTTP_HOST'] . "\r\n";
        $head .= "\r\n";
        fputs($fo, $head);
        sleep(1);
        fclose($fo);
    }

    public function sendMessageAsync()
    {
        $builtParams = (new api())->buidMessageParams(input(''));

        $messageModel = new \app\admin\model\fastflow\flow\Message();
        $enabledWays = $messageModel->getEnabledWays();
        $options = $messageModel->getMessageOptions();

        foreach ($enabledWays as $way) {
            $body = self::parseTemplet($options[$way]['templet'], $builtParams);
            call_user_func([$this, 'send' . ucfirst($way)], $builtParams['scope'], $builtParams['receivers'], $options[$way]['config'], $body);
        }
        return;
    }

    public function parseTemplet($templet, $params)
    {
        $body = $templet;
        foreach ($params as $key => $value) {
            if ($key !== 'receivers') {
                $keywords = '{' . $key . '}';
                $body = str_replace($keywords, $value, $body);
            }
        }
        foreach ($this->setCustomVariate($params) as $key => $value) {
            $keywords = '{' . $key . '}';
            $body = str_replace($keywords, $value, $body);
        }
        return $body;
    }

    /*
     * 自定义模板变量
     */
    private function setCustomVariate($params)
    {
        $variates = [];
        $variates['time'] = date('Y-m-d H:i:s', time());

        return $variates;
    }

    /*
     * $scope:1=人员,2=分组
     * $receivers = [['id' => 1, 'name' => '张三'], ['id' => 2, 'name' => '李四']] 或
     * $receivers = [['id' => 1, 'name' => '销售部'], ['id' => 2, 'name' => '生产部']]
     * $config为用户自定义配置，使用键名获取值，如：$config['host']
     * $body为消息主体
     */

    /*
     * 自定义邮件发送
     */
    public function sendEmail($scope, $receivers, $config, $body)
    {
        if ($scope == 1) {//人员
            foreach ($receivers as $receiver) {
                $email = (new Admin())->find($receiver['id'])['email'];
                $ok = (new Mailer())
                    ->setServer($config['host'], $config['port'], $config['verify_type'])
                    ->setAuth($config['user'], $config['password'])
                    ->setFrom($config['user'], $config['user'])
                    ->addTo($email, $email)
                    ->setSubject('您有待审批流程需要处理')
                    ->setBody($body)
                    ->send();
            }
        }
        elseif ($scope == 2) {//分组
            foreach ($receivers as $receiver) {
                $groupAccess = (new AuthGroupAccess())->where('group_id', $receiver['id'])->select();
                foreach ($groupAccess as $row){
                    $email = (new Admin())->find($row['uid'])['email'];
                    $ok = (new Mailer())
                        ->setServer($config['host'], $config['port'], $config['verify_type'])
                        ->setAuth($config['user'], $config['password'])
                        ->setFrom($config['user'], $config['user'])
                        ->addTo($email, $email)
                        ->setSubject('您有待审批流程需要处理')
                        ->setBody($body)
                        ->send();
                }
            }
        }

    }

}