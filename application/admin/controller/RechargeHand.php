<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */

namespace app\admin\controller;

use app\common\model\CoinsModel;
use app\common\model\RechargeHandModel;
use app\common\model\UserModel;
use app\common\model\UserWalletModel;

class RechargeHand extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'b.floginName|b.fRealName|b.fNickName|b.fEmail|b.fTelephone', 'like'],
                ['coin', 'a.FVirtualCoinTypeId', '='],
                ['status', 'a.FStatus', '=']
            ]);

            $field  = 'a.*,b.floginName,b.fNickName,b.fRealName,b.fEmail,b.fTelephone,c.fName as coin,d.fName as creator';
            $order  = 'a.FCreateTime desc';

            $model = RechargeHandModel::alias('a')
                ->join('fuser b', 'a.FUserId = b.fId')
                ->join('fvirtualcointype c', 'a.FVirtualCoinTypeId = c.fId')
                ->join('fadmin d', 'a.FCreatorId = d.fId')
                ->field($field)
                ->where($where);

            $data = $model->order($order)->limit($limit)->select();
            return table_json($model->count(), $data);
        }

        $coins = CoinsModel::getCoins();
        return view('', compact('coins'));
    }

    /**
     * @desc 获取数据
     */
    public function create()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['FUserId', '', 'require', 'UID'],
                ['FVirtualCoinTypeId', '', 'require', '币种'],
                'FQty',
                ['FStatus', 1],
                ['FCreateTime', date('Y-m-d H:i:s')],
                ['FCreatorId', session('uid')]
            ]);

            $res = RechargeHandModel::insert($data);
            return $res ? success('添加成功') : error('添加失败');
        }

        return view('', ['coins' => CoinsModel::getCoins()]);
    }

    /**
     * @desc 审核
     */
    public function check()
    {
        if (!$id = post('id')) {
            return error('参数有误');
        }

        $info = RechargeHandModel::find($id);
        if ($info['FStatus'] == 2) {
            return error('只有暂存状态的才可审核');
        }

        try {
            RechargeHandModel::startTrans();
            $res = RechargeHandModel::where('FId', $id)->update(['FStatus' => 2]);
            if (!$res) {
                throw new \Exception('状态修改异常');
            }
            //审核后添加用户资金
            $where[] = ['fuid', '=', $info['FUserId']];
            $where[] = ['fVi_fId', '=', $info['FVirtualCoinTypeId']];
            $res = UserWalletModel::where($where)->setInc('fTotal', $info['FQty']);
            if (!$res) {
                throw new \Exception('添加用户资金异常');
            }
            RechargeHandModel::commit();

            return success('操作成功');
        } catch (\Throwable $e) {
            RechargeHandModel::rollback();
            return error('操作失败：' . $e->getMessage());
        }
    }

    /**
     * @desc 审核
     */
    public function delete()
    {
        if (!$id = post('id')) {
            return error('参数有误');
        }

        $where[] = ['FId', 'in', $id];
        $where[] = ['FStatus', '=', 2];

        $info = RechargeHandModel::where($where)->find();
        if ($info) {
            return error('只有暂存状态的才可删除');
        }
        $res = RechargeHandModel::where('FId', 'in', $id)->delete();
        return $res ? success('操作成功') : error('操作失败');
    }

    /**
     * @desc 选择用户
     */
    public function selectUser()
    {
        if ($this->request->get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'floginName|fRealName|fNickName|fTelephone|fEmail|fIdentityNo', 'like'],
            ]);
            $field = 'fId,floginName,fRealName,fNickName,fTelephone,fEmail,fIdentityNo,fStatus';
            $model = UserModel::field($field)->where($where);
            $data  = $model->limit($limit)->select();

            return table_json($model->count(), $data);
        }

        return view('');
    }
}