<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */

namespace app\admin\controller;

use app\common\model\ContractTypeModel;
use app\common\model\StatisticAssetsModel;
use app\common\model\StatisticContractModel;
use app\common\model\StatisticEntrushOrderModel;
use app\common\model\StatisticEntrushOrderlogModel;
use app\common\model\StatisticGainsModel;
use app\common\model\StatisticPorderlogModel;

class Statistic extends Base
{
    /**
     * @desc 合约监控
     */
    public function contract()
    {
        if (get('page')) {
            $data  = StatisticContractModel::alias('a')
                ->field('a.*,b.c_name_cn as contract_name,b.contract_cate')
                ->join('contract_type b', 'a.c_id = b.c_id')
                ->select();

            return table_json(0, $data);
        }

        $contracts = ContractTypeModel::getContracts();
        return view('', ['contracts' => $contracts]);
    }

    /**
     * @desc 委托订单统计
     */
    public function contractorder()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['c_id', 'a.c_id', '='],
                ['statistic_time', 'a.statistic_time', 'between']
            ]);

            $model = StatisticEntrushOrderModel::where($where);
            $data  = $model->alias('a')
                ->field('a.*,b.c_name_cn as contract_name,b.contract_cate')
                ->join('contract_type b', 'a.c_id = b.c_id')
                ->order('a.statistic_time desc')
                ->limit($limit)
                ->select();


            return table_json($model->count(), $data);
        }

        $contracts = ContractTypeModel::getContracts();
        return view('', ['contracts' => $contracts]);
    }

    /**
     * @desc 成交订单统计
     */
    public function contractorderlog()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['c_id', 'a.c_id', '='],
                ['statistic_time', 'a.statistic_time', 'between']
            ]);

            $model = StatisticEntrushOrderlogModel::where($where);
            $data  = $model->alias('a')
                ->field('a.*,b.c_name_cn as contract_name,b.contract_cate')
                ->join('contract_type b', 'a.c_id = b.c_id')
                ->order('a.statistic_time desc')
                ->limit($limit)
                ->select();


            return table_json($model->count(), $data);
        }

        $contracts = ContractTypeModel::getContracts();
        return view('', ['contracts' => $contracts]);
    }

    /**
     * @desc 持仓订单
     */
    public function contractporderlog()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['c_id', 'a.c_id', '='],
                ['statistic_time', 'a.statistic_time', 'between']
            ]);

            $model = StatisticPorderlogModel::where($where);
            $data  = $model->alias('a')
                ->field('a.*,b.c_name_cn as contract_name,b.contract_cate')
                ->join('contract_type b', 'a.c_id = b.c_id')
                ->order('a.statistic_time desc')
                ->limit($limit)
                ->select();


            return table_json($model->count(), $data);
        }

        $contracts = ContractTypeModel::getContracts();
        return view('', ['contracts' => $contracts]);
    }

    /**
     * @desc 平台收益
     */
    public function gains()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['statistic_time', 'a.statistic_time', 'between']
            ]);

            $model = StatisticGainsModel::where($where);
            $data  = $model->alias('a')
                ->field('a.*,b.fName as coin_name')
                ->join('fvirtualcointype b', 'a.coin_id = b.fId')
                ->order('a.statistic_time desc')
                ->limit($limit)
                ->select();

            return table_json($model->count(), $data);
        }
        return view();
    }

    /**
     * @desc 平台收益累计
     */
    public function gainsaccu()
    {
        $query = get('statistic_time/a');
        $where = [];
        if (!empty($query[0])) {
            $where[] = ['statistic_time', 'EGT', $query[0]];
        }

        if (!empty($query[1])) {
            $where[] = ['statistic_time', 'ELT', $query[1]];
        }

        $field = [
            'sum(a.withdraw_fees) as withdraw_fees',
            'sum(a.market_fees) as market_fees',
            'sum(a.market_commission) as market_commission',
            'sum(a.airdrop) as airdrop',
            'sum(a.amount_change) as amount_change',
            'sum(a.p_fees) as p_fees',
            'sum(a.close_p_loss) as close_p_loss',
            'sum(a.gains_or_loss) as gains_or_loss',
            'b.fName as coin_name',
        ];

        $model = StatisticGainsModel::where($where);
        $data  = $model->alias('a')
            ->field($field)
            ->join('fvirtualcointype b', 'a.coin_id = b.fId')
            ->order('a.statistic_time desc')
            ->group('a.coin_id')
            ->select();

        return view('', ['data' => $data]);
    }

    /**
     * @desc 资产对账表
     */
    public function assets()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['statistic_time', 'a.statistic_time', 'between']
            ]);

            $model = StatisticAssetsModel::where($where);
            $data  = $model->alias('a')
                ->field('a.*,b.fName as coin_name')
                ->join('fvirtualcointype b', 'a.coin_id = b.fId')
                ->order('a.statistic_time desc')
                ->limit($limit)
                ->select();

            return table_json($model->count(), $data);
        }
        return view();
    }

    /**
     * @desc 资产对账表累计
     */
    public function assetsaccu()
    {
        $query = get('statistic_time/a');
        $where = [];
        if (!empty($query[0])) {
            $where[] = ['statistic_time', 'EGT', $query[0]];
        }

        if (!empty($query[1])) {
            $where[] = ['statistic_time', 'ELT', $query[1]];
        }

        $field = [
            'sum(a.recharge_amount) as recharge_amount',
            'sum(a.withdraw_amount) as withdraw_amount',
            'sum(a.withdraw_fees) as withdraw_fees',
            'sum(a.market_fees) as market_fees',
            'sum(a.market_commission) as market_commission',
            'sum(a.airdrop) as airdrop',
            'sum(a.amount_change) as amount_change',
            'sum(a.p_fees) as p_fees',
            'sum(a.close_p_loss) as close_p_loss',
            'sum(a.otc_buy) as otc_buy',
            'sum(a.otc_sell) as otc_sell',
            'sum(a.loss) as loss',
            'b.fName as coin_name',
        ];

        $model = StatisticAssetsModel::where($where);
        $data  = $model->alias('a')
            ->field($field)
            ->join('fvirtualcointype b', 'a.coin_id = b.fId')
            ->order('a.statistic_time desc')
            ->group('a.coin_id')
            ->select();

        return view('', ['data' => $data]);
    }
}