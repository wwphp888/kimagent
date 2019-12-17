<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */

namespace app\admin\controller;

use app\common\model\OtcAccountModel;
use app\common\model\OtcShopModel;
use app\common\model\OtcWalletModel;
use app\common\model\UserWalletModel;
use app\common\service\EmailService;
use app\common\service\SmsService;

class OtcShop extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['name', 'otc_name', 'like'],
                ['status', 'otc_status', '=']
            ]);
            $order = 'a.otc_add_time desc';

            $model = OtcShopModel::alias('a')->field('a.*,b.fName as check_name')->join('fadmin b', 'a.otc_reviewer = b.fId', 'left')->where($where);
            $data  = $model->order($order)->limit($limit)->select();

            return table_json($model->count(), $data);
        }
        return view();
    }

    /**
     * @desc 支付方式管理
     */
    public function payment()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'b.floginName|b.fRealName|b.fNickName|b.fTelephone|b.fEmail|b.fIdentityNo', 'like'],
            ]);

            $model = OtcAccountModel::where($where);
            $data  = $model->alias('a')
                ->field('a.*,b.fRealName')
                ->join('fuser b', 'a.uid = b.fId')
                ->order('a.id desc')
                ->limit($limit)
                ->select();

            return table_json($model->count(), $data);
        }
        return view();
    }

    /**
     * @desc 审核查看
     * @return \think\response\View
     */
    public function check()
    {
        if ($this->request->isPost()) {
            list ($id, $otcStatus, $otcCause) = [
                post('id'),
                post('otc_status'),
                post('otc_cause'),
            ];

            $info = OtcShopModel::find($id);

            if ($otcStatus == 3) {
                if (!$otcCause) {
                    return error('请说明审核失败原因');
                }
                $time = date('Y-m-d H:i:s');
                $data = [
                    'otc_status' => $otcStatus,
                    'otc_cause' => $otcCause,
                    'otc_reviewer' => session('uid'),
                    'otc_audit_time' => $time,
                    'otc_update_time' => $time
                ];
                $res = OtcShopModel::where('id', $id)->update($data);
                if (!$res) return error('操作失败');

                if ($info['otc_phone']) {
                    SmsService::send($info['otc_phone'], '抱歉,您申请商户未通过审核,原因: ' . $otcCause);
                }
                return success('操作成功');
            } else {

                $coinId = 9;
                $amount = 5000;
                $uid = $info['f_uid'];
                $time = date('Y-m-d H:i:s');

                $walletInfo = UserWalletModel::where('fuid', $uid)->where('fVi_fId', $coinId)->find();
                if ($walletInfo['fTotal'] < $amount) {
                    return error('资产不足5000');
                }

                try {
                    OtcShopModel::startTrans();
                    $res = OtcShopModel::where(['f_uid' => $uid])->update([
                        'otc_status' => 2,
                        'otc_reviewer' => session('uid'),
                        'otc_audit_time' => $time,
                        'otc_update_time' => $time
                    ]);
                    if (!$res) {
                        throw new \Exception('OtcShopModel update error');
                    }
                    $res = UserWalletModel::where('fuid', $uid)->where('fVi_fId', $coinId)->setDec('fTotal', $amount);
                    if (!$res) {
                        throw new \Exception('VirtualWallet update error');
                    }

                    $res = OtcWalletModel::insert([
                        'coin_id' => $coinId,
                        'otc_total' => $amount,
                        'otc_frozen' => 0,
                        'f_uid' => $uid,
                        'otc_add_time' => date('Y-m-d H:i:s'),
                    ]);
                    if (!$res) {
                        throw new \Exception('OtcWalletModel insert error');
                    }
                    OtcShopModel::commit();
                    return success('操作成功');

                } catch (\Exception $e) {
                    OtcShopModel::rollback();
                    return error($e->getMessage());
                }
            }
        }

        $info = OtcShopModel::find(get('id'));
        $uploadUrl = config('upload.domain');
        $info['otc_hand_url']  = $uploadUrl . $info['otc_hand_url'];
        $info['otc_information_page_url'] = $uploadUrl . $info['otc_information_page_url'];
        $info['otc_certificate_means_url'] = $uploadUrl . $info['otc_certificate_means_url'];
        return view('', $info);
    }


    /**
     * @desc 删除
     */
    public function delete()
    {
        OtcShopModel::where('id', 'in', post('id'))->delete();
        return success('删除成功');
    }
}