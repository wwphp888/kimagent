<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */

namespace app\player\controller;

use app\common\model\AdminModel;
use app\common\model\ExperienceModel;
use app\common\model\PlayerModel;
use app\common\model\PlayerSpreadModel;
use app\common\service\SmsService;
use think\Controller;

class Index extends Controller
{
    public $skipLogin = [
        'player/index/login',
        'player/index/test'
    ];

    public function __construct()
    {
        parent::__construct();
        $curUrl = $this->request->module() . '/' . lcfirst($this->request->controller()) . '/' . $this->request->action();
        if (!in_array($curUrl, $this->skipLogin) && !session('uid') && !session('roleid')) {
            $this->redirect('player/index/login');
        }
    }

    public function index()
    {
        return view();
    }

    public function home()
    {
        if ($this->request->get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'user_account', 'like'],
                ['win_type', 'win_type', '='],
                ['player_fee', 'player_fee', '='],
                ['out_type', 'out_type', '=']
            ]);

            $count = PlayerModel::where($where)->count();
            $data  = PlayerModel::where($where)->order('fid', 'desc')->limit($limit)->select();

            foreach ($data as &$v) {
                $v['add_time'] = date('Y-m-d H:i:s', strtotime($v['add_time']));
                $v['update_time'] = date('Y-m-d H:i:s', strtotime($v['update_time']));
            }

            return table_json($count, $data);
        }

        return view();
    }

    /**
     * @desc 设置中奖
     */
    public function setWin()
    {
        $uid = post('uid');
        if (!$uid) {
            return error('uid不存在');
        }
        $error = $error1 = 0;
        $result = PlayerModel::where('f_uid', 'in', $uid)->select();
        foreach ($result as $v) {
            if ($v['win_type'] == 1 || $v['player_fee'] != 2) {
                $error = 1;
            }
            if ($v['out_type'] != 0) {
                $error1 = 1;
            }
        }

        if ($error == 1) {
            return error('设置失败:已中奖或未缴纳的无法设置');
        }
        if ($error1 == 1) {
            return error('设置失败:只有未退出的人才能设置中奖');
        }
        PlayerModel::where('f_uid', 'in', $uid)->update(['win_type' => 1]);

        foreach ($result as $v) {
            ExperienceModel::insert([
                'f_uid' => $v['f_uid'],
                'f_available' => 1500,
            ]);

            SmsService::send($v['user_account'], '恭喜您中奖');
        }

        return success('设置成功');
    }

    /**
     * @desc 退出审核
     */
    public function outcheck()
    {
        if (!$uid = post('uid')) {
            return error('uid不存在');
        }

        $result = PlayerModel::where('f_uid', 'in', $uid)->select();

        $error = 0;
        foreach ($result as $v) {
            if ($v['out_type'] != 1) {
                $error = 1;
            }
        }
        if ($error == 1) {
            return error('设置失败:已中奖或未缴纳的无法设置');
        }

        PlayerModel::where('f_uid', 'in', $uid)->update(['out_type' => 3]);
        return success('设置成功');
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

            $info = AdminModel::where(['fName' => $username])->find();
            if (!$info) {
                return error('账号不存在');
            }
            if (!$info['fStatus']) {
                return error('账号被禁用, 请联系管理员');
            }

            if (admin_md5($password) != $info['fPassword']) {
                return error('密码错误');
            }

            session('uid', $info['fId']);
            session('username', $info['fName']);
            session('roleid', $info['froleId']);

            return success('登陆成功');
        }

        return view();
    }

    /**
     * @desc 退出
     */
    public function logout()
    {
        session(null);
        $this->redirect('/player');
    }

    /**
     * @desc 推广列表
     */
    public function spread()
    {
        if ($this->request->get('page')) {
            list ($page, $limit) = [
                get('page', 1),
                get('limit', 25)
            ];

            $total  = PlayerSpreadModel::count();
            $result = PlayerSpreadModel::order('id', 'desc')->limit(($page-1)*$limit, $limit)->select();

            $uids = [];
            foreach ($result as $v) {
                $uids[] = $v['parent_id'];
                $uids[] = $v['child_id'];
            }

            $player = PlayerModel::where('f_uid', 'in', $uids)->column('user_account', 'f_uid');

            foreach ($result as &$v) {
                $v['create_time'] = date('Y-m-d', strtotime($v['create_time']));
                $v['update_time'] = date('Y-m-d', strtotime($v['update_time']));
                $v['child_name']  = $player[$v['child_id']];
                $v['parent_name'] = $player[$v['parent_id']];
            }

            return table_json($total, $result);
        }

        return view();
    }
}