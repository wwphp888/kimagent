<?php
namespace app\common\model;

class CoinsModel extends BaseModel
{
    /**
     * @var 表
     */
    protected static $name = 'fvirtualcointype';

    /**
     * @var 主键
     */
    protected static $pk = 'fId';


    /**
     * @desc 得到以id键值
     * @return array
     */
    public static function getCoins()
    {
        return self::column('fName', 'fId');
    }
}