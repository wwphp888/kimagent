<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description: 文章类型
 */

namespace app\admin\controller;

use app\common\model\ArticleTypeModel;

class ArticleType extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'fName|fKeywords|fDescription', 'like']
            ]);

            $model = ArticleTypeModel::where($where);
            $data  = $model->order('fLastCreateDate desc')->limit($limit)->select();
            return table_json($model->count(), $data);
        }
        return view();
    }

    public function create()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['fName', '', 'require|unique:farticletype', '名称'],
                'fKeywords',
                'fDescription',
                ['version', 0],
                ['fLastCreateDate', date('Y-m-d H:i:s')]
            ]);
            $res = ArticleTypeModel::insert($data);
            return $res ? success('添加成功') : error('添加失败');
        }

        return view();
    }

    public function update()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['fId', '', 'require', 'ID'],
                ['fName', '', 'require|unique:farticletype', '名称'],
                'fKeywords',
                'fDescription',
                ['fLastModifyDate', date('Y-m-d H:i:s')]
            ]);
            $res = ArticleTypeModel::update($data);
            return $res ? success('修改成功') : error('修改失败');
        }

        return view('', ArticleTypeModel::find(get('fId')));
    }

    public function delete()
    {
        ArticleTypeModel::where('fId', 'in', post('fId'))->delete();
        return success('删除成功');
    }
}