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
     * @param int $priority
     *
     * @return \Afbus\Task
     */
    public function getTask($priority = null): Task
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