<?php

namespace app\common\service;

class WorkerService
{
    /**
     * @desc 消息推送
     * @param $data
     */
    public static function push($data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $config = config('socket.text');
        $client = stream_socket_client(sprintf('tcp://%s:%s', $config['ip'], $config['port']));
        fwrite($client, $data . PHP_EOL);
        fclose($client);
    }

    /**
     * @desc 异步
     * @param $class
     * @param $params
     */
   public static function async($class, $params = [])
   {
       $data = [$class, $params];
       $data = json_encode($data);
       $config = config('socket.async');
       $client = stream_socket_client(sprintf('tcp://%s:%s', $config['ip'], $config['port']));
       fwrite($client, $data . PHP_EOL);
       fclose($client);
   }
}