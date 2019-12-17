<?php
namespace app\common\command\task;

use app\common\model\CoinsModel;
use app\common\model\StatisticGainsModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class StatisticGains extends Command
{
    protected function configure()
    {
        $this->setName('statisticGains')->setDescription('平台收益统计');
    }

    protected function execute(Input $input, Output $output)
    {
        $timefrom = date('Y-m-d 00:00:00',  strtotime('-10 day'));
        $timeto = date('Y-m-d 00:00:00');
        $statistic_time = date('Y-m-d',  strtotime('-1 day'));


        $data = CoinsModel::getCoins();

        //提现手续费
        $data1 = CoinsModel::query("select fVi_fId2,sum(ffees) as withdraw_fees from fvirtualcaptualoperation where fType = 2 and fCreateTime > '{$timefrom}' and fCreateTime <= '{$timeto}' group by fVi_fId2");
        $data1 = array_column($data1, null, 'fVi_fId2');

        //交易手续费
        $data2 = CoinsModel::query("select b.coin_id,sum(a.make_fee) as market_fees from entrust_order a left join contract_type b on a.c_id = b.c_id where a.add_time > '{$timefrom}' and a.add_time <= '{$timeto}' group by b.coin_id");
        $data2 = array_column($data2, null, 'coin_id');

        //空投
        $data3 = CoinsModel::query("select b.coin_id,sum(a.amount) as airdrop from contract_airdrop a left join contract_type b on a.c_id = b.c_id where a.add_time > '{$timefrom}' and a.add_time <= '{$timeto}' group by b.coin_id");
        $data3 = array_column($data3, null, 'coin_id');

        //持仓费
        $data4 = CoinsModel::query("select b.coin_id,sum(a.p_gains) as p_fees from position_order_logs a left join contract_type b on a.c_id = b.c_id where a.add_time > '{$timefrom}' and a.add_time <= '{$timeto}' group by b.coin_id");
        $data4 = array_column($data4, null, 'coin_id');

        //交易返佣
        $data5 = [];

        //资金调整
        $data6 = CoinsModel::query("select coin_id,sum(amount) as amount_change from fasset_change_log where add_time > '{$timefrom}' and add_time <= '{$timeto}' group by coin_id");
        $data6 = array_column($data6, null, 'coin_id');

        //强平系统亏损
        $data7 = [];

        $insertAll = [];
        foreach ($data as $k => $v) {
            $insert = [
                'coin_id' => $k,
                'withdraw_fees' => empty($data1[$k]['withdraw_fees']) ? 0: $data1[$k]['withdraw_fees'],
                'market_fees' => empty($data2[$k]['market_fees']) ? 0: $data2[$k]['market_fees'],
                'airdrop' => empty($data3[$k]['airdrop']) ? 0: $data3[$k]['airdrop'],
                'p_fees' => empty($data4[$k]['p_fees']) ? 0: $data4[$k]['p_fees'],
                'market_commission' => empty($data5[$k]['market_commission']) ? 0: $data5[$k]['market_commission'],
                'amount_change' => empty($data6[$k]['amount_change']) ? 0: $data6[$k]['amount_change'],
                'close_p_loss' => empty($data7[$k]['close_p_loss']) ? 0: $data7[$k]['close_p_loss'],
                'statistic_time' => $statistic_time
            ];
            $insert['gains_or_loss'] = $insert['withdraw_fees'] + $insert['market_fees'] - $insert['market_commission'] - $insert['airdrop'] + $insert['amount_change'] + $insert['p_fees'] - $insert['close_p_loss'];
            $insertAll[] = $insert;
        }

        StatisticGainsModel::insertAll($insertAll);
    }
}
