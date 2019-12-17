<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use Workerman\Worker;
use Workerman\Lib\Timer as WorkerTimer;

class Timer extends Command
{
    /**
     * @var int
     */
    static $timer = [];

    protected function configure()
    {
        // 指令配置
        $this->setName('timer')
            ->addArgument('status', Argument::REQUIRED, 'start/stop/reload/status/connections')
            ->addOption('d', null, Option::VALUE_NONE, 'daemon（守护进程）方式启动')
            ->setDescription('start/stop/restart 定时任务');
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

        $task = new Worker();
        $task->count = 1;

        $task->onWorkerStart = [$this, 'start'];
        $task->runAll();
    }

    /**
     * @desc 启动服务
     */
    public function start()
    {
        $task = config('crontab.');
        foreach ($task as $v) {
            self::$timer[] = WorkerTimer::add($v['interval'], function () use($v) {
                try {
                    call_user_func_array($v['task'], []);
                } catch (\Throwable $e) {
                    echo $e->getMessage();
                }
            });
        }
    }

    /**
     * @desc 停止定时器
     */
    public function stop()
    {
        foreach (self::$timer as $v) {
            WorkerTimer::del($v);
        }
    }
}
