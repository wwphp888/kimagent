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
use app\common\service\EmailService;
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
        $rechargeUsers1 = CapitalOperateModel::alias('a')
            ->join('fuser b', 'a.FUs_fId2=b.fId')
            ->where('b.user_node', session('user_invita_code'))
            ->where('a.fCreateTime', '>=', date('Y-m-d'))
            ->where('a.fCreateTime', '<=', date('Y-m-d H:i:s'))
            ->value('count(distinct a.FUs_fId2)');

        $rechargeAmount1 = CapitalOperateModel::alias('a')
            ->join('fuser b', 'a.FUs_fId2=b.fId')
            ->where('b.user_node', session('user_invita_code'))
            ->where('a.fCreateTime', '>=', date('Y-m-d'))
            ->where('a.fCreateTime', '<=', date('Y-m-d H:i:s'))
            ->value('sum(a.fAmount)');

        $tradeUsers1 = ContractOrderLogModel::alias('a')
            ->join('fuser b', 'a.f_uid=b.fId')
            ->where('b.user_node', session('user_invita_code'))
            ->where('a.add_time', '>=', date('Y-m-d'))
            ->where('a.add_time', '<=', date('Y-m-d H:i:s'))
            ->value('count(distinct a.f_uid)');

        $tradeAmount1 = ContractOrderLogModel::alias('a')
            ->join('fuser b', 'a.f_uid=b.fId')
            ->where('b.user_node', session('user_invita_code'))
            ->where('a.add_time', '>=', date('Y-m-d'))
            ->where('a.add_time', '<=', date('Y-m-d H:i:s'))
            ->value('sum(a.f_amount)');

        $rechargeAmount1 = $rechargeAmount1 ? $rechargeAmount1 : 0;
        $tradeAmount1 = $tradeAmount1 ? $tradeAmount1 : 0;

        $rechargeUsers2 = CapitalOperateModel::alias('a')
            ->join('fuser b', 'a.FUs_fId2=b.fId')
            ->where('b.user_node', session('user_invita_code'))
            ->where('a.fCreateTime', '>=', date('Y-m-d', time() - 86400 * 30))
            ->where('a.fCreateTime', '<=', date('Y-m-d H:i:s'))
            ->value('count(distinct a.FUs_fId2)');

        $rechargeAmount2 = CapitalOperateModel::alias('a')
            ->join('fuser b', 'a.FUs_fId2=b.fId')
            ->where('b.user_node', session('user_invita_code'))
            ->where('a.fCreateTime', '>=', date('Y-m-d', time() - 86400 * 30))
            ->where('a.fCreateTime', '<=', date('Y-m-d H:i:s'))
            ->value('sum(a.fAmount)');

        $tradeUsers2 = ContractOrderLogModel::alias('a')
            ->join('fuser b', 'a.f_uid=b.fId')
            ->where('b.user_node', session('user_invita_code'))
            ->where('a.add_time', '>=', date('Y-m-d', time() - 86400 * 30))
            ->where('a.add_time', '<=', date('Y-m-d H:i:s'))
            ->value('count(distinct a.f_uid)');

        $tradeAmount2 = ContractOrderLogModel::alias('a')
            ->join('fuser b', 'a.f_uid=b.fId')
            ->where('b.user_node', session('user_invita_code'))
            ->where('a.add_time', '>=', date('Y-m-d', time() - 86400 * 30))
            ->where('a.add_time', '<=', date('Y-m-d H:i:s'))
            ->value('sum(a.f_amount)');

        $rechargeAmount2 = $rechargeAmount2 ? $rechargeAmount2 : 0;
        $tradeAmount2 = $tradeAmount2 ? $tradeAmount2 : 0;

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

        $data = [
            'rechargeUsers1' => $rechargeUsers1,
            'rechargeAmount1' => $rechargeAmount1,
            'tradeUsers1' => $tradeUsers1,
            'tradeAmount1' => $tradeAmount1,

            'rechargeUsers2' => $rechargeUsers2,
            'rechargeAmount2' => $rechargeAmount2,
            'tradeUsers2' => $tradeUsers2,
            'tradeAmount2' => $tradeAmount2,

            'rechargeUsers' => $rechargeUsers,
            'rechargeAmount' => $rechargeAmount,
            'tradeUsers' => $tradeUsers,
            'tradeAmount' => $tradeAmount,

        ];


        return view('', $data);
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
            if ($code != session('code')) {
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
        if (env('free_code')) {
            session('code', 666666);
            return success('发送成功');
        }
        
        $username = post('username');
        if(preg_match("/^1[34578]{1}\d{9}$/", $username)){
            $res = SmsService::sendCode($username, '您的后台登陆验证码为:%s');
            if (true === $res) {
                return success('已发送到您的手机');
            }
        } else {
            $code = rand(100000, 1000000);
            $res = EmailService::send($username, '欢迎登陆BZEX代理后台, 您的验证码为: ' . $code);
            if (true === $res) {
                session('code', $code);
                return success('已发送到您的邮箱');
            }
        }
        return error('发送失败: ' . $res);
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