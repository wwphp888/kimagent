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
use app\common\model\ContractPorderlogModel;
use app\common\model\ContractSettleModel;
use app\common\model\ContractTypeModel;
use app\common\service\ContractService;

class Settle extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'b.floginName||b.fId|b.user_node', 'like'],
                ['p_type', 'a.p_type', '='],
                ['c_id', 'a.c_id', '='],
                ['create_time', 'a.create_time', 'between']
            ]);

            $where[] = ['b.user_node', '=', session('user_invita_code')];
            $model = ContractSettleModel::where($where);
            $data  = $model->alias('a')
                ->field('a.*,b.floginName,b.user_node,b.user_type,c.c_name_cn,c.c_nmae_en,c.contract_cate')
                ->join('fuser b', 'a.f_uid = b.fId')
                ->join('contract_type c', 'a.c_id = c.c_id')
                ->order('a.create_time desc')
                ->limit($limit)
                ->select();

            return table_json($model->count(), $data);
        }
        return view('', ['contracts' => ContractTypeModel::getContracts()]);
    }
}