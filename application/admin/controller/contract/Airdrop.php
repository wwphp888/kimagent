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
use app\common\model\ContractAirdropModel;
use app\common\model\ContractTypeModel;
use app\common\model\ContractWalletModel;
use app\common\model\UserModel;
use app\common\service\ExportService;


class Airdrop extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'b.floginName|b.fId', 'like'],
                ['c_id', 'c.c_id', '=']
            ]);

            $model = ContractAirdropModel::where($where);
            $data  = $model->alias('a')
                ->field('a.*,b.floginName,c.c_name_cn as contract_name,d.fName as coin_name')
                ->join('fuser b', 'a.uid = b.fId')
                ->join('contract_type c', 'a.c_id = c.c_id')
                ->join('fvirtualcointype d', 'c.coin_id = d.fId')
                ->order('a.add_time desc')
                ->limit($limit)
                ->select();

            return table_json($model->count(), $data);
        }

        return view('', ['contracts' => ContractTypeModel::getContracts()]);
    }

    /**
     * @desc 选择空投用户
     */
    public function selectuser()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'floginName|fId', 'like'],
            ]);

            $model = UserModel::where($where);
            $data = $model->limit($limit)->select();
            return table_json($model->count(), $data);
        }

        $contracts = ContractTypeModel::getContracts();
        return view('', ['contracts' => $contracts]);
    }

    /**
     * @desc 执行空投
     */
    public function run()
    {
        list ($keyword, $c_id, $type, $amount, $note) = [
            post('keyword'),
            post('c_id'),
            post('type'),
            post('amount'),
            post('note')
        ];

        if (!$amount) return error('请输入数量');

        $where = [];
        if ($keyword) {
            $where[] = ['floginName|fRealName|fNickName|fTelephone|fEmail|fIdentityNo', 'like', $keyword . '%'];
        }

        $uids = UserModel::where($where)->column('fId');

        $date = date('Y-m-d H:i:s');
        foreach ($uids as $uid) {
            ContractWalletModel::where(['f_uid' => $uid, 'contract_id' => $c_id])->update([
                'f_total' => ['inc', $amount],
                'account_interest' => ['inc', $amount],
                'f_available' => ['inc', $amount],
            ]);
            ContractAirdropModel::insert([
                'uid' => $uid,
                'type' => $type,
                'c_id' => $c_id,
                'amount' => $amount,
                'note' => $note,
                'add_time' => $date,
            ]);
        }

        return success('空投成功');
    }

    /**
     * @desc 导出模板
     */
    public function exporttemp()
    {
        $title = ['用户ID', '合约类型ID', '空投数量', '空投类型', '备注'];
        ExportService::exportCsv('exportTemp.csv', $title);
    }

    /**
     * @desc 上传模板
     * @return string|\think\response\View
     */
    public function importtemp()
    {
        if (get('upload')) {
            $file = $this->request->file('file');
            if ($file) {
                $dir = app()->getRootPath() . 'public/upload/airdrop/';
                $info = $file->validate(['ext'=>'csv'])->move($dir, 'airdrop.csv');
                if ($info) {
                    $filename = $dir . 'airdrop.csv';
                    $data = ExportService::readCsv($filename);
                    unset($data[0]);
                    if ($data) {
                        $date = date('Y-m-d H:i:s');
                        foreach ($data as $k => $v) {
                            list ($uid, $c_id, $amount, $type, $note) = $v;
                            ContractWalletModel::where(['f_uid' => $uid, 'contract_id' => $c_id])->update([
                                'f_total' => ['inc', $amount],
                                'account_interest' => ['inc', $amount],
                                'f_available' => ['inc', $amount],
                            ]);
                            ContractAirdropModel::insert([
                                'uid' => $uid,
                                'type' => $type,
                                'c_id' => $c_id,
                                'amount' => $amount,
                                'note' => $note,
                                'add_time' => $date,
                            ]);

                            if ($k % 100 == 0) {
                                sleep(1);
                            }
                        }
                    }
                    return success('上传成功');

                } else {
                    return error($file->getError());
                }
            }
        }

        return view();
    }
}