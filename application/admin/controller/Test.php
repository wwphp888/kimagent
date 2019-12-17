<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */

namespace app\admin\controller;

use app\common\model\SysAuthModel;
use app\common\service\CurlService;
use app\common\service\QueueService;
use app\common\service\SmsService;
use app\common\model\AdminModel;
use app\common\service\UploadService;
use app\common\service\WorkerService;

class Test
{
    public function index()
    {
        $amount = 20.000000000000;

        $data = [
            'method' => 'sendtoaddress',
            'params' => ["0x4f61138e59168b94e19845ed87dc31fdee45db91", bcadd($amount, 0), ''],
            'id' => 1,
        ];

        dump(CurlService::post('http://aaauser:aaapassword@47.74.240.135:10010', json_encode($data)));
    }
}