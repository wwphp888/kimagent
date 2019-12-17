<?php

namespace app\common\service;

use app\common\model\TradeMarkModel;

class PullTradeMarkService
{
    /**
     * @desc 获取指数
     */
    public static function getMark()
    {
        $date = date('Y-m-d\TH:i:s');
        $res = CurlService::get('https://api.chainext.cn/v1/index_realtime');
        $HQ = $HO = $TTS = 0;
        if ($res['code'] == 1000) {
            foreach ($res['data'] as $v) {
                if ($v['indexCode'] == 'csi11-20') {
                    $HQ = $v['lastPrice'];
                }
                if ($v['indexCode'] == 'csi10') {
                    $HO = $v['lastPrice'];
                }
                if ($v['indexCode'] == 'csi21-100') {
                    $TTS = $v['lastPrice'];
                }
            }

            $data = [
                [
                    'type' => 1,
                    'mark' => bcadd($HQ, 0, 8),
                    'create_time' => $date
                ],
                [
                    'type' => 2,
                    'mark' => bcmul($HO, 1.25, 8),
                    'create_time' => $date
                ],
                [
                    'type' => 3,
                    'mark' => bcmul($TTS, 1.5, 8),
                    'create_time' => $date
                ],
            ];
            TradeMarkModel::insertAll($data);
        }
    }

//    /**
//     * @desc 获取权重
//     */
//    public static function getWeight()
//    {
//        set_time_limit(0);
//
//        $coins = config('trade.coins');
//        $exchanges = config('trade.exchange');
//        $data = [];
//
//        foreach ($coins as $v) {
//            $data[$v] = 0;
//        }
//
//        foreach ($exchanges as $v1) {
//            foreach ($coins as $v) {
//                $res = call_user_func_array([PullTradeMarkService::class, $v1], [strtolower($v)]);
//                if ($res) {
//                    $data[$v] += $res['price'] * $res['amount'];
//                }
//            }
//        }
//
//        $total = array_sum($data);
//        $markPrice = self::getMarkPrice();
//
//        TradeWeightModel::where('id', '>', 0)->delete();
//        foreach ($data as $k => $v) {
//            TradeWeightModel::create([
//                'symbol' => $k,
//                'weight' => $v / $total,
//                'mark_price' => $markPrice[$k]
//            ]);
//        }
//
//        echo 'success';
//    }
//
//    /**
//     * @desc 获取指数
//     */
//    public static function getHQ()
//    {
//        $markPrice = self::getMarkPrice();
//        $weightInfo = TradeWeightModel::column('symbol,weight,mark_price');
//
//        $Ai = $A0 = 0;
//        foreach ($markPrice as $k => $v) {
//            $Ai += bcmul($v, $weightInfo[$k]['weight'], 8);
//        }
//
//        foreach ($weightInfo as $k => $v) {
//            $A0 += bcmul($v['weight'], $v['mark_price'], 8);
//        }
//
//        $mark = bcdiv($Ai, $A0, 8) * 2000;
//        TradeMarkModel::insert([
//            'type' => 1,
//            'mark' => $mark,
//            'create_time' => date('Y-m-d\TH:i:s')
//        ]);
//
//        echo 1;
//    }
//
//    /**
//     * @desc 获取指数
//     */
//    public static function getHO()
//    {
//        $tradeMarkModel = new TradeMarkModel();
//        $res = CurlService::get('https://api.huobi.pro/market/tickers');
//        if ($res['status'] == 'ok') {
//            foreach ($res['data'] as $v) {
//                if ($v['symbol'] == 'hb10usdt') {
//                    $mark = bcmul($v['close'], 2000, 8);
//                    $tradeMarkModel->insert([
//                        'type' => 2,
//                        'mark' => $mark,
//                        'create_time' => date('Y-m-d\TH:i:s')
//                    ]);
//                    break;
//                }
//            }
//        }
//
//        echo 2;
//    }

    /**
     * @desc 获取指数价格
     */
    public static function getMarkPrice()
    {
        $coins = config('trade.coins');

        $urls = [];
        foreach ($coins as $v) {
            $symbol = strtoupper($v) . '-USDT';
            $urls[] = sprintf('https://www.okex.com/api/index/v3/%s/constituents', $symbol);
        }

        $result = CurlService::multi($urls);
        $markPrice = [];
        foreach ($result as $v) {
            $data = json_decode($v, true);
            if ($data && $data['code'] == 0) {
                $coin = $data['data']['instrument_id'];
                $coin = explode('-', $coin)[0];
                $markPrice[$coin] = $data['data']['last'];
            }
        }
        return $markPrice;
    }

