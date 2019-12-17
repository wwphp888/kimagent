<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: ChainService.php
 * @Date: 2019/10/24
 * @Time: 11:32
 * @Description: 钱包中间件
 */
namespace app\common\service;

class ContractService
{
    /**
     * @desc 获取冻结保证金
     * @param $entrustNums 委托数量
     * @param $entrustPrice 委托价格
     * @param $contractMul 合约乘数
     * @param $dopositRate 保证金率
     * @return string
     */
    public static function getFreezeDeposit($entrustNums, $entrustPrice, $contractMul, $dopositRate)
    {
        return bcmul(bcmul($entrustNums, $entrustPrice, 8), bcmul($contractMul, $dopositRate, 8), 8);
    }

    /**
     * @desc 得到持仓保证金
     * @param $pAvg 持仓均价
     * @param $pNum 持仓数量
     * @return string
     */
    public static function getPositionDeposit($pAvg, $pNum)
    {
        return bcmul($pAvg, $pNum, 8);
    }

    /**
     * @desc 得到浮动盈亏的收益率
     * @param $positionDeposit
     * @param $profitLoss
     * @return string
     */
    public static function getProfitLossRate($positionDeposit, $profitLoss)
    {
        return $positionDeposit == 0 ? 0 : bcdiv($profitLoss, $positionDeposit, 8);
    }
}