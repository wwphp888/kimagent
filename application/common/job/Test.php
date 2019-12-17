<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: ChainService.php
 * @Date: 2019/10/24
 * @Time: 11:32
 * @Description: 钱包中间件
 */
namespace app\common\job;

use app\common\model\ContractWalletModel;
use app\common\model\UserModel;

class Test
{
    public function handle()
    {
        file_put_contents(app()->getRootPath() . 'ww.txt', '23334');
    }
}