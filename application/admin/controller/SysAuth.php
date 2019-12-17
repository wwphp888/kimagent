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

class SysAuth extends Base
{
    public function index()
    {
        if (get('page')) {
            return table_json(0, SysAuthModel::getAuthLevel());
        }
        return view();
    }

    /**
     * @desc 获取数据
     */
    public function create()
    {
        if ($this->request->isPost()) {
            $res = SysAuthModel::insert(post());
            SysAuthModel::clearAuthCache();
            return $res ? success('添加成功') : error('添加失败');
        }
        return view('', ['authLevel' => SysAuthModel::getAuthLevel()]);
    }

    /**
     * @desc 获取数据
     */
    public function update()
    {
        if ($this->request->isPost()) {
            $res = SysAuthModel::update(post());
            SysAuthModel::clearAuthCache();
            return $res ? success('修改成功') : error('修改失败');
        }

        return view('', [
            'row' => SysAuthModel::find(get('id')),
            'authLevel' => SysAuthModel::getAuthLevel()
        ]);
    }

    /**
     * @desc 删除
     */
    public function delete()
    {
        if (!post('id')) {
            return error('id为空');
        }
        SysAuthModel::where(['id' => post('id')])->delete();
        SysAuthModel::clearAuthCache();
        return success('删除成功');
    }
}