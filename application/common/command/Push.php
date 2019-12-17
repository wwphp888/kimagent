<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use Workerman\Worker;

class Push extends Command
{
    /**
     * @var array 连接对象
     */
    static $connections = [];

    protected function configure()
    {
        // 指令配置
        $this->setName('push')
            ->addArgument('status', Argument::REQUIRED, 'start/stop/reload/status/connections')
            ->addArgument('server', Argument::OPTIONAL, 'admin/chat/channel')
            ->addOption('d', null, Option::VALUE_NONE, 'daemon（守护进程）方式启动')
            ->setDescription('start/stop/restart workerman');
    }

    /**
     * @desc 执行
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        global $argv;
        $argv[1] = $input->getArgument('status') ?: 'start';

        if ($input->hasOption('d')) {
            $argv[2] = '-d';
        } else {
            unset($argv[2]);
        }

        $this->start();
    }

    /**
     * @desc 启动workerman
     */
    protected function start()
    {
        $config = config('socket.push');

        $worker = new Worker(sprintf('%s://%s:%s', $config['protocol'], $config['ip'], $config['port']));
        $worker->count = $config['count'];

        $worker->onWorkerStart = function() {
            $config = config('socket.text');
            // 开启一个内部端口，方便内部系统推送数据，Text协议格式 文本+换行符
            $innerWorker = new Worker('text://' . $config['ip'] . ':' . $config['port']);
            $innerWorker->onMessage = function ($connection, $data) {
                foreach (self::$connections as $conn) {
                    $conn->send($data);
                }
                //$connection->send('success');
            };
            $innerWorker->listen();
        };

        $worker->onConnect = function($connection) {
            self::$connections[$connection->id] = $connection;
        };

        $worker->onClose = function($connection) {
            unset(self::$connections[$connection->id]);
        };

        Worker::runAll();
    }
}
