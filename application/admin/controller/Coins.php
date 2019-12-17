<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: Coins.php
 * @Date: 2019/10/22
 * @Time: 17:21
 * @Description: 虚拟币管理
 */

namespace app\admin\controller;

use app\common\model\CoinsAddressModel;
use app\common\model\CoinsModel;
use app\common\model\UserWalletModel;
use app\common\model\WithdrawFeeModel;
use app\common\service\ChainService;

class Coins extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'fName|fShortName', 'like']
            ]);
            $order = 'fAddTime desc';

            $model = CoinsModel::where($where);
            $data  = $model->order($order)->limit($limit)->select();

            return table_json($model->count(), $data);
        }
        return view();
    }

    /**
     * @desc 新增
     */
    public function create()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['fName', '', 'require|unique:fvirtualcointype', '名称'],
                'fShortName',
                'faccess_key',
                'fsecrt_key',
                'fip',
                'fport',
                'confirm_times',
                ['chain_name', '', 'require', '链名称'],
                'otcRate',
                'otc_buy_price',
                'otc_sell_price',
                'block_url',
                ['FIsWithDraw', 1],
                ['FIsRecharge', 1],
                ['FIsShare', 1],
                ['fstatus', 1],
                ['version', 0],
                ['fAddTime', date('Y-m-d H:i:s')],
            ]);
            $res = CoinsModel::insert($data);
            return $res ? success('创建成功') : error('创建失败');
        }
        return view();
    }

    /**
     * @desc 修改
     */
    public function update()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['fId', '', 'require', 'ID'],
                ['fName', '', 'require|unique:fvirtualcointype', '名称'],
                'fShortName',
                'faccess_key',
                'fsecrt_key',
                'fip',
                'fport',
                'confirm_times',
                ['chain_name', '', 'require', '链名称'],
                'otcRate',
                'otc_buy_price',
                'otc_sell_price',
                'block_url',
                ['FIsWithDraw', 1],
                ['FIsRecharge', 1],
                ['FIsShare', 1],
                ['isOtcActive', 1]
            ]);
            $res = CoinsModel::update($data);
            return $res ? success('修改成功') : error('修改失败');
        }

        return view('', CoinsModel::find(get('id')));
    }

    /**
     * @desc 修改状态
     */
    public function handleStatus()
    {
        list ($id, $status) = [post('id'), post('status')];
        if (!$id) {
            return error('参数有误');
        }

        $res = CoinsModel::where('fId', 'in', $id)->update(['fstatus' => $status]);
        return $res ? success('操作成功') : error('操作失败');
    }

    /**
     * @desc 提现设置
     */
    public function withdrawSetting()
    {
        if ($this->request->isPost()) {
            $is = WithdrawFeeModel::where('withdraw_fee_type', post('withdraw_fee_type'))->find();
            if ($is) {
                $res = WithdrawFeeModel::where('withdraw_fee_type', post('withdraw_fee_type'))->update(post());
            } else {
                $res = WithdrawFeeModel::insert(post());
            }

            return $res ? success('操作成功') : error('操作失败');
        }
        $info = WithdrawFeeModel::where('withdraw_fee_type', get('id'))->find();
        if (!$info) {
            $info = [
                'min_withdraw' => '',
                'withdraw' => '',
                'withdraw_ratio' => '',
                'withdraw_fee_type' => get('id'),
            ];
        }
        return view('', $info);
    }

    /**
     * @desc 钱包测试
     */
    public function walletTest()
    {
        if (!$id = get('id')) {
            return error('参数有误');
        }
        $coin = CoinsModel::find($id);
        try {
            $chain = ChainService::conncet($coin['faccess_key'], $coin['fsecrt_key'], $coin['fip'], $coin['fport'], $coin['chain_name']);
            $res = $chain->getbalance();

            if (!$res['error']) {
                return success('测试成功:当前主钱包余额为:' . $res['result']);
            }
            return error('测试失败:' . $res['error']);
        } catch (\Throwable $e) {
            return error('测试异常:' . $e->getMessage());
        }

    }

    /**
     * @desc 币种账号情况
     */
    public function viewAccount()
    {
        $fViFId = get('id');
        if (!$fViFId) {
            return error('未选择币种');
        }
        $res = CoinsModel::query("select count(u.fid) as count from fuser u where not exists (select 1 from fvirtualwallet w where w.fVi_fId = {$fViFId} and w.fuid = u.fid)");
        return success('还有' . $res[0]['count'] . '个用户没初始化该币种');
    }

    /**
     * @desc 初始化账号
     */
    public function initAccount()
    {
        $fViFId = get('id');
        if (!$fViFId) {
            return error('未选择币种');
        }
        $res = CoinsModel::query("select u.fid from fuser u where not exists (select 1 from fvirtualwallet w where w.fVi_fId = {$fViFId} and w.fuid = u.fid)");
        if (!$res) {
            return success('没有要初始化的钱包用户');
        }
        $insert = [];
        foreach ($res as $v) {
            $insert[] = [
                'fVi_fId' => $fViFId,
                'fTotal'  => 0,
                'fFrozen' => 0,
                'fLastUpdateTime' => date('Y-m-d H:i:s'),
                'version' => 0,
                'fuid'    => $v['fid']
            ];
        }
        UserWalletModel::insertAll($insert);
        return success('成功初始化钱包用户:' . count($res));
    }

    /**
     * @desc 充值地址
     */
    public function address()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'c.floginName|c.fNickName|c.fRealname|c.fEmail|fTelephone', 'like'],
                ['address', 'a.fAdderess', 'like'],
                ['coin', 'b.fId', '='],
            ]);

            $model = CoinsAddressModel::alias('a')
                ->join('fvirtualcointype b', 'a.fVi_fId = b.fId')
                ->join('fuser c', 'c.fId = a.fuid')
                ->where($where);

            $data  = $model->limit($limit)->select();
            return table_json($model->count(), $data);
        
        }
        return view('', ['coins' => CoinsModel::getCoins()]);
    }
}