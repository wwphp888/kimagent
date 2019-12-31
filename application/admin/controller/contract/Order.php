<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */

namespace app\admin\controller\contract;

use app\admin\controller\Base;
use app\common\model\ContractOrderModel;
use app\common\model\ContractTypeModel;
use app\common\service\ContractService;

class Order extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'b.floginName|a.id|a.c_order_number', 'like'],
                ['c_state', 'a.c_state', '='],
                ['c_id', 'a.c_id', '='],
                ['delegate_type', 'a.delegate_type', '='],
                ['add_time', 'a.add_time', 'between'],
            ]);

            $type = get('c_type');
            if ($type == 1) {
                $where[] = ['a.c_type', '=', 0];
                $where[] = ['a.open_type', '=', 0];
            } elseif ($type == 2) {
                $where[] = ['a.c_type', '=', 1];
                $where[] = ['a.open_type', '=', 0];
            } elseif ($type == 3) {
                $where[] = ['a.c_type', '=', 0];
                $where[] = ['a.open_type', '=', 1];
            } elseif ($type == 4) {
                $where[] = ['a.c_type', '=', 1];
                $where[] = ['a.open_type', '=', 1];
            }

            $where[] = ['b.user_node', '=', session('user_invita_code')];

            $model = ContractOrderModel::where($where);
            $data  = $model->alias('a')
                ->field('a.*,b.floginName,b.user_node,b.user_type,c.c_name_cn,c.c_nmae_en,contract_cate,c.contract_mul,d.deposit_rate')
                ->join('fuser b', 'a.f_uid = b.fId')
                ->join('contract_type c', 'a.c_id = c.c_id')
                ->join('contract_market d', 'c.c_id = d.c_id')
                ->order('a.update_time desc')
                ->limit($limit)
                ->select();

            foreach ($data as &$v) {
                $v['freeze_deposit'] = ContractService::getFreezeDeposit($v['e_number'], $v['e_price'], $v['contract_mul'], $v['deposit_rate']);
            }

            return table_json($model->count(), $data);
        }
        return view('', ['contracts' => ContractTypeModel::getContracts()]);
    }
}