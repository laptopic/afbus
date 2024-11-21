<?php

namespace Engine\Console;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Afbus\Task;
use Afbus\Worker;
use Afbus\Exception;

/**
 * Command.
 */
final class WorkerCommand extends Command
{

    public function __construct(
        public LoggerInterface $logger,
        public Worker $worker

    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setName('worker');
        $this->setDescription('App!');
    }

    public function doInterrupt()
    {
        $this->logger->debug('Interruption signal received.');
        exit;
    }

    public function doTerminate()
    {
        $this->logger->debug('Termination signal received.');
        exit;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->debug('Worker started ...');
        pcntl_signal(SIGINT, [$this, 'doInterrupt']);
        pcntl_signal(SIGTERM, [$this, 'doTerminate']);

        //each can have its own default
        $this->worker->setService(Task::SERVICE_DEVICE);

        //consumer
        try {
            $this->worker->run(true);
        } catch (Exception $e) {
            echo 'Error: '. $e->getMessage() . PHP_EOL;
        }

        return 0;

        //Queue
//        while (true) {
//            try {
//                if (null !== ($task = $this->worker->run())) {
//                    echo 'Ran task: '. $task->getName() . PHP_EOL;
//                }
//            } catch (Exception $e) {
//                echo 'Error: '. $e->getMessage() . PHP_EOL;
//            }
//        }

    }
}