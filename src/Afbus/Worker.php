<?php

namespace Afbus;

use Afbus\Exception;
use Afbus\Queue;
use Afbus\Task;
use Afbus\TaskInterface;
use Psr\Container\ContainerInterface;

/**
 * Worker
 *
 * @author laptopic
 */
class Worker
{
    /**
     * Run every 5 seconds by default
     */
    const DEFAULT_INTERVAL = 1;

    /**
     * Run every X seconds
     *
     * @var int
     */
    protected int $_interval = self::DEFAULT_INTERVAL;

    /**
     * Do only tasks with this priority or all if priority is null
     *
     * @var string
     */
    protected ?string $_service;

    /**
     *
     * @var Queue
     */
    protected Queue $_queue;

    /**
     *
     * @var float
     */
    protected float $_startTime;

    /**
     *
     * @var ContainerInterface
     */
    public ContainerInterface $c;

    /**
     *
     * @param ContainerInterface $c
     *
     * @return Worker
     */
    public function setContainer(ContainerInterface $c): Worker
    {
        $this->c = $c;
        return $this;
    }

    /**
     *
     * @return int
     */
    public function getInterval(): int
    {
        return $this->_interval;
    }

    /**
     *
     * @param int $interval
     *
     * @return Worker
     */
    public function setInterval(int $interval): Worker
    {
        $this->_interval = $interval;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getService(): string
    {
        return $this->_service;
    }

    /**
     *
     * @param string $service
     *
     * @return Worker
     *
     * @throws \InvalidArgumentException
     */
    public function setService(?string $service): Worker
    {
        if ($service !== null && !is_string($service)) {
            throw new \InvalidArgumentException('Priority must be null or an string');
        }

        $this->_service = $service;

        return $this;
    }

    /**
     *
     * @return Queue
     * @throws \Afbus\Exception
     */
    public function getQueue(): Queue
    {
        if (null === $this->_queue) {
            $this->_queue = Queue::get();
        }

        return $this->_queue;
    }

    /**
     *
     * @param Queue $queue
     *
     * @return Worker
     */
    public function setQueue(Queue $queue): Worker
    {
        $this->_queue = $queue;

        return $this;
    }

    /**
     * Run the worker, get tasks of the queue, run them
     *
     * @return Task|null Task which ran, or null if no task found
     * @throws \Exception
     */
    public function run(): ?\Afbus\Task
    {
        // Start timing
        $this->_startTime();

        // Get next task with set priority (or any task if priority not set)
        if (null === ($task = $this->getQueue()->getTask($this->getService()))) {
            $this->_sleep();
            return null;
        }

        $this->_runTask($task);

        // After working, sleep
        $this->_sleep();

        return $task;
    }

    /**
     * Start timing
     */
    protected function _startTime()
    {
        $this->_startTime = microtime(true);
    }

    /**
     * Get passed time
     *
     * @return float
     */
    protected function _getPassedTime(): float
    {
        return abs(microtime(true) - $this->_startTime);
    }

    /**
     * Sleep
     *
     * @return null
     */
    protected function _sleep()
    {
        // Time ... enough
        if ($this->_getPassedTime() <= $this->_interval) {
            $remainder = ($this->_interval) - $this->_getPassedTime();
            usleep($remainder * 1000000);
        } // Task took more than the interval, don't sleep
    }

    /**
     * Get class of the task, run it's default method or method specified in
     * task data [method]
     *
     * @param Task $task
     * @throws \Afbus\Exception
     */
    protected function _runTask(Task $task): TaskInterface
    {
        $taskClassName  = $task->getClassName();
        if (!class_exists($taskClassName)) {
            throw new \InvalidArgumentException(sprintf('Task class "%s" not found', $taskClassName));
        }

        //for autoloading put the class in dependencies autowire
        $taskObject     = $this->c->get($taskClassName);
//        $taskObject($task->getData());
        $taskObject->setData($task->getData());
        $taskObject->run();

        return $taskObject;
    }

}