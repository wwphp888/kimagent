<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */

namespace app\admin\controller;

use app\common\model\SysLimitModel;

class SysLimit extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['ip', 'fIp', '=']
            ]);

            $where[] = ['fCount', '>=', 10];

            $model = SysLimitModel::where($where);
            $data  = $model->order('fCreateTime desc')->limit($limit)->select();

            foreach ($data as &$v) {
                $v['fType'] = $this->getType($v['fType']);
            }

            return table_json($model->count(), $data);
        }
        return view();
    }

    public function delete()
    {
        SysLimitModel::where('fId', 'in', post('fId'))->delete();
        return success('操作成功');
    }

    protected function getType($value)
    {

        $name = '';
		switch ($value) {
            case 0:
                $name = "谷歌验证" ;
                break;
            case 1:
                $name = "登陆密码" ;
                break;
            case 2:
                $name = "交易密码" ;
                break;
            case 3:
                $name = "短信验证" ;
                break;
            case 4:
                $name = "邮箱验证" ;
                break;
            case 5:
                $name = "图片验证码验证" ;
                break;
            case 6:
                $name = "管理员登陆" ;
                break;
            case 7:
                $name = "语音验证" ;
                break;
        }
		return $name;
    }
}