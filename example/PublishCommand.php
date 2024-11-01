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

/**
 * Command.
 */
final class PublishCommand extends Command
{

    public function __construct(
        public LoggerInterface $logger,
        protected ContainerInterface $c,

    )
    {
        parent::__construct();
    }




    /**
     * Configure.
     *
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setName('publish');
        $this->setDescription('App!');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $settings = $this->c->get('settings')['redis'];
        $redisParams = [
            'host'  => $settings['host'],
            'port'  => $settings['port'],
            'tlsCertificateAuthorityPath' => $settings['tlsCertificateAuthorityPath'],
            'tlsClientCertificateFile' => $settings['tlsClientCertificateFile'],
            'tlsClientCertificateKeyFile' => $settings['tlsClientCertificateKeyFile'],
        ];

        $queueDrivers = new Redis();
        $queueDrivers->setOptions($redisParams);
        $queue          = new Queue();
        $queue->setDrivers($queueDrivers);

        Task::create(
            'Engine\Application\Tasks\TestTask',
            array(
                'to'        => 'someone@somewhere.com',
                'from'      => 'qutee@nowhere.tld',
                'subject'   => 'Hi!',
                'text'      => 'It\'s your faithful QuTee!'
            ),
            Task::SERVICE_DEVICE
        );

        return 0;
    }

}