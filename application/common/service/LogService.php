<?php
/**
 * @Copyright (C), WANGWEI.
 * @Author 王维
 * @Name Log.php
 * @Date: 2018/7/9
 * @Time: 16:13
 * @Description:
 */

namespace app\common\service;

class LogService
{
    static $log_dir = '';
    static $log_dir_list = [];
    static $maxDays = 30;
    static $expireTime;
    static $orginQueen;
    static $data = [];
    static $path = 'public/applog/';

    /**
     * @Desc  创建日志文件
     * @param $file 文件名
     * @return string
     */
    public static function create($file)
    {
        $dir = app()->getRootPath() . self::$path . dirname($file);

        if (!is_dir($dir)) {
            @mkdir($dir,0777,true);
            @chmod($dir,0777);
        }

        $file = $dir . '/' . $file;
        $ext  = pathinfo($file, PATHINFO_EXTENSION);
        $file = $ext ? $file : $file . '.txt';

        if (request()->isCli()) {
            $file = str_replace('.txt', '.cli.txt', $file);
        }

        if (!file_exists($file)) {
            //等同于创建一个所有权限的文件
            file_put_contents($file, "\xEF\xBB\xBF");
            @chmod($file,0777);
        }

        return $file;
    }

    /**
     * @desc 写日志
     * @date 2018/7/9 16:26
     * @author 王维
     * @param $file
     * @param $contents
     */
    public static function write($file, $contents)
    {
        $filename = self::create($file);

        if (is_array($contents)) {
            $contents = var_export($contents, true);
        }

        $head = ' ['.date('Y-m-d H:i:s').']';
        $contents = $head . ' ' . $contents  . "\n";
        file_put_contents($filename, $contents, FILE_APPEND);
    }

    /**
     * @desc 遍历日志文件
     * @param int $days
     * @return array
     */
    public static function eachLogFile($days = 5)
    {
        $days = $days > self::$maxDays ? self::$maxDays : $days;  //最多30天
        $dir = app()->getRootPath() . self::$path;
        return [[
            'name' => 'applog',
            'is_file' => 0,
            'children' => self::read_all_dir($dir, $days)
        ]];
    }

    /**
     * @desc 遍历日志文件
     * @author 王维
     * @param $dir
     * @param $days
     * @return array
     */
    public static function read_all_dir($dir,$days)
    {
        $arr = [];
        $handle = scandir($dir);
        $expire = date('Ymd', time()-$days*86400);

        foreach ($handle as $v) {
            if (is_dir($dir . DS  . $v) && $v != "." && $v != "..") {
                if (is_numeric($v) && strlen($v) == 8 && $v < $expire) {
                    continue;
                }
                $arr[] = [
                    'name' => $v,
                    'is_file' => 0,
                    'children' => self::read_all_dir($dir . DS . $v, $days)
                ];
            } else {
                if ($v != "." && $v != "..") {                
                  	$dir = str_replace(PUBLIC_PATH, '/', $dir).DS.$v;
                    $arr[] = [
                        'name' => $v,
                        'is_file' => 1,
                        'path' => $dir,
                    ];
                }
            }
        }
        return $arr;
    }
}