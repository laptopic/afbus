<?php

namespace Afbus\Drivers;

use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;
use Afbus\Task;

/**
 * Beanstalk
 *
 * @author laptopic
 */
class Beanstalk implements DriversInterface
{

    const TUBE_NAME = 'afbus:';

    /**
     *
     * @var array
     */
    private $_options = array();

    /**
     *
     * @var Pheanstalk
     */
    private $_client;

    /**
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     *
     * @param array $options
     *
     * @return \Afbus\Drivers\Beanstalk
     */
    public function setOptions(array $options): Beanstalk
    {
        $this->_options = $options;

        return $this;
    }

    /**
     * Add task to queue
     *
     * @param \Afbus\Task $task
     *
     * @return \Afbus\Drivers\Beanstalk
     */
    public function addTask(Task $task): Beanstalk
    {
        $queues = (array) $task->getService();
        foreach($queues as $value){
            $task->setService($value);
            $data = serialize($task);
            $queue = $this->_createQueueName($value);
            $this->_getClient()->putInTube($queue, $data);
        }

        return $this;
    }

    /**
     *
     * @param string|null $service
     *
     * @return \Afbus\Task|null
     */
    public function getTask(string $service = null)
    {

        if($service === null){
            return null;
        }
        $queue = $this->_createQueueName($service);
        $data   = $this->_getClient()->reserveFromTube($queue, 10);

        if (empty($data)) {
            return null;
        }

        $task = unserialize($data->getData());

        if (null !== $service && $task->getService() !== $service) {
            $this->_getClient()->release($data);

            return null;
        }

        $this->_getClient()->delete($data);

        return $task;
    }

    /**
     * Create queue name, which is in fact a service filter
     *
     * @param string $service
     *
     * @return string
     */
    protected function _createQueueName($service): string
    {

        return 'queue:service_'. $service;
    }

    /**
     * Clear queue
     *
     * @return boolean
     */
    public function clear()
    {
        while ($job = $this->_getClient()->peekReady(self::TUBE_NAME)) {
            $this->_getClient()->delete($job);
        }
    }

    /**
     *
     * @return Pheanstalk
     *
     * @throws \RuntimeException
     */
    protected function _getClient()
    {
        if (null === $this->_client) {

            $host = isset($this->_options['host']) ? $this->_options['host'] : '127.0.0.1';
            $port = isset($this->_options['port']) ? $this->_options['port'] : 11300;
            $connectTimeout = isset($this->_options['connect_timeout']) ? $this->_options['connect_timeout'] : null;

            $this->_client = new Pheanstalk($host, $port, $connectTimeout);
        }

        return $this->_client;
    }


}