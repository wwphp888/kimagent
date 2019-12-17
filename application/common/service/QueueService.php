<?php

namespace app\common\service;

use app\common\model\AsyncTaskModel;

class QueueService
{
    /**
     * @desc 异步
     * @param $class
     * @param $params
     * @return int
     */
    public static function push($class, $params = [])
    {
        $params = $params ? json_encode($params) : '';
        return AsyncTaskModel::insert([
            'class' => $class,
            'params' => $params,
        ]);
    }
}