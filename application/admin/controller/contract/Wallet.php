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
use app\common\model\AssetChangeModel;
use app\common\model\CoinsModel;
use app\common\model\ContractTurnLogModel;
use app\common\model\ContractTypeModel;
use app\common\model\ContractWalletModel;
use app\common\model\UserWalletModel;

class Wallet extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'c.floginName|c.user_node|c.fId', 'like'],
                ['c_id', 'a.contract_id', '='],
                //['p_type', 'a.p_type', '='],
                ['add_time', 'a.add_time', 'between']
            ]);

            $where[] = ['c.user_node', '=', session('user_invita_code')];
            $model = ContractWalletModel::where($where);
            $data  = $model->alias('a')
                ->field('a.*,c.floginName,c.user_node,c.user_type,d.c_name_cn,d.c_nmae_en')
                ->join('fuser c', 'a.f_uid = c.fId')
                ->join('contract_type d', 'a.contract_id = d.c_id')
                ->order('a.add_time desc')
                ->limit($limit)
                ->select();

            $turnRes = ContractTurnLogModel::query("select contract_id,f_uid,turn_type,sum(f_amount) as total from turn_log group by contract_id,f_uid,turn_type");
            $turnResN = [];
            foreach ($turnRes as $v) {
                $turnResN[$v['f_uid']][$v['contract_id']][$v['turn_type']] = $v['total'];
            }

            foreach ($data as &$v) {
                $v['turn_in'] = empty($turnResN[$v['f_uid']][$v['contract_id']][0]) ? 0 : $turnResN[$v['f_uid']][$v['contract_id']][0];
                $v['turn_out'] = empty($turnResN[$v['f_uid']][$v['contract_id']][1]) ? 0 : $turnResN[$v['f_uid']][$v['contract_id']][1];
            }

            return table_json($model->count(), $data);
        }

        return view('', ['contracts' => ContractTypeModel::getContracts()]);
    }

    /**
     * @desc 资金划转
     */
    public function transfer()
    {
        if ($this->request->isPost()) {
            list ($id, $type, $amount) = [
                post('id'),
                post('type'),
                post('amount')
            ];
            if (!$amount) {
                return error('划转金额为空');
            }

            $info = ContractWalletModel::alias('a')
                ->field('a.*,b.coin_id')
                ->join('contract_market b', 'a.contract_id = b.c_id')
                ->where('a.id', $id)
                ->find();

            try {
                ContractWalletModel::startTrans();
                if ($type == 0) {
                    ContractWalletModel::where('id', $id)->update([
                        'f_total' => ['inc', $amount],
                        'account_interest' => ['inc', $amount],
                        'f_available' => ['inc', $amount],
                    ]);
                    UserWalletModel::where('fuid', $info['f_uid'])->where('fVi_fId', $info['coin_id'])->update(['fTotal' => ['dec', $amount]]);
                } else {
                    ContractWalletModel::where('id', $id)->update([
                        'f_total' => ['dec', $amount],
                        'account_interest' => ['dec', $amount],
                        'f_available' => ['dec', $amount],
                    ]);
                    UserWalletModel::where('fuid', $info['f_uid'])->where('fVi_fId', $info['coin_id'])->update(['fTotal' => ['inc', $amount]]);
                }
                ContractTurnLogModel::insert([
                    'f_uid' => $info['f_uid'],
                    'coin_id' => $info['coin_id'],
                    'turn_type' => $type,
                    'contract_id' => $info['contract_id'],
                    'f_amount' => $amount,
                    'add_time' => date('Y-m-d H:i:s')
                ]);
                ContractWalletModel::commit();
                return success('操作成功');
            } catch (\Throwable $e) {
                ContractWalletModel::rollback();
                return error($e->getMessage());
            }
        }

        $row = ContractWalletModel::alias('a')
            ->field('a.*,b.coin_id')
            ->join('contract_market b', 'a.contract_id = b.c_id')
            ->where('a.id', get('id'))
            ->find();

        $coins = CoinsModel::getCoins();
        $contracts = ContractTypeModel::getContracts();
        return view('', compact('row', 'coins', 'contracts'));
    }


    /**
     * @desc 资产调整
     * @return string|\think\response\View
     */
    public function assetchange()
    {
        if ($this->request->isPost()) {
            list ($id, $amount, $note) = [
                post('id'),
                post('amount'),
                post('note')
            ];

            if (!$id) return error('id empty');

            try {
                ContractWalletModel::startTrans();

                $info = ContractWalletModel::alias('a')
                    ->field('a.*,b.coin_id')
                    ->join('contract_type b', 'a.contract_id=b.c_id')
                    ->where('a.id', $id)
                    ->find();

                if ($amount > $info['f_available']) {
                    return error('扣除金额不能大于可用余额');
                }

                ContractWalletModel::where('id', $id)->update([
                    'f_total' => ['dec', $amount],
                    'account_interest' => ['dec', $amount],
                    'f_available' => ['dec', $amount],
                ]);

                AssetChangeModel::insert([
                    'uid' => $info['f_uid'],
                    'coin_id' => $info['coin_id'],
                    'c_id' => $info['contract_id'],
                    'type' => 2,
                    'amount' => $amount,
                    'note' => $note,
                    'add_time' => date('Y-m-d H:i:s')
                ]);
                ContractWalletModel::commit();
                return success('操作成功');
            } catch (\Throwable $e) {
                ContractWalletModel::rollback();
                return error($e->getMessage());
            }
        }


        return view('', ['id' => get('id')]);
    }
}