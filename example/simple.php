<?php
/*
 * php app.php
 */
$loader = require_once __DIR__ . "/../vendor/autoload.php";
$loader->add('Acme', __DIR__);

use Afbus\Queue;
use Afbus\Task;
use Afbus\Worker;

// Setup our queue with persistor of choice, preferably in
// Dependency Injection Container

$redisParams    = array(
    'host'  => '127.0.0.1',
    'port'  => 6379
);
$queueDrivers = new Afbus\Drivers\Redis();
$queueDrivers->setOptions($redisParams);

$queue          = new Queue();
$queue->setDrivers($queueDrivers);

// Create a task
$task = new Task;
$task
    ->setName('Acme/SendMail')
    ->setData(array(
        'to'        => 'someone@somewhere.com',
        'from'      => 'qutee@nowhere.tld',
        'subject'   => 'Hi!',
        'text'      => 'It\'s your faithful QuTee!'
    ))
    ->setService(Task::SERVICE_PRODUCT);

// Queue it
$queue->addTask($task);

// Or do this in one go, if you set the queue (bootstrap maybe?)
Task::create(
    'Acme/SendMail',
    array(
        'to'        => 'someone@somewhere.com',
        'from'      => 'qutee@nowhere.tld',
        'subject'   => 'Hi!',
        'text'      => 'It\'s your faithful QuTee!'
    ),
    Task::SERVICE_DEVICE
);

Task::create(
    'Engine\Application\Tasks\InvokeTask',
    array(
        'to'        => 'someone@somewhere.com',
        'from'      => 'qutee@nowhere.tld',
        'subject'   => 'Hi!',
        'text'      => 'It\'s your faithful QuTee!'
    ),
    Task::SERVICE_ALL
);

// Send worker to do it
$worker = new Worker;
$worker
    ->setQueue($queue)
    ->setService(Task::SERVICE_DEVICE);

while (true) {
    try {
        if (null !== ($task = $worker->run())) {
            echo 'Ran task: '. $task->getName() . PHP_EOL;
        }
    } catch (Exception $e) {
        echo 'Error: '. $e->getMessage() . PHP_EOL;
    }
}
