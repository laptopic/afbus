<?php

namespace Afbus\Drivers;

/**
 * Memory
 *
 * @author laptopic
 */
class Memory implements DriversInterface
{

    /**
     *
     * @var array
     */
    private $_options = [];

    /**
     * Array for storage
     *
     * @var array
     */
    private $_storage = [];

    /**
     * Array for unique tasks
     *
     * @var array
     */
    private $_uniqueTasks = [];

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
     * @return \Afbus\Drivers\Memory
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
     * @return \Afbus\Drivers\Memory
     */
    public function addTask(\Afbus\Task $task)
    {
        $key = $this->_createKey($task);

        // Check if the task is unique and already exists
        if ($task->isUnique() && array_key_exists($key, $this->_uniqueTasks)) {
            return $this;
        }

        $this->_uniqueTasks[$key] = true;

        $this->_storage[] = serialize($task);

        return $this;
    }

    /**
     *
     * @param int $priority
     *
     * @return \Afbus\Task
     */
    public function getTask(string $service = null)
    {
        if (null !== $service) {
            foreach ($this->_storage as $k => $task) {
                $task = unserialize($task);
                if ($task->getDrivers() == $$service) {
                    unset($this->_storage[$k]);
                    return $task;
                }
            }
        } else {
            $task = array_shift($this->_storage);

            if (null !== $task) {
                $task = unserialize($task);
            }

            return $task;
        }
    }

    /**
     *
     * @param int $drivers
     *
     * @return array
     */
    public function getTasks($drivers = null)
    {
        $tasks = array();
        foreach ($this->_storage as $task) {
            $task = unserialize($task);
            if (null !== $drivers && $task->getDrivers() != $drivers) {
                continue;
            }

            $tasks[] = $task;
        }

        return $tasks;
    }


    /**
     * Clear queue
     *
     * @return boolean
     */
    public function clear()
    {
        $this->_storage = array();

        return true;
    }


    /**
     *
     * @param \Afbus\Task $task
     *
     * @return string
     */
    protected function _createKey(\Afbus\Task $task)
    {
        if ($task->isUnique()) {
            $key = sprintf('task:%s:%s', $task->getName(), $task->getUniqueId());
        } else {
            $key = sprintf('task:%s:%s', $task->getName(), uniqid('', true));
        }

        return $key;
    }
}