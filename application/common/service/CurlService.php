<?php

namespace app\common\service;

class CurlService
{
    public static function get($url, $header = [])
    {
        return self::request($url, 'GET', $header);
    }

    public static function post($url, $data = [], $header = [])
    {
        return self::request($url, 'POST', $data, $header);
    }

    public static function request($url, $method, $data = [], $header = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);//设置超时时间

        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if (strpos($url, 'https') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        if (env('proxyhost')) {
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 0);
            curl_setopt($ch, CURLOPT_PROXY, env('proxyhost'));
            curl_setopt($ch, CURLOPT_PROXYTYPE, 7);
        }

        $output = curl_exec($ch);

        if (!$output) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);
        if (null === json_decode($output, true)) {
            throw new \Exception('not json data');
        }
        $output = json_decode($output, true);
        return $output;
    }

    /**
     * @desc 多线程curl
     * @param $urls
     * @return array
     */
    public static function multi($urls)
    {
        $mh = curl_multi_init();//创建多个curl语柄
        $handle = [];
        foreach ($urls as $k => $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);//设置超时时间
            curl_setopt($ch, CURLOPT_HEADER, 0);//这里不要header，加块效率
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            if (env('proxyhost')) {
                curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 0);
                curl_setopt($ch, CURLOPT_PROXY, env('proxyhost'));
                curl_setopt($ch, CURLOPT_PROXYTYPE, 7);
            }
            if (strpos($url, 'https') === 0) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            }
            curl_multi_add_handle($mh, $ch);
            $handle[] = $ch;
        }

        // 执行批处理句柄
        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);//当无数据，active=true
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);//当正在接受数据时
        while ($active && $mrc == CURLM_OK) {//当无数据时或请求暂停时，active=true
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

        $res = [];
        foreach ($handle as $v) {
            $res[] = curl_multi_getcontent($v);//获得返回信息
            curl_multi_remove_handle($mh, $v);//释放资源
        }

        curl_multi_close($mh);
        return $res;
    }
}