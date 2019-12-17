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
use app\common\model\OtcOrderLogModel;
use app\common\model\OtcOrderModel;
use app\common\model\UserWalletModel;

class OtcOrderLog extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['order_num', 'a.order_num', '='],
                ['keyword', 'b.floginName|b.fRealName|b.fNickName|b.fTelephone|b.fEmail|b.fIdentityNo', 'like'],
                ['type', 'c.type', '='],
                ['status', 'a.status', '='],
                ['cid', 'd.fId', '='],
                ['create_time', 'a.create_time', 'between']
            ]);

            $model = OtcOrderLogModel::where($where);
            $data  = $model->alias('a')
                ->field('a.*,b.fRealName,c.price,c.type,d.fName,e.fRealName as otc_name')
                ->join('fuser b', 'a.uid = b.fId')
                ->join('otc_order c', 'a.order_id = c.id')
                ->join('fvirtualcointype d', 'c.cid = d.fId')
                ->join('fuser e', 'a.otc_acceptance_id = e.fId')
                ->order('a.update_time desc')
                ->limit($limit)
                ->select();

            $payway = ['银行卡', '支付宝', '微信'];
            foreach ($data as &$v) {
                $payArr = [];
                $pay = explode(',', trim($v['pay_way'], ','));
                foreach ($pay as $v1) {
                    $payArr[] = $payway[$v1];
                }
                $v['pay_way'] = join(', ', $payArr);
            }

            return table_json($model->count(), $data);
        }
        return view('', ['coins' => CoinsModel::getCoins()]);
    }

    /**
     * @desc 完成订单
     * @return string
     */
    public function complete()
    {
        if (!$id = get('id')) return error('id不存在');

        $info = OtcOrderLogModel::alias('a')
            ->field('a.*,b.type,b.cid')
            ->join('otc_order b', 'a.order_id = b.id')
            ->find($id);

        if ($info['status'] != 4 && $info['status'] != 5) {
            return error('只有申诉或冻结中才能完成');
        }
        try {
            OtcOrderLogModel::startTrans();
            $res = OtcOrderLogModel::where('id', $id)->update([
                'status' => 2,
                'update_time' => date('Y-m-d H:i:s')
            ]);
            if (!$res) {
                throw new \Exception('OtcOrderLogModel error');
            }
            if ($info['type'] == 1) {
                OtcOrderModel::where('id', $info['order_id'])->update([
                    'success_amount' => ['inc', $info['amount']],
                    'frozen_amount' => ['dec', $info['amount']],
                    'update_time' => date('Y-m-d H:i:s')
                ]);

                UserWalletModel::where('fuid', $info['uid'])->where('fVi_fId', $info['cid'])->update([
                    'fTotal' => ['inc', $info['amount']],
                    'fLastUpdateTime' => date('Y-m-d H:i:s'),
                ]);

                UserWalletModel::where('fuid', $info['otc_acceptance_id'])->where('fVi_fId', $info['cid'])->update([
                    'fFrozen' => ['dec', $info['amount']],
                    'fLastUpdateTime' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $res = OtcOrderModel::where('id', $info['order_id'])->update([
                    'success_amount' => ['inc', $info['amount']],
                    'frozen_amount' => ['dec', $info['amount']],
                    'update_time' => date('Y-m-d H:i:s')
                ]);
                if (!$res) {
                    throw new \Exception('OtcOrderModel error');
                }

                UserWalletModel::where('fuid', $info['otc_acceptance_id'])->where('fVi_fId', $info['cid'])->update([
                    'fTotal' => ['inc', $info['amount']],
                    'fLastUpdateTime' => date('Y-m-d H:i:s'),
                ]);

                UserWalletModel::where('fuid', $info['uid'])->where('fVi_fId', $info['cid'])->update([
                    'fFrozen' => ['dec', $info['amount']],
                    'fLastUpdateTime' => date('Y-m-d H:i:s'),
                ]);
            }

            OtcOrderLogModel::commit();
            return success('操作成功');
        } catch (\Throwable $e) {
            OtcOrderLogModel::rollback();
            return error($e->getMessage());
        }
    }

    /**
     * @desc 取消订单
     * @return string
     */
    public function cancel()
    {
        if (!$id = get('id')) return error('id不存在');

        $info = OtcOrderLogModel::alias('a')
            ->field('a.*,b.type,b.cid')
            ->join('otc_order b', 'a.order_id = b.id')
            ->find($id);

        if ($info['status'] != 4 && $info['status'] != 5) {
            return error('只有申诉或冻结中才能取消');
        }
        try {
            OtcOrderLogModel::startTrans();
            OtcOrderLogModel::where('id', $id)->update([
                'status' => 3,
                'update_time' => date('Y-m-d H:i:s')
            ]);

            if ($info['type'] == 1) {
                OtcOrderModel::where('id', $info['order_id'])->update([
                    'frozen_amount' => ['dec', $info['amount']],
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            } else {
                OtcOrderModel::where('id', $info['order_id'])->update([
                    'frozen_amount' => ['dec', $info['amount']],
                    'update_time' => date('Y-m-d H:i:s')
                ]);
                UserWalletModel::where('fuid', $info['uid'])->where('fVi_fId', $info['cid'])->update([
                    'fFrozen' => ['dec', $info['amount']],
                    'fTotal'  => ['inc', $info['amount']],
                    'fLastUpdateTime' => date('Y-m-d H:i:s'),
                ]);
            }
            OtcOrderLogModel::commit();
            return success('操作成功');
        } catch (\Throwable $e) {
            OtcOrderLogModel::rollback();
            return error($e->getMessage());
        }
    }
}