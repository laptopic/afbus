<?php

namespace Engine\Console;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Afbus\Drivers\Redis;
use Afbus\Queue;
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
        protected ContainerInterface $c,

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

        $settings = $this->c->get('settings')['redis'];
        $redisParams = [
            'host'  => $settings['host'],
            'port'  => $settings['port'],
            'tlsCertificateAuthorityPath' => $settings['tlsCertificateAuthorityPath'],
            'tlsClientCertificateFile' => $settings['tlsClientCertificateFile'],
            'tlsClientCertificateKeyFile' => $settings['tlsClientCertificateKeyFile'],
        ];

        $queueDrivers =     new Redis();
        $queueDrivers->setOptions($redisParams);
        $queue        =     new Queue();
        $queue->setDrivers($queueDrivers);

        $worker = new Worker;

        $worker
            ->setContainer($this->c)
            ->setQueue($queue)
            //each can have its own default
            ->setService(Task::SERVICE_DEVICE);

        while (true) {
            try {
                if (null !== ($task = $worker->run())) {
                    echo 'Ran task: '. $task->getName() . PHP_EOL;
                }
            } catch (Exception $e) {
                echo 'Error: '. $e->getMessage() . PHP_EOL;
            }
        }

    }
}