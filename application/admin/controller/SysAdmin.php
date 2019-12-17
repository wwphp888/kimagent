<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */

namespace app\admin\controller;

use app\common\model\AdminModel;
use app\common\model\SysAuthModel;
use app\common\model\SysRoleModel;

class SysAdmin extends Base
{
    public function index()
    {
        if (get('page')) {
            $data = AdminModel::alias('a')->field('a.*,b.fname as role')->join('frole b', 'a.froleId = b.fid')->select();
            return table_json(0, $data);
        }
        return view();
    }

    /**
     * @desc 获取数据
     */
    public function create()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['fName', '', 'require|unique:fadmin', '用户名'],
                ['fPassword', '', 'require', '密码'],
                'mobile',
                'froleId',
                ['fStatus', 1],
                ['version', 0],
                ['fCreateTime', date('Y-m-d H:i:s')]
            ]);
            $data['fPassword'] = admin_md5($data['fPassword']);
            $res = AdminModel::insert($data);
            return $res ? success('添加成功') : error('添加失败');
        }

        return view('', ['roles' => SysRoleModel::getRoles()]);
    }

    /**
     * @desc 获取数据
     */
    public function update()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['fId', '', 'require', 'ID'],
                ['fName', '', 'require|unique:fadmin', '用户名'],
                'froleId'
            ]);
            if (post('fPassword')) {
                $data['fPassword'] = admin_md5(post('fPassword'));
            }
            $res = AdminModel::update($data);
            return $res ? success('修改成功') : error('修改失败');
        }

        $row = AdminModel::find(get('id'));
        $roles = SysRoleModel::getRoles();
        return view('', compact('row', 'roles'));
    }

    /**
     * @desc 删除
     */
    public function delete()
    {
        if (!$id = post('id')) {
            return error('id为空');
        }
        AdminModel::where(['fId' => $id])->delete();
        return success('删除成功');
    }

    /**
     * @desc 修改状态
     */
    public function handleStatus()
    {
        $post = post(['id', 'status']);
        if (!$post['id']) {
            return error('参数有误');
        }

        $res = AdminModel::where('fId', 'in', $post['id'])->update(['fStatus' => $post['status']]);
        return $res ? success('操作成功') : error('操作失败');
    }
}