<?php
namespace app\common\command\task;

use app\common\model\ContractOrderModel;
use app\common\model\ContractPorderlogModel;
use app\common\model\ContractTypeModel;
use app\common\model\StatisticContractModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class StatisticContract extends Command
{
    protected function configure()
    {
        $this->setName('statisticContract')->setDescription('合约统计');
    }

    protected function execute(Input $input, Output $output)
    {
        $data = ContractTypeModel::getContracts();

        $field = [
            'c_id',
            'count(id) as multi_p',
            'sum(position_average) as multi_p_avg_price'
        ];
        $data1 = ContractOrderModel::field($field)->where('open_type', 0)->group('c_id')->select();
        $data1 = array_column($data1, null, 'c_id');

        $field = [
            'c_id',
            'count(id) as short_p',
            'sum(position_average) as short_p_avg_price'
        ];
        $data2 = ContractOrderModel::field($field)->where('open_type', 1)->group('c_id')->select();
        $data2 = array_column($data2, null, 'c_id');

        $data3 = ContractPorderlogModel::field('sum(f_profit_loss) as gains_loss')->group('c_id')->select();
        $data3 = array_column($data3, null, 'c_id');

        $time = date('Y-m-d H:i:s');

        foreach ($data as $k => $v) {
            $insert = [
                'c_id' => $k,
                'multi_p' => empty($data1[$k]['multi_p']) ? 0: $data1[$k]['multi_p'],
                'short_p' => empty($data2[$k]['short_p']) ? 0: $data2[$k]['short_p'],
                'multi_p_avg_price' => empty($data1[$k]['multi_p_avg_price']) ? 0: $data1[$k]['multi_p_avg_price'],
                'short_p_avg_price' => empty($data2[$k]['short_p_avg_price']) ? 0: $data2[$k]['short_p_avg_price'],
                'gains_loss' => empty($data3[$k]['gains_loss']) ? 0: $data3[$k]['gains_loss'],
                'update_time' => $time,
            ];

            if (StatisticContractModel::where('c_id', $k)->find()) {
                StatisticContractModel::where('c_id', $k)->update($insert);
            } else {
                StatisticContractModel::insert($insert, true);
            }
        }
    }
}
