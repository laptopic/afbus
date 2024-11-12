<?php

namespace Engine\Console;


use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Afbus\Queue;
use Afbus\Task;

/**
 * Command.
 */
final class PublishCommand extends Command
{

    public function __construct(
        public LoggerInterface $logger,
        public Queue $queue

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