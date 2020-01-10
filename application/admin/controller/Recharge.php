<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */

namespace app\admin\controller;

use app\common\model\CapitalOperateModel;
use app\common\model\CoinsModel;

class Recharge extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'b.floginName|b.fNickName|b.fRealName', 'like'],
                ['address', 'a.recharge_virtual_address', 'like'],
                ['coin', 'a.fVi_fId2', '='],
                ['status', 'a.fStatus', '=']
            ]);

            $where[] = ['a.fType', '=', 1];
            $where[] = ['b.user_node', '=', session('user_invita_code')];
            $order = 'a.flastUpdateTime desc';
            $field = 'a.*,b.floginName,b.fNickName,b.fRealName,b.fEmail,b.fTelephone,c.fName as coin';

            $model = CapitalOperateModel::alias('a')
                ->field($field)
                ->join('fuser b', 'a.FUs_fId2 = b.fId')
                ->join('fvirtualcointype c', 'a.fVi_fId2 = c.fId')
                ->where($where)
                ->order($order);

            $data = $model->limit($limit)->select();
            return table_json($model->count(), $data);
        }

        return view('', ['coins' => CoinsModel::getCoins()]);
    }
}