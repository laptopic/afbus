<?php

namespace Afbus;

use Afbus\Queue;

/**
 * Task
 *
 * @author laptopic
 */
class Task
{
    /**
     * Default name of the method to run the task
     */
    const DEFAULT_METHOD_NAME   = 'run';

    /**
     * microservice gateway
     */
    const SERVICE_GATEWAY   = 'gateway';

    /**
     * microservice user
     */
    const SERVICE_USER      = 'user';

    /**
     * microservice device
     */
    const SERVICE_DEVICE    = 'device';

    /**
     * microservice product
     */
    const SERVICE_PRODUCT   = 'product';

    /**
     * microservice general
     */
    const SERVICE_ALL   = ['gateway', 'user', 'device', 'product'];

    /**
     *
     * @var string
     */
    protected string $_name;

    /**
     *
     * @var string
     */
    protected string $_methodName;

    /**
     *
     * @var array
     */
    protected array $_data;

    /**
     *
     * @var string
     */
    protected string $_service = self::SERVICE_DEVICE;

    /**
     *
     * @param string|null $name
     * @param array $data
     * @param string|array $service
     * @param string|null $methodName
     *
     * @param array $data
     */
    public function __construct($name = null, array $data = [], $service = null, $methodName = null)
    {
        if (null !== $name) {
            $this->setName($name);
        }

        if (null !== $data) {
            $this->setData($data);
        }

        if (null !== $methodName) {
            $this->setMethodName($methodName);
        }

        if (null !== $service) {
            $this->setService($service);
        }

    }

    /**
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     *
     * @param string $name
     *
     * @return Task
     */
    public function setName(string $name): Task
    {
        // Name can hold method name in it
        if (strpos($name, '::')) {
            list($name, $methodName) = explode('::', $name);
        }

        // Validate name
        if (!preg_match('/^[a-zA-Z0-9\/\\\ _-]+$/', $name)) {
            throw new \InvalidArgumentException('Name can be only alphanumerics, spaces, underscores and dashes');
        }

        if (isset($methodName)) {
            $this->setMethodName($methodName);
        }

        $this->_name = $name;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getMethodName(): string
    {
        if ($this->_methodName === null) {
            $this->_methodName = self::DEFAULT_METHOD_NAME;
        }

        return $this->_methodName;
    }

    /**
     *
     * @param string $methodName
     * @return \Afbus\Task
     *
     * @throws \InvalidArgumentException
     */
    public function setMethodName(string $methodName): Task
    {
        // validate name
        if (!preg_match('/^[a-z][a-zA-Z0-9_]+$/', $methodName)) {
            throw new \InvalidArgumentException('Method name can be only alphanumerics and underscores');
        }

        $this->_methodName = $methodName;

        return $this;
    }

    /**
     *
     * @return string
     * @throws Exception
     */
    public function getClassName(): string
    {
        if ($this->_name === null) {
            throw new Exception('Name not set, can not create class name');
        }

        if (strpos($this->_name, '\\') !== false) {
            // FQCN?
            $className = $this->_name;
        } elseif (strpos($this->_name, '/') !== false) {
            // Forward slash FQCN?
            $className = str_replace('/', '\\', $this->_name);
        } else {
            $className = str_replace(array('-','_'), ' ', strtolower($this->_name));
            $className = str_replace(' ', '', ucwords($className));
        }

        return $className;
    }

    /**
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->_data;
    }

    /**
     *
     * @param array $data
     *
     * @return Task
     */
    public function setData(array $data): Task
    {
        $this->_data = $data;

        return $this;
    }

    /**
     *
     * @return string|array
     */
    public function getService()
    {
        return $this->_service;
    }

    /**
     *
     * @param string $service
     *
     * @return Task
     */
    public function setService($service): Task
    {
        $this->_service = $service;
        return $this;
    }

    /**
     *
     * @param string $name
     * @param array $data
     * @param int $service
     * @param string $methodName
     *
     * @return Task
     */
    public static function create($name, $data = array(), $service = null, $methodName = null): Task
    {
        $queue  = Queue::get();
        $task   = new self($name, $data, $service, $methodName);
        $queue->addTask($task);

        return $task;
    }

}