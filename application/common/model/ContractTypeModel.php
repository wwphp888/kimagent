<?php
namespace app\common\model;

class ContractTypeModel extends BaseModel
{
    /**
     * @var 表
     */
    protected static $name = 'contract_type';

    /**
     * @var 主键
    */
    protected static $pk = 'c_id';

    public static function getContracts()
    {
        return self::column('c_name_cn', 'c_id');
    }
}