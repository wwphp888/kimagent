<?php
namespace app\common\model;

class ArticleTypeModel extends BaseModel
{
    /**
     * @var 表
     */
    protected static $name = 'farticletype';

    /**
     * @var 主键
     */
    protected static $pk = 'fId';

    /**
     * @desc 文章类型
     */
    public static function getArticleType()
    {
        return self::column('fName', 'fId');
    }
}