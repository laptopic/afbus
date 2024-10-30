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
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     *
     * @param array $options
     *
     * @return \Afbus\Drivers\Redis
     */
    public function setOptions(array $options)
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
    public function addTask(Task $task)
    {
        //по идее task один надо оправить в несколько очередей как?
        $queue = $this->_createQueueName($task->getService());

        //надо сделать поддержку нескольких очередей и путь всегда приходит массив

        // Add task to queue
        $data = serialize(serialize($task));
        $this->_getRedis()->rpush($queue, $data);

        return $this;
    }

    /**
     * Add task to queues
     *
     * @param \Afbus\Task $task
     *
     * @return \Afbus\Drivers\Redis
     */
    public function addTasks(Task $task)
    {
        // Add task to queues
        $data = serialize(serialize($task));
        //по идее task один надо оправить в несколько очередей как?
        $queues = $task->getService();
        foreach($queues as $value){
            $queue = $this->_createQueueName($value);
            $this->_getRedis()->rpush($queue, $data);
        }

        return $this;
    }

    /**
     *
     * @param string $service
     *
     * @return \Afbus\Task|null
     */
    public function getTask($service = null)
    {
        if($service === null){
            return null;
        }

        $queues = (array) $this->_createQueueName($service);

        $data   = $this->_getRedis()->blpop($queues, 10);
        if (empty($data)) {
            return null;
        }

        list(, $taskData) = $data;

        return unserialize($taskData);
    }


    /**
     * Clear queue
     *
     * @return boolean
     */
    public function clear()
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
    protected function _createQueueName($service)
    {

        return 'queue:service_'. $service;
    }

    /**
     *
     * @return \Redis
     *
     * @throws \RuntimeException
     */
    protected function _getRedis()
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