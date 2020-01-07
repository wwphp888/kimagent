<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */
namespace app\admin\controller;

use app\common\model\CommissionRatioModel;
use app\common\model\UserModel;
use app\common\model\UserRebateModel;

class UserType extends Base
{
    public function secondNode()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'a.fId|a.floginName', 'like']
            ]);
            $where[] = ['a.user_type', '=', 3];
            $where[] = ['a.user_node', '=', session('user_invita_code')];
            $order = 'flastUpdateTime desc';

            $model = UserModel::alias('a')
                ->join('commission_ratio b', 'a.fId = b.f_uid', 'LEFT')
                ->field('a.fId,a.floginName,a.user_invita_code,b.f_ratio')
                ->where($where);

            $data = $model->order($order)->limit($limit)->select();

            foreach ($data as &$v) {
                $v['introNum'] = UserModel::where('fIntroUser_id', $v['fId'])->count();
                $fId = $v['fId'];
                $childNum = UserModel::query("select count(*) as count from fuser t,(select fuserChilrenNode(" . $fId . ") id) a where FIND_IN_SET(t.fid,a.id) and ");
                $v['childNum'] = $childNum[0]['count'] ? $childNum[0]['count'] - 1 : 0;
            }
            unset($v);
            return table_json($model->count(), $data);
        }
        return view();
    }

    /**
     * @desc 设置超级节点
     */
    public function  createSecondNode()
    {
        if ($this->request->isPost()) {
            list ($fId, $commision_rate) = [post('fId'), post('commision_rate')];
            if (!$fId) return error('请选择用户');
            if (!$commision_rate) return error('请设置佣金比例');
            $info = UserModel::find($fId);
            if ($info['user_type'] == 3) {
                return error('该用户已是二级节点');
            }
            try {
                UserModel::startTrans();
                $res = UserModel::where('fId', $fId)->update([
                    'user_type' => 3
                ]);
                if (!$res) {
                    throw new \Exception('user update error');
                }

                if (CommissionRatioModel::where('f_uid', $fId)->find()) {
                    CommissionRatioModel::where('f_uid', $fId)->update(['f_ratio' => $commision_rate]);
                } else {
                    CommissionRatioModel::insert([
                        'f_uid'     => $fId,
                        'f_account' => $info['floginName'],
                        'user_type' => 3,
                        'f_ratio'   => $commision_rate,
                        'add_time'  => date('Y-m-d'),
                    ]);
                }

                UserModel::commit();
                return success();

            } catch (\Exception $e) {
                UserModel::rollback();
                return error($e->getMessage());
            }
        }

        return view();
    }

    /**
     * @desc 更新超级节点
     */
    public function  updateSecondNode()
    {
        if ($this->request->isPost()) {
            list ($fId, $commision_rate) = [post('fId'), post('commision_rate')];
            if (!$fId) return error('请选择用户');
            if (!$commision_rate) return error('请设置佣金比例');
            try {
                if (CommissionRatioModel::where('f_uid', $fId)->find()) {
                    CommissionRatioModel::where('f_uid', $fId)->update(['f_ratio' => $commision_rate]);
                } else {
                    $info = UserModel::find($fId);
                    CommissionRatioModel::insert([
                        'f_uid'     => $fId,
                        'f_account' => $info['floginName'],
                        'user_node' => $info['user_node'],
                        'user_type' => $info['user_type'],
                        'f_ratio'   => $commision_rate,
                        'add_time'  => date('Y-m-d'),
                    ]);
                }
                return success();

            } catch (\Exception $e) {
                return error($e->getMessage());
            }
        }

        if (!$fId = get('fId')) {
            if (!$fId) return error('id empty');
        }
        $row = UserModel::alias('a')
            ->join('commission_ratio b', 'a.fId = b.f_uid', 'LEFT')
            ->field('a.fId,a.floginName,b.f_ratio')
            ->where('a.fId', '=', $fId)
            ->find();

        return view('', ['row' => $row]);
    }


    /**
     * @desc 取消超级节点
     * @return string
     */
    public function cancelSecondNode()
    {
        if (!$fId = post('fId')) {
            return error('id为空');
        }

        $res = UserModel::where('fId', $fId)->update(['user_type' => 0]);
        if ($res) {
            return success();
        }
        return error();
    }

    /**
     * @desc 返佣
     * @return string|\think\response\View
     */
    public function rebate()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'f_account|f_uid', 'like'],
                ['add_time', 'add_time', 'between']
            ]);
            $where[] = ['super_user_node', '=', session('user_invita_code')];
            $order = 'add_time desc';
            $model = UserRebateModel::where($where);
            $data = $model->order($order)->limit($limit)->select();

            return table_json($model->count(), $data);
        }
        return view();
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
            $where[] = ['a.user_node', '=', session('user_invita_code')];
            $field = 'fId,floginName,fRealName,fNickName,fTelephone,fEmail,fIdentityNo,fStatus';
            $model = UserModel::field($field)->where($where);
            $data  = $model->limit($limit)->select();

            return table_json($model->count(), $data);
        }

        return view('');
    }
}