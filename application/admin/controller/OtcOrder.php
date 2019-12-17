<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */

namespace app\admin\controller;

use app\common\model\CoinsModel;
use app\common\model\OtcOrderModel;

class OtcOrder extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['id', 'a.id', '='],
                ['keyword', 'b.floginName|b.fRealName|b.fNickName|b.fTelephone|b.fEmail|b.fIdentityNo', 'like'],
                ['type', 'a.type', '='],
                ['status', 'a.status', '='],
                ['cid', 'a.cid', '='],
                ['create_time', 'a.create_time', 'between'],
            ]);

            $model = OtcOrderModel::where($where);
            $data  = $model->alias('a')
                ->field('a.*,concat(a.min_amount,"-", a.max_amount) as amount_rand,b.fRealName,c.fName')
                ->join('fuser b', 'a.uid = b.fId')
                ->join('fvirtualcointype c', 'a.cid = c.fId')
                ->order('a.update_time desc')
                ->limit($limit)
                ->select();

            $payway = ['银行卡', '支付宝', '微信'];
            foreach ($data as &$v) {
                $payArr = [];
                $pay = explode(',', trim($v['pay_way'], ','));
                foreach ($pay as $v1) {
                    $payArr[] = $payway[$v1];
                }
                $v['pay_way'] = join(', ', $payArr);
            }

            return table_json($model->count(), $data);
        }
        return view('', ['coins' => CoinsModel::getCoins()]);
    }
}