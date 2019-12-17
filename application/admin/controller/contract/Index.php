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
use app\common\model\CoinsModel;
use app\common\model\ContractMarketModel;
use app\common\model\ContractRiskModel;
use app\common\model\ContractTypeModel;
use app\common\service\QueueService;

class Index extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'a.c_name_cn', 'like']
            ]);

            $model = ContractTypeModel::where($where);
            $data  = $model->alias('a')
                ->field('a.*,b.*,c.fName as coin')
                ->join('contract_market b', 'a.c_id = b.c_id')
                ->join('fvirtualcointype c', 'a.coin_id = c.fId')
                ->limit($limit)
                ->select();

            $markType = config('marktype.');
            foreach ($data as &$v) {
                $v['mark_type'] = $markType[$v['mark_type']];
            }

            return table_json($model->count(), $data);
        }
        return view('');
    }

    public function create()
    {
        if ($this->request->isPost()) {
            $contract = post('contract/a');
            $market = post('market/a');

            $res = $this->validate(array_merge($contract, $market), [
                'c_name_cn|合约中文名称' => 'require|unique:contract_type',
                'c_nmae_en|合约英文文名称' => 'require|unique:contract_type',
                'coin_id|计价币' => 'require',
                'mark_type|指数类型' => 'require',
                'contract_mul|合约乘数' => 'require|gt:0',
                'contract_cate|合约类型' => 'require',
                'start_time|起始时间' => 'require',
                'settlement_time|持仓费用收取时间' => 'require',
                'trade_time|交易时间' => 'require',
            ]);
            if (true !== $res) {
                return error($res);
            }
            if ($market['deposit_type'] == 1) {
                $market['deposit'] = 0;
            } else {
                $market['deposit_rate'] = 0;
            }

            if ($market['fee_type'] == 1) {
                $market['hold_fee'] = 0;
                $market['open_fee'] = 0;
                $market['close_fee'] = 0;
            } else {
                $market['hold_fee_rate'] = 0;
                $market['open_fee_rate'] = 0;
                $market['close_fee_rate'] = 0;
            }
            if ($market['min_count'] > $market['max_count']) {
                return error('委托最小数量不能大于委托最大数量');
            }
            if ((int)$market['min_count'] == 0) {
                $market['min_count'] = -1;
            }
            if ((int)$market['max_count'] == 0) {
                $market['max_count'] = -1;
            }
            try {
                $time = date('Y-m-d H:i:s');
                $contract['c_stauts'] = 1;
                $contract['add_time'] = $time;
                $contract['update_time'] = $time;

                ContractTypeModel::startTrans();
                $c_id = ContractTypeModel::insert($contract, false, true);
                if (!$c_id) {
                    return error('contract insert error');
                }
                $market['c_id'] = $c_id;
                $market['coin_id'] = $contract['coin_id'];
                $market['version'] = 0;
                $market['show'] = 0;
                $market['status'] = 0;
                $market['trade_status'] = 0;
                $market['leveraged_trade'] = 1;
                ContractMarketModel::insert($market);
                ContractTypeModel::commit();

                //初始化钱包账号
                QueueService::push('InitContractWallet', [$c_id]);

                return success('新增成功');
            } catch (\Throwable $e) {
                ContractTypeModel::rollback();
                return error('失败:' . $e->getMessage());
            }
        }

        $markType = config('marktype.');
        $coins = CoinsModel::getCoins();
        return view('', compact('markType', 'coins'));
    }

    public function update()
    {
        if ($this->request->isPost()) {
            $contract = post('contract/a');
            $market = post('market/a');

            $marketInfo = ContractMarketModel::where('c_id',$contract['c_id'])->find();
            if ($marketInfo['status'] == 0) {
                $res = $this->validate(array_merge($contract, $market), [
                    'c_name_cn|合约中文名称' => 'require|unique:contract_type',
                    'c_nmae_en|合约英文文名称' => 'require|unique:contract_type',
                    'coin_id|计价币' => 'require',
                    'mark_type|指数类型' => 'require',
                    'contract_mul|合约乘数' => 'require|gt:0',
                    'contract_cate|合约类型' => 'require',
                    'start_time|起始时间' => 'require',
                    'settlement_time|持仓费用收取时间' => 'require',
                    'trade_time|交易时间' => 'require',
                ]);
                if (true !== $res) {
                    return error($res);
                }
                if ($market['deposit_type'] == 1) {
                    $market['deposit'] = 0;
                } else {
                    $market['deposit_rate'] = 0;
                }

                if ($market['fee_type'] == 1) {
                    $market['hold_fee'] = 0;
                    $market['open_fee'] = 0;
                    $market['close_fee'] = 0;
                } else {
                    $market['hold_fee_rate'] = 0;
                    $market['open_fee_rate'] = 0;
                    $market['close_fee_rate'] = 0;
                }
                if ($market['min_count'] > $market['max_count']) {
                    return error('委托最小数量不能大于委托最大数量');
                }
                if ((int)$market['min_count'] == 0) {
                    $market['min_count'] = -1;
                }
                if ((int)$market['max_count'] == 0) {
                    $market['max_count'] = -1;
                }
                $market['coin_id'] = $contract['coin_id'];
                $market['start_time'] = $market['start_time'] ? $market['start_time'] : date('Y-m-d H:i:s');
                $market['version'] = 0;
                $market['show'] = 0;
                $market['status'] = 0;
                $market['trade_status'] = 0;
            }

            $contract['update_time'] = date('Y-m-d H:i:s');
            ContractTypeModel::update($contract);
            ContractMarketModel::where('c_id', $contract['c_id'])->update($market);
            return success('修改成功');
        }

        $contract = ContractTypeModel::find(get('c_id'));
        $market = ContractMarketModel::where('c_id', get('c_id'))->find();
        $coins = CoinsModel::getCoins();
        $markType = config('marktype.');

        return view('', compact('contract', 'market', 'coins', 'markType'));
    }

    /**
     * @desc 禁用/启用
     */
    public function handleStatus()
    {
        list ($c_id, $status, $type) = [
            post('c_id'),
            post('status'),
            post('type'),
        ];

        if (!$c_id) return error('id error');

        $field = $type == 1 ? 'status' : 'trade_status';

        ContractMarketModel::where('c_id', $c_id)->update([$field => $status]);
        return success('操作成功');
    }

    /**
     * @desc 修改保证金
     */
    public function updateDoposit()
    {
        if ($this->request->isPost()) {
            $c_id = post('c_id');
            if (!$c_id) return error('c_id error');
            ContractMarketModel::where('c_id', $c_id)->update(post());
            return success('修改成功');
        }

        $market = ContractMarketModel::field('c_id,deposit_type,deposit_rate,deposit')->where('c_id', get('c_id'))->find();
        return view('', ['market' => $market]);
    }

    /**
     * @desc 风控
     */
    public function risk()
    {
        if ($this->request->isPost()) {
            $c_id = post('c_id');
            if (!$c_id) return error('c_id error');

            $data = post('data/a');

            foreach ($data as &$v) {
                $v['c_id'] = $c_id;
            }

            ContractRiskModel::where(['c_id' => $c_id])->delete();
            ContractRiskModel::insertAll($data);
            return success('操作成功');
        }

        $c_id = get('c_id');
        $data = ContractRiskModel::where('c_id', $c_id)->select();
        $i = count($data) + 1;
        return view('', compact('c_id', 'data', 'i'));
    }
}