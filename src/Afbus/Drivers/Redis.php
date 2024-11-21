<?php

namespace Afbus\Drivers;

use Afbus\Task;

/**
 * Redis
 *
 * @author laptopic
 */
class Redis implements DriversInterface
{

    /**
     * Default name for Redis key (queue name)
     */
    const DEFAULT_PREFIX = 'afbus:';

    /**
     *
     * @var array
     */
    private $_options = array();

    /**
     *
     * @var \Redis
     */
    private $_redis;

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
     * @return \Afbus\Drivers\Redis
     */
    public function setOptions(array $options): Redis
    {
        $this->_options = $options;

        return $this;
    }

    /**
     * Add task to queue
     *
     * @param \Afbus\Task $task
     *
     * @return \Afbus\Drivers\Redis
     */
    public function addTask(Task $task): Redis
    {
        $queues = (array) $task->getService();

        foreach($queues as $value){
            $task->setService($value);
            $data = serialize($task);
            $queue = $this->_createQueueName($value);
            $this->_getRedis()->rpush($queue, $data);
        }

        return $this;
    }

    /**
     *
     * @param string|null $service
     *
     * @return \Afbus\Task|null
     */
    public function getTask(string $service = null, callable|null $callback = null)
    {
        if($service === null){
            return null;
        }

        $queue = $this->_createQueueName($service);

        $data   = $this->_getRedis()->blpop($queue, 10);
        if (empty($data)) {
            return null;
        }

        list(, $taskData) = $data;
        $task = unserialize($taskData);
        if (null !== $service && $task->getService() !== $service) {
            //throw?
            return null;
        }

        return $task;
    }

    /**
     * Clear queue
     *
     * @return boolean
     */
    public function clear(): bool
    {
        return $this->_getRedis()->flushDB();
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
     *
     * @return \Redis
     *
     * @throws \RuntimeException
     */
    protected function _getRedis(): \Redis
    {
        if (null === $this->_redis) {

            $host = isset($this->_options['host']) ? $this->_options['host'] : '127.0.0.1';
            $port = isset($this->_options['port']) ? $this->_options['port'] : 6379;
            $tlsCertificateAuthorityPath = $this->_options['tlsCertificateAuthorityPath'];
            $tlsClientCertificateFile = $this->_options['tlsClientCertificateFile'];
            $tlsClientCertificateKeyFile = $this->_options['tlsClientCertificateKeyFile'];

            if (extension_loaded('redis')) {
                $redis = new \Redis;

                if (false === $redis->connect("tlsv1.2://" . $host, $port, 0,null, 0, 0, [
                        'stream' => [
                            'local_cert' => $tlsClientCertificateFile,
                            'local_pk' => $tlsClientCertificateKeyFile,
                            'cafile' => $tlsCertificateAuthorityPath,
                            'verify_peer' => false,
                            'verify_peer_name' => false
                        ],
                    ])) {
                    throw new \Exception("Unable to connect to Redis server tlsv1.2://$host:$port");
                }

                $redis->select(isset($this->_options['database']) ? $this->_options['database'] : 0);
                $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);

                if (isset($this->_options['prefix'])) {
                    // Enforce trailing semicollon
                    $prefix = rtrim($this->_options['prefix'], ':') .':';
                } else {
                    $prefix = self::DEFAULT_PREFIX;
                }

                $redis->setOption(\Redis::OPT_PREFIX, $prefix);
            }else {
                throw new \RuntimeException('Redis drivers requires redis extension or predis client library.');
            }

            $this->_redis = $redis;
        }

        return $this->_redis;
    }

}