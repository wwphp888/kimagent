<?php
namespace app\common\command\task;

use app\common\model\ContractOrderModel;
use app\common\model\ContractTypeModel;
use app\common\model\StatisticEntrushOrderlogModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class StatisticContractOrderlog extends Command
{
    protected function configure()
    {
        $this->setName('statisticContractOrderlog')->setDescription('成交订单统计');
    }

    protected function execute(Input $input, Output $output)
    {
        $timefrom = date('Y-m-d 00:00:00',  strtotime('-10 day'));
        $timeto = date('Y-m-d 00:00:00', strtotime('1 day'));
        $statistic_time = date('Y-m-d',  strtotime('-1 day'));

        $data = ContractTypeModel::getContracts();

        $field = [
            'c_id',
            'count(DISTINCT f_uid) as total_user',
            'sum(make_price) as total_amount',
            'sum(e_number) as total_e_num',
            'sum(make_mean_price) as total_avg_amount',
            'sum(make_fee) as total_fees',
        ];
        $data1 = ContractOrderModel::field($field)->where([
            ['c_state', 'in', '2,3'],
            ['add_time', '>', $timefrom],
            ['add_time', '<=', $timeto],
        ])->group('c_id')->select();
        $data1 = array_column($data1, null, 'c_id');


        $data2 = ContractOrderModel::field('sum(make_number) as total_close_num')->where([
            ['delegate_type', '=', '2'],
            ['add_time', '>', $timefrom],
            ['add_time', '<=', $timeto],
        ])->group('c_id')->select();
        $data2 = array_column($data2, null, 'c_id');

        $insertAll = [];
        foreach ($data as $k => $v) {
            $insertAll[] = [
                'c_id' => $k,
                'total_user' => empty($data1[$k]['total_user']) ? 0: $data1[$k]['total_user'],
                'total_amount' => empty($data1[$k]['total_amount']) ? 0: $data1[$k]['total_amount'],
                'total_e_num' => empty($data1[$k]['total_e_num']) ? 0: $data1[$k]['total_e_num'],
                'total_close_num' => empty($data2[$k]['total_close_num']) ? 0: $data2[$k]['total_close_num'],
                'total_avg_amount' => empty($data1[$k]['total_avg_amount']) ? 0: $data1[$k]['total_avg_amount'],
                'total_fees' => empty($data1[$k]['total_fees']) ? 0: $data1[$k]['total_fees'],
                'statistic_time' => $statistic_time
            ];
        }

        StatisticEntrushOrderlogModel::insertAll($insertAll);
    }
}
