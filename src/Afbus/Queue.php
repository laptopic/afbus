<?php

namespace Afbus;

use Afbus\Task;

/**
 * Queue
 *
 * @author laptopic
 */
class Queue
{

    /**
     *
     * @var \Afbus\Drivers\DriversInterface
     */
    protected $_drivers;

    /**
     *
     * @var \Afbus\Queue
     */
    static protected $_instance;

    public function __construct()
    {
        self::$_instance = $this;
    }

    /**
     *
     * @return \Afbus\Drivers\DriversInterface
     */
    public function getDrivers()
    {
        return $this->_drivers;
    }

    /**
     *
     * @param \Afbus\Drivers\DriversInterface $drivers
     *
     * @return \Afbus\Queue
     */
    public function setDrivers(\Afbus\Drivers\DriversInterface $drivers)
    {
        $this->_drivers = $drivers;

        return $this;
    }

    /**
     *
     * @param \Afbus\Task $task
     *
     * @return \Afbus\Queue
     */
    public function addTask(Task $task): Queue
    {
        $this->getDrivers()->addTask($task);
        return $this;
    }

    /**
     *
     * @param \Afbus\Task $task
     *
     * @return \Afbus\Queue
     */
    public function addTasks(Task $task): Queue
    {
        $this->getDrivers()->addTasks($task);
        return $this;
    }

    /**
     *
     * @param int $priority
     *
     * @return \Afbus\Task
     */
    public function getTask($priority = null): \Afbus\Task
    {
        return $this->getDrivers()->getTask($priority);
    }

    /**
     * Clear all tasks
     *
     * @return boolean
     */
    public function clear(): bool
    {
        if ($this->getDrivers()->clear()) {
            return true;
        }

        return false;
    }

    /**
     * Create queue
     *
     * @param array $config:
     *  persistor: name of the persistor adapter
     *  options:   array with options for the persistor
     *
     * @return \Afbus\Queue
     * @throws \InvalidArgumentException
     */
    static public function factory($config = array()): Queue
    {
        if (isset($config['drivers'])) {
            $driversClass = 'Afbus\\Drivers\\'. ucfirst($config['drivers']);
            if (class_exists($driversClass)) {
                $drivers = new $driversClass;
            } elseif (class_exists($config['drivers'])) {
                $drivers = new $config['drivers'];
            }

            if (!isset($persistor) || !is_object($persistor)) {
                throw new \InvalidArgumentException(sprintf('Drivers "%s" doesn\'t exist', $config['drivers']));
            } elseif (!($persistor instanceof Drivers\DriversInterface)) {
                throw new \InvalidArgumentException(sprintf('Drivers "%s" does not implement Drivers\DriversInterface', $config['drivers']));
            }

            if (isset($config['options'])) {
                $drivers->setOptions($config['options']);
            }
        } else {
            // Default persistor
            $drivers = new \Afbus\Drivers\Memory;
        }

        $queue = new self;
        $queue->setDrivers($drivers);

        return $queue;
    }

    static public function setInstance($instance)
    {
        self::$_instance = $instance;
    }

    /**
     *
     * @return \Afbus\Queue
     */
    static public function get(): Queue
    {
        if (null === self::$_instance) {
            throw new Exception('Queue not created');
        }

        return self::$_instance;
    }
}