<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description: 文章类型
 */

namespace app\admin\controller;

use app\common\model\ArticleModel;
use app\common\model\ArticleTypeModel;

class Article extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['keyword', 'fTitle|en_title', 'like'],
                ['fArticleType', 'fArticleType', '='],
                ['isTop', 'isTop', '='],
            ]);

            $model = ArticleModel::alias('a')
                ->field('a.*,b.fName as articleType')
                ->join('farticletype b', 'a.fArticleType = b.fId')
                ->where($where);

            $data  = $model->order('fCreateDate desc')->limit($limit)->select();
            return table_json($model->count(), $data);
        }
        return view('', ['articleTypes' => ArticleTypeModel::getArticleType()]);
    }

    public function create()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['fTitle', '', 'require|unique:farticle', '名称'],
                ['en_title', '', 'require', '英文名称'],
                ['fArticleType', '', 'require', '文章类型'],
                'f_url_cn',
                'f_url_en',
                'f_url_cn_mobile',
                'f_url_en_mobile',
                'jump_link',
                'jump_link_mobile',
                'isTop',
                'fContent',
                'en_content',
                ['version', 0],
                ['fCreateDate', date('Y-m-d H:i:s')],
                ['fLastModifyDate', date('Y-m-d H:i:s')]
            ]);
            $res = ArticleModel::insert($data);
            return $res ? success('添加成功') : error('添加失败');
        }

        return view('', ['articleTypes' => ArticleTypeModel::getArticleType()]);
    }

    public function update()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['fId', '', 'require', 'ID'],
                ['fTitle', '', 'require|unique:farticle', '名称'],
                ['en_title', '', 'require', '英文名称'],
                ['fArticleType', '', 'require', '文章类型'],
                'f_url_cn',
                'f_url_en',
                'f_url_cn_mobile',
                'f_url_en_mobile',
                'jump_link',
                'jump_link_mobile',
                'isTop',
                'fContent',
                'en_content',
                ['fLastModifyDate', date('Y-m-d H:i:s')]
            ]);
            $res = ArticleModel::update($data);
            return $res ? success('更新成功') : error('更新失败');
        }

        $info = ArticleModel::find(get('fId'));
        $uploadPath = config('upload.domain');
        $info['f_url_cn_s'] = $uploadPath . $info['f_url_cn'];
        $info['f_url_en_s'] = $uploadPath . $info['f_url_en'];
        $info['f_url_cn_mobile_s'] = $uploadPath . $info['f_url_cn_mobile'];
        $info['f_url_en_mobile_s'] = $uploadPath . $info['f_url_en_mobile'];
        $info['articleTypes'] = ArticleTypeModel::getArticleType();
        return view('', $info);
    }

    public function delete()
    {
        ArticleModel::where('fId', 'in', post('fId'))->delete();
        return success('删除成功');
    }
}