<?php
namespace app\common\command\task;

use app\common\model\StatisticPorderlogModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class StatisticContractPorderlog extends Command
{
    protected function configure()
    {
        $this->setName('statisticContractPorderlog')->setDescription('持仓统计');
    }

    protected function execute(Input $input, Output $output)
    {
        $timefrom = date('Y-m-d 00:00:00',  strtotime('-1 day'));
        $timeto = date('Y-m-d 00:00:00');
        $statistic_time = date('Y-m-d',  strtotime('-1 day'));

        $data = StatisticPorderlogModel::query("select c_id,sum(p_number) as total_num,p_average*p_number as total_margin,sum(p_average) as p_avg_price from position_order_logs where add_time > '{$timefrom}' and add_time <= '{$timeto}' group by c_id");

        $data = array_column($data, null, 'c_id');

        $data1 = StatisticPorderlogModel::query("select count(distinct f_uid) as gains_users from position_order_logs where add_time > '{$timefrom}' and add_time <= '{$timeto}' and p_gains > 0 group by c_id");
        $data2 = StatisticPorderlogModel::query("select count(distinct f_uid) as loss_users from position_order_logs where add_time > '{$timefrom}' and add_time <= '{$timeto}' and p_gains < 0 group by c_id");
        $data3 = StatisticPorderlogModel::query("select count(distinct f_uid) as multi_p_users from position_order_logs where add_time > '{$timefrom}' and add_time <= '{$timeto}' and p_type = 1 group by c_id");
        $data4 = StatisticPorderlogModel::query("select count(distinct f_uid) as short_p_users from position_order_logs where add_time > '{$timefrom}' and add_time <= '{$timeto}' and p_type = 0 group by c_id");

        $data1 =  array_column($data1, null , 'c_id');
        $data2 =  array_column($data2, null , 'c_id');
        $data2 =  array_column($data2, null , 'c_id');
        $data4 =  array_column($data4, null , 'c_id');

        foreach ($data as $k => $v) {
            if (!empty($data1[$k])) {
                $data[$k] = array_merge($data[$k], $data1[$k]);
            }
            if (!empty($data2[$k])) {
                $data[$k] = array_merge($data[$k], $data2[$k]);
            }
            if (!empty($data3[$k])) {
                $data[$k] = array_merge($data[$k], $data3[$k]);
            }
            if (!empty($data4[$k])) {
                $data[$k] = array_merge($data[$k], $data4[$k]);
            }
        }


        foreach ($data as &$v) {
            if (empty($v['gains_users'])) $v['gains_users'] = 0;
            if (empty($v['loss_users'])) $v['loss_users'] = 0;
            if (empty($v['multi_p_users'])) $v['multi_p_users'] = 0;
            if (empty($v['short_p_users'])) $v['short_p_users'] = 0;

            $v['statistic_time'] = $statistic_time;
        }

        StatisticPorderlogModel::insertAll($data);
    }
}
