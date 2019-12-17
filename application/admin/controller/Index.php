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
use app\common\model\ContractOrderLogModel;
use app\common\model\SysAuthModel;
use app\common\model\UserModel;
use app\common\service\SmsService;
use app\common\model\AdminModel;
use app\common\service\UploadService;

class Index extends Base
{
    public function index()
    {
        return view();
    }

    /**
     * @desc 首页
     */
    public function home()
    {
        $rechargeUsers = CapitalOperateModel::alias('a')
            ->join('fuser b', 'a.FUs_fId2=b.fId')
            ->where('b.user_node', session('user_invita_code'))
            ->value('count(distinct a.FUs_fId2)');

        $rechargeAmount = CapitalOperateModel::alias('a')
            ->join('fuser b', 'a.FUs_fId2=b.fId')
            ->where('b.user_node', session('user_invita_code'))
            ->value('sum(a.fAmount)');

        $tradeUsers = ContractOrderLogModel::alias('a')
            ->join('fuser b', 'a.f_uid=b.fId')
            ->where('b.user_node', session('user_invita_code'))
            ->value('count(distinct a.f_uid)');

        $tradeAmount = ContractOrderLogModel::alias('a')
            ->join('fuser b', 'a.f_uid=b.fId')
            ->where('b.user_node', session('user_invita_code'))
            ->value('sum(a.f_amount)');

        $rechargeAmount = $rechargeAmount ? $rechargeAmount : 0;
        $tradeAmount = $tradeAmount ? $tradeAmount : 0;


        return view('', compact('rechargeUsers', 'rechargeAmount', 'tradeUsers', 'tradeAmount'));
    }

    /**
     * @desc 登陆
     */
    public function login()
    {
        if ($this->request->isPost()) {
            list ($username, $password, $code) = [
                post('username'),
                post('password'),
                post('code')
            ];

            if (!$username || !$password) {
                return error('账号密码为空');
            }
            if (!$code) {
                return error('验证码为空');
            }
            if ($code != session('code') && !env('free_code')) {
                return error('验证码不正确');
            }

            $info = UserModel::where('floginName',  $username)->find();
            if (!$info || $info['user_type'] != 1) {
                return error('账号不存在');
            }
            if (!$info['fStatus']) {
                return error('账号被禁用, 请联系管理员');
            }

            if (admin_md5($password) != $info['fLoginPassword']) {
                return error('密码错误');
            }

            session('uid', $info['fId']);
            session('username', $info['floginName']);
            session('user_invita_code', $info['user_invita_code']);

            return success('登陆成功');
        }

        return view();
    }

    /**
     * @desc 获取验证码
     * @return string
     */
    public function getCode()
    {
        $mobile = post('mobile');
        $res = SmsService::sendCode($mobile, '您的后台登陆验证码为:%s');
        if (true === $res) {
            return success('发送成功');
        }
        return success('发送失败:' . $res);
    }

    /**
     * @desc 退出
     */
    public function logout()
    {
        session(null);
        $this->redirect('/admin');
    }

    /**
     * @desc 修改密码
     */
    public function editLoginPwd()
    {
        if (!$password = post('password')) {
            return error('密码为空');
        }
        $password = admin_md5($password);
        UserModel::where('fId', session('uid'))->update(['fLoginPassword' => $password]);
        return success('修改成功');
    }

    /**
     * @desc 清除菜单
     */
    public function clearCache()
    {
        SysAuthModel::clearAuthCache();
        echo '清除缓存成功';
    }

    /**
     * @desc 上传
     */
    public function upload()
    {
        $info = UploadService::image('file');
        if ($info['code'] == 200) {
            return success('上传成功', ['path' => $info['name'], 'url' => $info['dir']]);
        } else {
            return error('上传错误');
        }
    }

    /**
     * @desc 上传
     */
    public function richTextUpload()
    {
        $info = UploadService::image('upfile');
        if ($info['code'] == 200) {
            $data = [
                'name' => $info['name'],
                'originalName' => $info['name'],
                'size' => $info['size'],
                'state' => 'SUCCESS',
                'type' => $info['type'],
                'url' => $info['dir'],

            ];
            return json_encode($data);
        }
    }
}