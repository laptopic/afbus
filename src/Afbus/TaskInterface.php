<?php

namespace Afbus;

/**
 * Task Interface
 *
 * @author laptopic
 */
interface TaskInterface
{
    /**
     * Set data needed for the task to run
     *
     * @param array $data
     */
    public function setData(array $data);

    /**
     * Run the task
     */
    public function run();
}