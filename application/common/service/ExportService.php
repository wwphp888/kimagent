<?php
namespace app\common\service;

class ExportService
{
    /**
     * @desc 导出csv文件
     * @param $filename
     * @param array $titleArray
     * @param array $dataArray
     * @param bool $isHttp 是否输出到浏览器
     * @param string $savepath 保存的文件名
     */
    public static function exportCsv($filename, $titleArray = [], $dataArray = [], $isHttp = true, $savepath = '')
    {
        ini_set('memory_limit', '512M');
        set_time_limit(0);
        ob_end_clean();
        ob_start();
        if ($isHttp)
        {
            header("Content-Type:text/csv;charset=utf-8");
            header("Content-Disposition:filename={$filename}");
            $fp = fopen('php://output', 'w');
        }
        else
        {
            $fp = fopen($savepath, 'w');
        }

        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));//转码 防止乱码(比如微信昵称(乱七八糟的))
        fputcsv($fp, $titleArray);

        $index = 0;
        foreach ($dataArray as $item)
        {
            if ($index == 1000)
            {
                $index = 0;
                ob_flush();
                flush();
            }
            $index++;
            fputcsv($fp, $item);
        }
        ob_flush();
        flush();
        ob_end_clean();
    }

    /**
     * @desc 读取csv文件
     * @param $csvfile
     * @param int $offset
     * @param int $limit
     * @return array|bool
     */
    public static function readCsv($csvfile, $offset = 0, $limit = 0)
    {
        if (!$fp = fopen($csvfile, 'r')) {
            return false;
        }
        $data = [];
        $i = 0;
        while ($line = fgetcsv($fp)) {
            if ($offset && $i < $offset) {
                $i++;
                continue;
            }
            if ($limit && ($i-$offset+1) > $limit) {
                break;
            }
            $i++;
            $data[] = $line;
        }
        fclose($fp);
        return $data;
    }

    /**
     * @desc 得到倒数第几行数据
     * @param $file
     * @param int $limit
     * @return array
     */
    public static function getLastLines($file, $limit = 100)
    {
        $fp = fopen($file,'r');

        $i = 0;
        while (fgets($fp)) {
            $i++;
        }
        fseek($fp,0);
        $j    = 0;
        $data = [];
        while ($line = fgets($fp)) {
            if ($j < ($i-$limit)) {
                $j++;
                continue;
            }
            $data[] = $line;
        }
        return $data;
    }
}
