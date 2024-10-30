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
     * Add task to the queue
     *
     * @param \Afbus\Task $name
     *
     * @return DriversInterface
     */
    public function addTasks(\Afbus\Task $task);

    /**
     * Get next task from the queue
     *
     * @param string $service Return only tasks with this service
     *
     * @return \Afbus\Task|null
     */
    public function getTask($service = null);

    /**
     * Clear all tasks from queue
     *
     * @return boolean
     */
    public function clear();
}