<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use Workerman\Worker;
use Workerman\Lib\Timer as WorkerTimer;

class Env extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('env')
            ->addArgument('env', Argument::REQUIRED, 'local/test/prod')
            ->addArgument('debug', Argument::OPTIONAL, '1/0')
            ->setDescription('初始化env文件');
    }

    protected function execute(Input $input, Output $output)
    {
        $env = $input->getArgument('env');
        $debug = $input->getArgument('debug'); //仅用于正式环境
        $str = '';

        if ($env == 'dev') {
            $str .= "app_debug = true\n";
            $str .= "free_code = true\n\n";
            $str .= "[database]\n";
            $str .= "type = mysql\n";
            $str .= "hostname = 192.168.0.110\n";
            $str .= "database = trade\n";
            $str .= "username = root\n";
            $str .= "password = kim123456\n";
            $str .= "debug = true\n";
        }

        if ($env == 'test') {
            $str .= "app_debug = true\n";
            $str .= "free_code = true\n\n";
            $str .= "[database]\n";
            $str .= "type = mysql\n";
            $str .= "hostname = 127.0.0.1\n";
            $str .= "database = trade\n";
            $str .= "username = root\n";
            $str .= "password = kim123456\n";
            $str .= "debug = true\n";
        }

        if ($env == 'test1') {
            $str .= "app_debug = true\n";
            $str .= "free_code = true\n\n";
            $str .= "[database]\n";
            $str .= "type = mysql\n";
            $str .= "hostname = 161.117.197.104\n";
            $str .= "database = bzexdb\n";
            $str .= "username = bzex\n";
            $str .= "password = 'bzex&cc20190808mysql'\n";
            $str .= "hostport = 7306\n";
            $str .= "debug = true\n";
        }

        if ($env == 'peprod') {
            $str .= "app_debug = true\n";
            $str .= "free_code = true\n\n";
            $str .= "[database]\n";
            $str .= "type = mysql\n";
            $str .= "hostname = 161.117.197.104\n";
            $str .= "database = kimexbz\n";
            $str .= "username = kimex\n";
            $str .= "password = 'kimex&cc20190808mysql'\n";
            $str .= "hostport = 7306\n";
            $str .= "debug = true\n";
        }

        if ($env == 'prod') {
            $str = "app_debug = false";
            if ($debug == 1) {
                $str = "app_debug = true";
            }
        }

        file_put_contents('.env', $str);
    }
}
