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
use app\common\model\ContractTypeModel;
use app\common\service\ContractService;
use app\common\service\CurlService;

class Porderlog extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'c.floginName|c.user_node|c.fId', 'like'],
                ['c_id', 'a.c_id', '='],
                ['p_type', 'a.p_type', '='],
                ['add_time', 'a.add_time', 'between']
            ]);

            $where[] = ['c.user_node', '=', session('user_invita_code')];
            $model = ContractPorderlogModel::where($where);
            $data  = $model->alias('a')
                ->field('a.*,c.floginName,c.user_node,c.user_type,d.c_name_cn,d.c_nmae_en,contract_cate')
                ->join('fuser c', 'a.f_uid = c.fId')
                ->join('contract_type d', 'a.c_id = d.c_id')
                ->join('contract_market e', 'd.c_id = e.c_id')
                ->order('a.add_time desc')
                ->limit($limit)
                ->select();

            foreach ($data as &$v) {
                $v['p_deposit'] = ContractService::getPositionDeposit($v['p_average'], $v['p_number']);
                $v['profit_loss_rate'] = ContractService::getProfitLossRate($v['p_deposit'], $v['f_profit_loss']);
            }

            return table_json($model->count(), $data);
        }
        return view('', ['contracts' => ContractTypeModel::getContracts()]);
    }

    /**
     * @desc 强制平仓
     * @return string
     */
    public function focusTradeOut()
    {
        $id = post('id');
        if (!$id) {
            return error('id error');
        }
        $info = ContractPorderlogModel::find($id);

        dump($info);exit;

        try {
            $params = [
                'contractId' => $info['c_id'],
                'openType' => 1,
                'cType' => $info['p_type'] == 0 ? 1 : 0,
                'tradePrice' => $info['p_type'],
                'uid' => $info['p_type'],
            ];

            $res = CurlService::post('/v1/tradeOut', http_build_query($params));

            return success('1');
        } catch (\Throwable $e) {
            return error($e->getMessage());
        }
    }
}