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

class User extends Base
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
            $order = 'flastUpdateTime desc';

            $where[] = ['user_node', '=', session('user_invita_code')];
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

    /**
     * @desc 查看身份证
     */
    public function viewIdentity()
    {
        if (!$fId = get('fId')) {
            return error('参数有误');
        }
        $info = UserModel::field('fIdentityPath,fIdentityPath2,fIdentityPath3')->find($fId);
        $uploadUrl = config('upload.domain');
        $info['fIdentityPath']  = $uploadUrl . $info['fIdentityPath'];
        $info['fIdentityPath2'] = $uploadUrl . $info['fIdentityPath2'];
        $info['fIdentityPath3'] = $uploadUrl . $info['fIdentityPath3'];
        return view('', $info);
    }

    /**
     * @desc 修改身份证信息
     */
    public function updateIdentity()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['fId', '', 'require', 'ID'],
                ['fRealName', '', 'require', '会员真实名称'],
                ['fIdentityNo', '', 'require', '证件号码']
            ]);

            $res = UserModel::update($data);
            return $res ? success('修改成功') : error('修改失败');
        }
        $info = UserModel::field('fId,fRealName,fidentityType,fIdentityNo')->find(get('fId'));
        return view('', $info);
    }

    /**
     * @desc 会员审核列表
     */
    public function checkList()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'floginName|fRealName|fNickName', 'like'],
            ]);
            $where[] = ['fIdentityStatus', '=', 1];
            $field = 'fId,floginName,fStatus,fNickName,fRealName,fhasRealValidate,fIdentityStatus,fTelephone,fEmail,fidentityType,fIdentityNo';
            $order = 'flastUpdateTime desc';

            $model = UserModel::where($where);
            $data  = $model->field($field)->order($order)->limit($limit)->select();

            return table_json($model->count(), $data);
        }
        return view();
    }

    /**
     * @desc 审核
     */
    public function checkUser()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['fId', '', 'require', 'ID'],
                'fail_reason',
                'notice_lang',
                'fIdentityStatus'
            ]);

            UserModel::where('fId', $data['fId'])->update(['fIdentityStatus' => $data['fIdentityStatus']]);
            if ($data['fIdentityStatus'] == 3) {
                if (!$data['fail_reason']) {
                    return error('请填写不通过原因');
                }
                $info = UserModel::find($data['fId']);
                $reasons = UserModel::getReasonType();
                $msg = $reasons[$data['notice_lang']][$data['fail_reason']];
                if ($info['fTelephone']) {
                    SmsService::send($info['fTelephone'], $msg);
                } else {
                    EmailService::send($info['fEmail'], $msg);
                }

            }
            return success('操作成功');
        }

        $info = UserModel::find(get('fId'));
        $uploadUrl = config('upload.domain');
        $info['fIdentityPath']  = $uploadUrl . $info['fIdentityPath'];
        $info['fIdentityPath2'] = $uploadUrl . $info['fIdentityPath2'];
        $info['fIdentityPath3'] = $uploadUrl . $info['fIdentityPath3'];

        return view('', $info);
    }

    /**
     * @desc 会员登陆日志
     */
    public function loginlog()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
               ['keyword', 'fkey2', 'like']
            ]);
            $order = 'fCreateTime desc';

            $where[] = ['b.user_node', '=', session('user_invita_code')];
            $model = LogModel::alias('a')->join('fuser b', 'a.fkey1=b.fId', 'left')->field('a.*')->where($where);
            $data  = $model->order($order)->limit($limit)->select();

            return table_json($model->count(), $data);
        }
        return view();
    }

    /**
     * @desc 会员推广日志
     */
    public function spread()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['id', 'a.parent_id', '='],
                ['keyword', 'b.floginName|b.fRealName|b.fNickName', 'like'],
            ]);
            $order = 'create_time desc';

            $model = UserSpreadModel::alias('a')
                ->join('fuser b', 'a.parent_id = b.fId')
                ->where($where);
            $data  = $model->order($order)->limit($limit)->order('create_time desc')->select();
            return table_json($model->count(), $data);
        }
        return view();
    }

    /**
     * @desc 会员分润列表
     */
    public function shareFees()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['id', 'a.parent_id', '='],
                ['keyword', 'b.floginName|b.fRealName|b.fNickName', 'like'],
            ]);

            $model = UserShareFeesModel::alias('a')
                ->join('fuser b', 'a.parent_id = b.fId')
                ->where($where);
            $data  = $model->limit($limit)->order('create_time desc')->select();
            return table_json($model->count(), $data);
        }
        return view();
    }
}