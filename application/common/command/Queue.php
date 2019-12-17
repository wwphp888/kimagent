<?php
namespace app\common\command;

use app\common\model\AsyncTaskModel;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use Workerman\Worker;
use Workerman\Lib\Timer as WorkerTimer;

class Queue extends Command
{
    /**
     * @var int
     */
    static $timer;

    static $task = [];

    protected function configure()
    {
        // 指令配置
        $this->setName('queue')
            ->addArgument('status', Argument::REQUIRED, 'start/stop/reload/status/connections')
            ->addOption('d', null, Option::VALUE_NONE, 'daemon（守护进程）方式启动')
            ->setDescription('start/stop/restart Queue');
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
        self::$timer = WorkerTimer::add(5, function () {
            set_time_limit(0);
            $is = AsyncTaskModel::where('status', 1)->find();
            if ($is) {
                return false;
            }
            $info = AsyncTaskModel::where('status', 0)->find();
            if (!$info) {
                return false;
            }
            AsyncTaskModel::where('id', $info['id'])->update(['status' => 1]);

            $class = ucfirst($info['class']);
            $class = "\\app\\common\\job\\{$class}";

            if (empty(self::$task[$info['class']])) {
                $class = new $class();
                self::$task[$info['class']] = $class;
            }
            $class = self::$task[$info['class']];

            $params = $info['params'] ? json_decode($info['params'], true) : [];
            try {
                call_user_func_array([$class, 'handle'], $params);
                AsyncTaskModel::where('id', $info['id'])->update(['status' => 2]);
            } catch (\Throwable $e) {
                AsyncTaskModel::where('id', $info['id'])->update(['fail_log' => $e->getMessage()]);
            }
        });
    }

    /**
     * @desc 停止定时器
     */
    public function stop()
    {
        WorkerTimer::del(self::$timer);
    }
}
