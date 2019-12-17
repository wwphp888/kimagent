<?php

namespace app\common\service;

use app\common\model\ContractOrderModel;

class StatisticsService
{
    public function contractOrderStatistics()
    {
        if (date('H')  == 00) {
            $timeTo = date('Y-m-d 00:00:00',  '1day');
            $timeFrom =  $timeTo-86400;

            $where = [];
            $where[] = ['add_time', '>', $timeFrom];
            $where[] = ['add_time', '<=', $timeTo];

            $data = ContractOrderModel::query("select sum(e_number),sum(make_number),sum(left_number),count(distinct f_uid),sum(success_amount) from entrust_order where add_time > {$timeFrom} and add_time <= {$timeTo} group by c_type,c_state");
            dump($data);exit;
        }
    }
}