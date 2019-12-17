<?php
/**
 * @Copyright (C), WANGWEI.
 * @Name: index.php
 * @Date: 2019/10/8
 * @Time: 13:50
 * @Description:
 */

namespace app\admin\controller;

use app\common\service\ExportService;
use think\Db;

class SysHelper extends Base
{
    public function index()
    {
        return view();
    }

    public function exportData()
    {
        $sql = get('sql');
        if (preg_match('/\[delete|truncate|update|insert|grant|replace|\]/i', $sql, $match)) {
            echo '非法参数';exit;
        }
        try {
            $data = Db::query($sql);
            if (!is_array($data)) {
                echo '数据有误';exit;
            }
            $title = [];
            foreach ($data[0] as $k => $v) {
                $title[] = $k;
            }
            ExportService::exportCsv('databases.csv', $title, $data);
        } catch (\Throwable $e) {
            echo 'sql有误';
        }
    }
}