<?php

namespace Engine\Application\Tasks;

use Afbus\TaskInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;



class TestTask implements TaskInterface
{

    protected $_data;

    public function __construct(
        public LoggerInterface $logger,
        public ContainerInterface $c
    ){}

    public function run()
    {
//        $this->logger->error(date('Y-m-d H:i:s', time()) . ': ' . json_encode($this->_data));
        echo date('Y-m-d H:i:s', time()) . ': ' . json_encode($this->_data) . "\n";
        return true;
    }

    public function setData(array $data)
    {
        $this->_data = $data;
    }
}