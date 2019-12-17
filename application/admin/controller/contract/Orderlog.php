<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description: 合约成交记录
 */

namespace app\admin\controller\contract;

use app\admin\controller\Base;
use app\common\model\ContractOrderLogModel;
use app\common\model\ContractTypeModel;

class Orderlog extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'c.floginName|c.fId|a.fid|b.c_order_number', 'like'],
                ['c_state', 'a.c_state', '='],
                ['c_id', 'a.c_id', '='],
                ['add_time', 'a.add_time', 'between'],
            ]);

            $type = get('c_type');
            if ($type == 1) {
                $where[] = ['a.c_type', '=', 0];
                $where[] = ['a.trade_type', '=', 2];
            } elseif ($type == 2) {
                $where[] = ['a.c_type', '=', 1];
                $where[] = ['a.trade_type', '=', 2];
            } elseif ($type == 3) {
                $where[] = ['a.c_type', '=', 0];
                $where[] = ['a.trade_type', '=', 3];
            } elseif ($type == 4) {
                $where[] = ['a.c_type', '=', 1];
                $where[] = ['a.trade_type', '=', 3];
            }

            $where[] = ['c.user_node', '=', session('user_invita_code')];

            $model = ContractOrderLogModel::where($where);
            $data  = $model->alias('a')
                ->field('a.*,b.e_state,b.e_number,b.e_price,c.floginName,c.user_node,c.user_type,d.c_name_cn,d.c_nmae_en,d.contract_cate')
                ->join('entrust_order b', 'a.order_id = b.id')
                ->join('fuser c', 'a.f_uid = c.fId')
                ->join('contract_type d', 'a.c_id = d.c_id')
                ->order('a.add_time desc')
                ->limit($limit)
                ->select();


            return table_json($model->count(), $data);
        }
        return view('', ['contracts' => ContractTypeModel::getContracts()]);
    }
}