    /**
     * @desc 火币
     * @param $symbol
     * @return array|bool
     */
    static function huobi($symbol)
    {
        $symbol = $symbol . '';

        $result = CurlService::get(sprintf('https://api.huobi.pro/market/detail?symbol=%',  $symbol));
        if ($result['status'] == 'ok') {
            $data = $result['tick'];
            return [
                'price'  => $data['close'],
                'amount' => $data['amount']
            ];
        }
        return false;
    }

    /**
     * @desc 币安
     * @param $symbol
     * @return array|bool
     */
    static function binance($symbol)
    {
        $symbol = $symbol . 'usdt';
        $end = strtotime(date('Y-m-d'))-8*3600;
        $start = ($end-86400) * 1000;
        $end = $end * 1000;

        $result = CurlService::get(sprintf('https://fapi.binance.com/fapi/v1/klines?interval=1d&symbol=%s&startTime=%s&endTime=%s', $symbol, $start, $end));
        if (!empty($result[0])) {
            return [
                'price'  => $result[0][4],
                'amount' => $result[0][5]
            ];
        }
        return false;
    }

    /**
     * @desc coinbase
     * @param $symbol
     * @return array|bool
     */
    static function coinbase($symbol)
    {
        $symbol = $symbol . '-usd';

        $end = strtotime(date('Y-m-d'))-8*3600;
        $start = date('Y-m-d\TH:i:s\Z',$end-86400);
        $end = date('Y-m-d\TH:i:s\Z', $end);

        $result = CurlService::get(sprintf('https://api.pro.coinbase.com/products/%s/candles?granularity=86400&start=%s&end=%s', $symbol,  $start, $end));

        if (!empty($result[0])) {
            return [
                'price'  => $result[0][4],
                'amount' => $result[0][5]
            ];
        }
        return false;
    }

    /**
     * @desc okex
     * @param $symbol
     * @return array|bool
     */
    static function okex($symbol)
    {
        $symbol = $symbol . '-usdt';

        $end = strtotime(date('Y-m-d'))-8*3600;
        $start = date('Y-m-d\TH:i:s\Z',$end-86400);
        $end = date('Y-m-d\TH:i:s\Z', $end);


        $result = CurlService::get(sprintf('https://www.okex.com/api/spot/v3/instruments/%s/candles?granularity=86400&start=%s&end=%s', $symbol, $start, $end));
        if (!empty($result[0])) {
            return [
                'price'  => $result[0][4],
                'amount' => $result[0][5]
            ];
        }
        return false;
    }

    /**
     * @desc zb
     * @param $symbol
     * @return array|bool
     */
    static function zb($symbol)
    {
        $symbol = $symbol . 'usdt';
        $start = (strtotime(date('Y-m-d'))-8*3600-86400) * 1000;

        $result = CurlService::get(sprintf('http://api.zb.plus/data/v1/kline?market=%s&type=1day&since=%s', $symbol, $start));
        if (!empty($result[0])) {
            return [
                'price'  => $result['data'][0][4],
                'amount' => $result['data'][0][5]
            ];
        }
        return false;
    }

    /**
     * @desc bitfinex
     * @param $symbol
     * @return array|bool
     */
    static function bitfinex($symbol)
    {
        $symbol = 't' . strtoupper($symbol) . 'USD';
        $end = strtotime(date('Y-m-d'))-8*3600;
        $start = ($end-86400) * 1000;
        $end = $end * 1000;

        $result = CurlService::get(sprintf('https://api-pub.bitfinex.com/v2/candles/trade:1D:%s/hist/?start=%s&end=%s', $symbol, $start, $end));
        if (!empty($result[0])) {
            return [
                'price'  => $result[0][2],
                'amount' => $result[0][5]
            ];
        }
        return false;
    }

    /**
     * @desc bitfinex
     * @param $symbol
     * @return array|bool
     */
    static function hitbtc($symbol)
    {
        $symbol = $symbol. 'usd';
        $end = strtotime(date('Y-m-d'))-8*3600;
        $start = date('Y-m-d\TH:i:s\Z',$end-86400);
        $end = date('Y-m-d\TH:i:s\Z', $end);

        $result = CurlService::get(sprintf('https://api.hitbtc.com/api/2/public/candles/%s?period=D1&from=%s&till=%s', $symbol, $start, $end));
        if (!empty($result[0])) {
            return [
                'price'  => $result[0]['close'],
                'amount' => $result[0]['volume']
            ];
        }
        return false;
    }

    /**
     * @desc bitfinex
     * @param $symbol
     * @return array|bool
     */
    static function mxc($symbol)
    {
        $symbol = $symbol. 'usd';
        $start = (strtotime(date('Y-m-d'))-8*3600-86400) * 1000;

        $result = CurlService::get(sprintf('https://www.mxc.ceo/open/api/v1/data/kline?market=%s&interval=1d&startTime=', $symbol, $start));
        if ($result['code'] == '200') {
            return [
                'price'  => $result[0]['2'],
                'amount' => $result[0]['5']
            ];
        }
        return false;
    }
}