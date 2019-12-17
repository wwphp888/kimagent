<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: ChainService.php
 * @Date: 2019/10/24
 * @Time: 11:32
 * @Description: 钱包中间件
 */
namespace app\common\service;

class ChainService
{
    public static function conncet($account, $pwd, $ip, $port, $chain)
    {
        $chain = ucfirst(strtolower($chain));
        $class = "\\app\\common\\service\\chain\\{$chain}";
        return new $class($account, $pwd, $ip, $port);
    }
}