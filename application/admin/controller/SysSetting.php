<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */

namespace app\admin\controller;

use app\common\model\SysSettingModel;
use app\common\service\UploadService;

class SysSetting extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'fKey', 'like']
            ]);

            $model = SysSettingModel::where($where);
            $data  = $model->limit($limit)->select();

            return table_json($model->count(), $data);
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
                ['fKey', '', 'require|unique:fsystemargs', 'KEY'],
                ['fValue', '', 'require', '参数值'],
                'fDescription',
                'furl',
                ['version', 0]
            ]);
            $res = SysSettingModel::insert($data);
            return $res ? success('添加成功') : error('添加失败');
        }

        return view();
    }

    /**
     * @desc 获取数据
     */
    public function update()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['fId', '', 'require', 'ID'],
                ['fKey', '', 'require|unique:fsystemargs', 'KEY'],
                ['fValue', '', 'require', '参数值'],
                'fDescription',
                'furl',
                ['version', 0]
            ]);
            $res = SysSettingModel::update($data);
            return $res ? success('修改成功') : error('修改失败');
        }

        return view('', SysSettingModel::find(get('fId')));
    }
}