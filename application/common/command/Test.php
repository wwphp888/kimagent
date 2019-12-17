<?php
namespace app\common\command;

use app\common\service\CurlService;
use app\common\service\QueueService;
use app\common\service\SmsService;
use app\common\service\EmailService;
use app\common\service\PullTradeMarkService;
use app\common\service\WorkerService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Test extends Command
{
    protected function configure()
    {
        $this->setName('test')->setDescription('test');
    }

    protected function execute(Input $input, Output $output)
    {
        $amount = 20.00000000;
        //echo bcadd($amount, 0);


        $data = [
            'method' => 'sendtoaddress',
            'params' => ["0x4f61138e59168b94e19845ed87dc31fdee45db91", $amount, ''],
            'id' => 1,
        ];

        dump(CurlService::post('http://aaauser:aaapassword@47.74.240.135:10010', json_encode($data)));

        QueueService::push('test');
        //echo 111;exit;
    }
}
