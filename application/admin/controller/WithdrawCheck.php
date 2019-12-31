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
use app\common\model\UserFundsRecord;
use app\common\model\UserWalletModel;
use app\common\service\ChainService;

class WithdrawCheck extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'b.floginName|b.fNickName|b.fRealName', 'like'],
                ['address', 'a.withdraw_virtual_address', 'like'],
                ['coin', 'a.fVi_fId2', '=']
            ]);

            $where[] = ['a.fType', '=', 2];
            $where[] = ['a.fStatus', 'in', '1,2'];
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

    /**
     * @desc 提现审核
     */
    public function check()
    {
        if ($this->request->isPost()) {
            $post = post();
            if (empty($post['password'])) {
                return error('请输入提现密码');
            }
            $info = CapitalOperateModel::alias('a')
                ->field('a.*,b.floginName,c.fName as coin_name,c.faccess_key,c.fsecrt_key,c.fip,c.fport,c.chain_name')
                ->join('fuser b', 'a.FUs_fId2 = b.fId')
                ->join('fvirtualcointype c', 'a.fVi_fId2 = c.fId')
                ->where('a.fId', $post['fId'])
                ->find();

            if ($info['fStatus'] != 2) {
                return error('只有状态在正在处理中才能审核');
            }

            try {
                $chain = ChainService::conncet($info['faccess_key'], $info['fsecrt_key'], $info['fip'], $info['fport'], $info['chain_name']);
                $res = $chain->getbalance();

                $amount = $info['fAmount'] - $info['ffees'];
                if ($res['result'] < $amount) {
                    throw new \Exception('钱包余额不足');
                }
                $res = $chain->walletpassphrase($post['password']);
                if ($res['error']) {
                    throw new \Exception($res['error']);
                }
                $res = $chain->sendtoaddress($info['withdraw_virtual_address'], $amount);
                if ($res['error']) {
                    throw new \Exception($res['error']);
                }

                CapitalOperateModel::startTrans();
                try {
                    $update = [
                        'fStatus' => 3,
                        'ftradeUniqueNumber' => $res['result']
                    ];
                    $res = CapitalOperateModel::where('fId', $post['fId'])->update($update);
                    if (!$res) {
                        throw new \Exception('更新状态失败');
                    }
                    $res = UserWalletModel::where('fuid', $info['FUs_fId2'])->where('fVi_fId', $info['fVi_fId2'])->update([
                        'fFrozen' => ['dec', $info['fAmount']],
                        'fLastUpdateTime' => date('Y-m-d H:i:s'),
                    ]);
                    if (!$res) {
                        throw new \Exception('更新钱包失败');
                    }
                    $res = UserFundsRecord::insert([
                        'f_uid' => $info['FUs_fId2'],
                        'coin_id' => $info['fVi_fId2'],
                        'coin_name' => $info['coin_name'],
                        'f_type' => 2,
                        'f_status' => 3,
                        'f_amount' => $info['fAmount'],
                        'funds_tyep' => 1,
                        'add_time' => date('Y-m-d H:i:s'),
                    ]);
                    if (!$res) {
                        throw new \Exception('添加提币记录失败');
                    }
                    $res = UserFundsRecord::insert([
                        'f_uid' => $info['FUs_fId2'],
                        'coin_id' => $info['fVi_fId2'],
                        'coin_name' => $info['coin_name'],
                        'f_type' => 12,
                        'f_status' => 3,
                        'f_amount' => $info['ffees'],
                        'funds_tyep' => 1,
                        'add_time' => date('Y-m-d H:i:s'),
                    ]);
                    if (!$res) {
                        throw new \Exception('添加提币手续费记录失败');
                    }
                    CapitalOperateModel::commit();

                    $res = $chain->walletlock();
                    if ($res['error']) {
                        throw new \Exception('锁定钱包失败');
                    }
                    return success('提现成功');
                } catch (\Throwable $e) {
                    CapitalOperateModel::rollback();
                    return error($e->getMessage());
                }
            } catch (\Throwable $e) {
                return error($e->getMessage());
            }
        }

        $row = CapitalOperateModel::alias('a')
            ->field('a.*,b.floginName')
            ->join('fuser b', 'a.FUs_fId2 = b.fId')
            ->where('a.fId', get('fId'))
            ->find();
        $coins = CoinsModel::getCoins();
        return view('', compact('row', 'coins'));
    }

    /**
     * @desc 取消提现
     */
    public function cancel()
    {
        if (!$fId = post('fId')) {
            return error('fId 不存在');
        }
        $info = CapitalOperateModel::find($fId);
        if ($info['fStatus'] != 2) {
            return error('只有状态在正在处理中才能取消');
        }
        try {
            CapitalOperateModel::startTrans();
            $res = CapitalOperateModel::where('fId', $fId)->update(['fStatus' => 5]);
            if (!$res) {
                throw new \Exception('更新状态失败');
            }
            $res = UserWalletModel::where('fuid', $info['FUs_fId2'])->where('fVi_fId', $info['fVi_fId2'])->update([
                'fFrozen' => ['dec', $info['fAmount']],
                'fTotal'  => ['inc', $info['fAmount']],
                'fLastUpdateTime' => date('Y-m-d H:i:s'),
            ]);
            if (!$res) {
                throw new \Exception('更新钱包失败');
            }
            CapitalOperateModel::commit();
            return success('操作成功');

        } catch (\Throwable $e) {
            CapitalOperateModel::rollback();
            return error($e->getMessage());
        }
    }

    public function complete()
    {
        if ($this->request->isPost()) {
            try {
                list ($fId, $ftradeUniqueNumber) = [post('fId'), post('ftradeUniqueNumber')];
                if (!$ftradeUniqueNumber) {
                    return error('请输入交易号');
                }

                $info = CapitalOperateModel::find($fId);
                if ($info['fStatus'] != 2) {
                    return error('只有状态在正在处理中才能标记为完成');
                }

                CapitalOperateModel::startTrans();
                $res = CapitalOperateModel::where('fId', $fId)->update([
                    'fStatus' => 3,
                    'ftradeUniqueNumber' => $ftradeUniqueNumber
                ]);
                if (!$res) {
                    throw new \Exception('更新状态失败');
                }

                $res = UserWalletModel::where('fuid', $info['FUs_fId2'])->where('fVi_fId', $info['fVi_fId2'])->update([
                    'fFrozen' => ['dec', $info['fAmount']],
                    'fLastUpdateTime' => date('Y-m-d H:i:s'),
                ]);
                if (!$res) {
                    throw new \Exception('更新钱包失败');
                }
                CapitalOperateModel::commit();
                return success('操作成功');

            } catch (\Throwable $e) {
                CapitalOperateModel::rollback();
                return error($e->getMessage());
            }
        }

        $row = CapitalOperateModel::alias('a')
            ->field('a.*,b.floginName')
            ->join('fuser b', 'a.FUs_fId2 = b.fId')
            ->where('a.fId', get('fId'))
            ->find();
        $coins = CoinsModel::getCoins();
        return view('', compact('row', 'coins'));
    }

    /**
     * @desc 修改状态
     */
    public function handleStatus()
    {
        list ($fId, $status) = [post('fId'), post('status')];
        $res = CapitalOperateModel::where('fId', 'in', $fId)->update(['fStatus' => $status]);
        return $res ? success('操作成功') : error('操作失败');
    }
}