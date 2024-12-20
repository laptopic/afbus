<?php

namespace Afbus\Drivers;

/**
 * Drivers interface
 *
 * @author laptopic
 */
interface DriversInterface
{

    /**
     * Set options
     *
     * @param array $options
     *
     * @return DriversInterface
     */
    public function setOptions(array $options);

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions();

    /**
     * Add task to the queue
     *
     * @param \Afbus\Task $name
     *
     * @return DriversInterface
     */
    public function addTask(\Afbus\Task $task);

    /**
     * Get next task from the queue
     *
     * @param string|null $service Return only tasks with this service
     * @param callable|null $callback
     * @return \Afbus\Task|null
     */
    public function getTask(string $service = null, callable $callback);

    /**
     * Clear all tasks from queue
     *
     * @return boolean
     */
    public function clear();
}