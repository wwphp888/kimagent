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
use app\common\model\SysRoleModel;


class SysRole extends Base
{
    public function index()
    {
        if (get('page')) {
            return table_json(0, SysRoleModel::select());
        }
        return view();
    }

    /**
     * @desc 新增
     */
    public function create()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['fname', '', 'require|unique:frole', '名称'],
                'fdescription',
                'access'
            ]);
            SysRoleModel::insert($data);
            SysAuthModel::clearAuthCache();
            return success('修改成功');
        }
        return view('', ['authTree' => SysAuthModel::getAuth(false, true)]);
    }

    /**
     * @desc 修改
     */
    public function update()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['fid', '', 'require', 'ID'],
                ['fname', '', 'require|unique:frole', '名称'],
                'fdescription',
                'access'
            ]);
            SysRoleModel::update($data);
            SysAuthModel::clearAuthCache();
            return success('修改成功');
        }

        $row = SysRoleModel::find(get('id'));
        $authTree = SysAuthModel::getAuth(false, true);
        return view('', compact('row', 'authTree'));
    }

    /**
     * @desc 删除
     */
    public function delete()
    {
        if (!post('id')) {
            return error('id为空');
        }
        SysRoleModel::where(['fid' => post('id')])->delete();
        SysAuthModel::clearAuthCache();
        return success('删除成功');
    }
}