<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */
namespace app\admin\controller;

use app\common\model\AssetChangeModel;
use app\common\model\CoinsMarketModel;
use app\common\model\CoinsModel;
use app\common\model\UserModel;
use app\common\model\UserShareFeesModel;
use app\common\model\UserSpreadModel;
use app\common\model\UserWalletModel;

class UserWallet extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'b.floginName|b.fRealName|b.fNickName|b.fEmail|b.fTelephone', 'like'],
                ['coin', 'a.fVi_fId', '='],
            ]);

            $order = 'a.fLastUpdateTime desc';
            $field = 'a.*,b.floginName,b.fNickName,b.fRealName,b.fEmail,b.fTelephone,c.fName as coin';

            $model = UserWalletModel::alias('a')
                ->join('fuser b', 'a.fuid = b.fId')
                ->join('fvirtualcointype c', 'a.fVi_fId = c.fId')
                ->field($field)
                ->where($where);
            $data  = $model->order($order)->limit($limit)->select();

            return table_json($model->count(), $data);
        }
        $coins = CoinsModel::getCoins();
        return view('', compact('coins'));
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
                UserWalletModel::startTrans();

                $info = UserWalletModel::find($id);

                if ($amount > $info['fTotal']) {
                    return error('扣除金额不能大于余额');
                }

                UserWalletModel::where('fId', $id)->update([
                    'fTotal' => ['dec', $amount],
                ]);

                AssetChangeModel::insert([
                    'uid' => $info['fuid'],
                    'coin_id' => $info['fVi_fId'],
                    'type' => 1,
                    'amount' => $amount,
                    'note' => $note,
                    'add_time' => date('Y-m-d H:i:s')
                ]);
                UserWalletModel::commit();
                return success('操作成功');
            } catch (\Throwable $e) {
                UserWalletModel::rollback();
                return error($e->getMessage());
            }
        }


        return view('', ['id' => get('id')]);
    }
}