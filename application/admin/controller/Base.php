<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */

namespace app\admin\controller;

use app\common\model\SysAuthModel;
use think\Controller;

class Base extends Controller
{
    public $uid;
    public $roleid;
    public $username;
    public $curUrl;

    public $skipLogin = [
        'admin/index/login',
        'admin/index/getcode',
        'admin/index/test',
    ];

    public $skipAuth = [
        'admin/index/index',
        'admin/index/login',
        'admin/index/logout',
        'admin/index/getcode',
        'admin/index/test',
        'admin/index/clearcache',
        'admin/index/home',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->curUrl = $this->getCurUrl();
        $this->checkLogin();
        //$this->checkAuth();
    }

    /**
     * @desc 检查登陆
     */
    protected function checkLogin()
    {
        if (!in_array($this->curUrl, $this->skipLogin) && !session('uid') && !session('roleid')) {
            $this->redirect('admin/index/login');
        }
    }

    /**
     * @desc 检查权限
     */
    protected function checkAuth()
    {
        //SysAuthModel::AuthInit();
        //$access = session('accessUrl');
//        if (!$access) {
//            echo '403';exit;
//        }
//
//        if (!in_array($this->curUrl, $this->skipAuth) && !in_array($this->curUrl, $access)) {
//            echo '403';exit;
//        }
    }

    /**
     * @desc 得到当前的url
     * @return string
     */
    protected function getCurUrl()
    {
        return $this->request->module() . '/' . lcfirst($this->request->controller()) . '/' . $this->request->action();
    }
}