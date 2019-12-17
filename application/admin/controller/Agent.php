<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */
namespace app\admin\controller;

use app\common\model\LogModel;
use app\common\model\UserModel;
use app\common\model\UserShareFeesModel;
use app\common\model\UserSpreadModel;
use app\common\service\EmailService;
use app\common\service\SmsService;

class Agent extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'floginName|fRealName|fNickName|fTelephone|fEmail|fIdentityNo', 'like'],
                ['fStatus', 'fStatus', '='],
                ['fpostRealValidate', 'fpostRealValidate', '='],
                ['fIdentityStatus', 'fIdentityStatus', '=']
            ]);
            $where[] = ['user_node', '=', session('user_invita_code')];
            $order = 'flastUpdateTime desc';

            $model = UserModel::where($where);
            $data  = $model->order($order)->limit($limit)->select();

            return table_json($model->count(), $data);
        }
        return view();
    }

    /**
     * @desc 状态处理
     */
    public function handleStatus()
    {
        $post = post(['fId', 'status', 'type']);

        if (!$post['fId'])  return error('fId不存在');

        if ($post['type'] == 1) {
            $field = 'fStatus';
        } else if ($post['type'] == 2) {
            $field = 'fneedfee';
        } else {
            $field = 'can_otc';
        }

        $res = UserModel::where('fId', 'in', $post['fId'])->update([$field => $post['status']]);
        return $res ? success('操作成功') : error('操作失败');
    }

    /**
     * @desc 重置密码
     */
    public function resetPassword()
    {
        $post = post(['fId', 'password', 'type']);
        if (!$post['fId']) {
            return error('参数有误');
        }

        $field = $post['type'] == 1 ? 'fLoginPassword' : 'fTradePassword';

        $res = UserModel::where('fId', $post['fId'])->update([$field => admin_md5($post['password'])]);
        return $res ? success('操作成功') : error('操作失败');
    }
}