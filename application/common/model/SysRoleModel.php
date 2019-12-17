<?php
namespace app\common\model;

class SysRoleModel extends BaseModel
{
    /**
     * @var 表
     */
    protected static $name = 'frole';

    /**
     * @var 主键
     */
    protected static $pk = 'fid';

    /**
     * @desc 得到以id键值
     * @return array
     */
    public static function getRoles()
    {
        return self::column('fname', 'fid');
    }
}