<?php

namespace app\common\service;

class SmsService
{
    const URL = 'http://intapi.253.com/send/json';
    const ACCOUNT = 'I4546366';
    const PASSWORD = '2BdKtIr3Jl259e';

    /**
     * @desc 发送短信
     * @param int $mobile
     * @param string $msg
     * @param int $quhao
     * @return bool|string
     */
    public static function send($mobile, $msg = '', $quhao = 86)
    {
        $data = [
            'account'  => self::ACCOUNT,
            'password' => self::PASSWORD,
            'msg'      => '【BZEX】 ' . $msg,
            'mobile'   => $quhao . $mobile,
            'report'   => true
        ];
        $data = json_encode($data);

        try {
            $headers = [
                'Content-Type: application/json; charset=utf-8'
            ];

            $res = CurlService::post(self::URL, $data, $headers);

            if ($res['code'] == 0) {
                return true;
            }
            return $res['error'];
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    /**
     * @desc 发送短信
     * @param int $mobile
     * @param string $temp
     * @return bool|string
     */
    public static function sendCode($mobile, $temp)
    {
        $code = rand(100000, 1000000);
        $msg = sprintf("{$temp}", $code);

        $res = self::send($mobile, $msg);
        if (true === $res) {
            session('code', $code);
        }

        return $res;
    }
}