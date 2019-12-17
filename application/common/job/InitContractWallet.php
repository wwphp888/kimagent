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

class InitContractWallet
{
    public function handle($contractId)
    {
        $date = date('Y-m-d H:i:s');
        $count = UserModel::count();
        $limit = 200;
        $page = ceil($count / $limit);

        for ($i = 0; $i < $page; $i++) {
            $data = UserModel::field('fId')->limit($i*$limit, $limit)->select();
            $insert = [];
            foreach ($data as $v) {
                $insert[] = [
                    'f_uid' => $v['fId'],
                    'contract_id' => $contractId,
                    'add_time' => $date,
                ];
            }
            ContractWalletModel::insertAll($insert);
            usleep(100000);
        }
    }
}