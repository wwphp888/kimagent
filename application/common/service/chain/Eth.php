<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: ChainService.php
 * @Date: 2019/10/24
 * @Time: 11:32
 * @Description: 钱包中间件
 */
namespace app\common\service\chain;

use app\common\service\CurlService;

class Eth
{
    protected $url;

    public function __construct($account, $pwd, $ip, $port)
    {
        $this->url = sprintf('http://%s:%s@%s:%s', $account, $pwd, $ip, $port);
    }

    /**
     * @desc 获取余额
     */
    public function getbalance()
    {
        $data = [
            'method' => 'getbalance',
            'params' => [],
            'id' => 1,
        ];

        return CurlService::post($this->url, json_encode($data));
    }

    /**
     * @desc 解锁钱包
     */
    public function walletpassphrase($password, $time = 30)
    {
        $data = [
            'method' => 'walletpassphrase',
            'params' => [$password, $time],
            'id' => 1,
        ];

        return CurlService::post($this->url, json_encode($data));
    }

    /**
     * @desc 锁钱包
     */
    public function walletlock()
    {
        $data = [
            'method' => 'walletlock',
            'params' => [],
            'id' => 1,
        ];

        return CurlService::post($this->url, json_encode($data));
    }

    /**
     * @desc 发送交易
     * @param $address
     * @param $amount
     * @return mixed
     */
    public function sendtoaddress($address, $amount)
    {
        $amount = (float)$amount;
        $data = [
            'method' => 'sendtoaddress',
            'params' => [$address, $amount, ''],
            'id' => 1,
        ];

        return CurlService::post($this->url, json_encode($data));
    }
}