<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: Coins.php
 * @Date: 2019/10/22
 * @Time: 17:21
 * @Description: 虚拟币管理
 */

namespace app\admin\controller;

use app\common\model\CoinsMarketModel;
use app\common\model\CoinsModel;

class CoinsMarket extends Base
{
    public function index()
    {
        if (get('page')) {
            list ($where, $limit) = build_params([
                ['status', 'status', '='],
                ['trade_status', 'trade_status', '=']
            ]);

            $model = CoinsMarketModel::where($where);
            $data  = $model->limit($limit)->select();

            $coins = CoinsModel::getCoins();
            foreach ($data as &$v) {
                $v['buy_coin']  = !empty($coins[$v['buy_id']]) ? $coins[$v['buy_id']] : '';
                $v['sell_coin'] = !empty($coins[$v['sell_id']]) ? $coins[$v['sell_id']] : '';
            }

            return table_json($model->count(), $data);
        }
        return view();
    }

    /**
     * @desc 创建
     */
    public function create()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['buy_id', '', 'require', '交易市场'],
                ['sell_id', '', 'require|different:buy_id', '交易币种'],
                ['decimals', 8],
                'buy_fee',
                'sell_fee',
                'min_count',
                'max_count',
                'min_price',
                'max_price',
                'min_money',
                'max_money',
                'trade_time'
            ]);
            $res = CoinsMarketModel::insert($data);
            return $res ? success('创建成功') : error('创建失败');
        }
        return view('', ['coins' => CoinsModel::getCoins()]);
    }

    /**
     * @desc 修改
     */
    public function update()
    {
        if ($this->request->isPost()) {
            $data = post([
                ['id', '', 'require', 'ID'],
                ['buy_id', '', 'require', '交易市场'],
                ['sell_id', '', 'require|different:buy_id', '交易币种'],
                ['decimals', 8],
                'buy_fee',
                'sell_fee',
                'min_count',
                'max_count',
                'min_price',
                'max_price',
                'min_money',
                'max_money',
                'trade_time'
            ]);

            $res = CoinsMarketModel::update($data);
            return $res ? success('修改成功') : error('修改失败');
        }

        $info = CoinsMarketModel::find(get('id'));
        $info['coins'] = CoinsModel::getCoins();
        return view('', $info);
    }

    /**
     * @desc 状态处理
     */
    public function handleStatus()
    {
        $data = post([
            ['id', '', 'require', 'ID'],
            'status',
            'type',
        ]);

        $field = $data['type'] == 1 ? 'status' : 'trade_status';
        $res = CoinsMarketModel::where('id', 'in', $data['id'])->update([$field => $data['status']]);
        return $res ? success('操作成功') : error('操作失败');
    }
}