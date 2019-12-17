<?php
namespace app\common\command\task;

use app\common\model\ContractTypeModel;
use app\common\model\ContractOrderModel;
use app\common\model\StatisticEntrushOrderModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class StatisticContractOrder extends Command
{
    protected function configure()
    {
        $this->setName('statisticContractOrder')->setDescription('委托订单统计');
    }

    protected function execute(Input $input, Output $output)
    {
        $timefrom = date('Y-m-d 00:00:00',  strtotime('-1 day'));
        $timeto = date('Y-m-d 00:00:00');
        $statistic_time = date('Y-m-d',  strtotime('-1 day'));

        $where = [];
        $where[] = ['add_time', '>', $timefrom];
        $where[] = ['add_time', '<=', $timeto];

        $data = ContractTypeModel::getContracts();

        //当日新增委托买入数量
        $field = [
            'c_id',
            'sum(make_number) as total_buy_e_num',
        ];
        $data1 = ContractOrderModel::field($field)->where([
            ['c_type', '=', 0],
            ['add_time', '>', $timefrom],
            ['add_time', '<=', $timeto],
        ])->group('c_id')->select();
        $data1 = array_column($data1, null, 'c_id');

        //当日新增委托买入数量
        $field = [
            'c_id',
            'sum(make_number) as total_sell_e_num',
        ];
        $data2 = ContractOrderModel::field($field)->where([
            ['c_type', '=', 1],
            ['add_time', '>', $timefrom],
            ['add_time', '<=', $timeto],
        ])->group('c_id')->select();
        $data2 = array_column($data2, null, 'c_id');

        //当日已成交买入委托量
        $field = [
            'c_id',
            'sum(make_number) as total_buy_make_num',
        ];
        $data3 = ContractOrderModel::field($field)->where([
            ['c_type', '=', 0],
            ['c_state', 'in', '2,3'],
            ['add_time', '>', $timefrom],
            ['add_time', '<=', $timeto],
        ])->group('c_id')->select();
        $data3 = array_column($data3, null, 'c_id');

        //当日已未成交买入委托量
        $field = [
            'c_id',
            'sum(left_number) as total_buy_not_make_num',
        ];
        $data4 = ContractOrderModel::field($field)->where([
            ['c_type', '=', 0],
            ['add_time', '>', $timefrom],
            ['add_time', '<=', $timeto],
        ])->group('c_id')->select();
        $data4 = array_column($data4, null, 'c_id');

        //当日已成交卖出委托量
        $field = [
            'c_id',
            'sum(make_number) as total_sell_make_num',
        ];
        $data5 = ContractOrderModel::field($field)->where([
            ['c_type', '=', 0],
            ['c_state', 'in', '2,3'],
            ['add_time', '>', $timefrom],
            ['add_time', '<=', $timeto],
        ])->group('c_id')->select();
        $data5 = array_column($data5, null, 'c_id');

        //当日未成交卖出委托量
        $field = [
            'c_id',
            'sum(left_number) as total_sell_not_make_num',
        ];
        $data6 = ContractOrderModel::field($field)->where([
            ['c_type', '=', 1],
            ['add_time', '>', $timefrom],
            ['add_time', '<=', $timeto],
        ])->group('c_id')->select();
        $data6 = array_column($data6, null, 'c_id');

        //当日买入的成交额
        $field = [
            'c_id',
            'sum(success_amount) as total_buy_make_amount',
        ];
        $data7 = ContractOrderModel::field($field)->where([
            ['c_type', '=', 0],
            ['add_time', '>', $timefrom],
            ['add_time', '<=', $timeto],
        ])->group('c_id')->select();

        //当日未成交卖出委托量
        $field = [
            'c_id',
            'sum(success_amount) as total_sell_make_amount',
        ];
        $data8 = ContractOrderModel::field($field)->where([
            ['c_type', '=', 1],
            ['add_time', '>', $timefrom],
            ['add_time', '<=', $timeto],
        ])->group('c_id')->select();
        $data8 = array_column($data8, null, 'c_id');

        //当日委托用户数
        $field = [
            'c_id',
            'count(DISTINCT f_uid) as total_user',
        ];
        $data9 = ContractOrderModel::field($field)->where([
            ['add_time', '>', $timefrom],
            ['add_time', '<=', $timeto],
        ])->group('c_id')->select();
        $data9 = array_column($data9, null, 'c_id');


        $insertAll = [];
        foreach ($data as $k => $v) {
            $insert = [
                'c_id' => $k,
                'total_buy_e_num' => empty($data1[$k]['total_amount']) ? 0: $data1[$k]['total_amount'],
                'total_sell_e_num' => empty($data2[$k]['total_e_num']) ? 0: $data2[$k]['total_e_num'],
                'total_buy_make_num' => empty($data3[$k]['total_buy_make_num']) ? 0: $data3[$k]['total_buy_make_num'],
                'total_buy_not_make_num' => empty($data4[$k]['total_buy_not_make_num']) ? 0: $data4[$k]['total_buy_not_make_num'],
                'total_sell_make_num' => empty($data5[$k]['total_sell_make_num']) ? 0: $data5[$k]['total_sell_make_num'],
                'total_sell_not_make_num' => empty($data6[$k]['total_sell_not_make_num']) ? 0: $data6[$k]['total_sell_not_make_num'],
                'total_buy_make_amount' => empty($data7[$k]['total_buy_make_amount']) ? 0: $data7[$k]['total_buy_make_amount'],
                'total_sell_make_amount' => empty($data8[$k]['total_sell_make_amount']) ? 0: $data8[$k]['total_sell_make_amount'],
                'total_user' => empty($data9[$k]['total_user']) ? 0: $data9[$k]['total_user'],
                'statistic_time' => $statistic_time
            ];

            $insert['total_e_num'] = $insert['total_buy_e_num'] + $insert['total_sell_e_num'];
            $insertAll[] = $insert;
        }


        StatisticEntrushOrderModel::insertAll($insertAll);
    }
}
