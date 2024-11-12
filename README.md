example of how to include a library in composer.json
```json
{
	"repositories": [
		{
			"type": "git",
			"url": "https://github.com/laptopic/afbus"
		}
	],
	"require": {
		"laptopic/afbus": "dev-master"
	},
	"autoload": {
		"psr-4": {
			"laptopic\\afbus\\": "Afbus/"
	    }
	}
}
```

dependencies.php
```php
Afbus\Queue::class => function (ContainerInterface $c): Afbus\Queue {
    $settings = $c->get('settings')['redis'];
    $redisParams = [
        'host'  => $settings['host'],
        'port'  => $settings['port'],
        'tlsCertificateAuthorityPath' => $settings['tlsCertificateAuthorityPath'],
        'tlsClientCertificateFile' => $settings['tlsClientCertificateFile'],
        'tlsClientCertificateKeyFile' => $settings['tlsClientCertificateKeyFile'],
    ];
    $queueDrivers = new Afbus\Drivers\Redis();
    $queueDrivers->setOptions($redisParams);
    $queue = new Afbus\Queue();
    $queue->setDrivers($queueDrivers);
    return $queue;
},

Afbus\Worker::class => function (ContainerInterface $c): Afbus\Worker {
    $worker = new Afbus\Worker;
    $worker
    ->setContainer($c)
    ->setQueue($c->get(Afbus\Queue::class));

    return $worker;
},

Engine\Application\Tasks\TestTask::class => \DI\autowire(Engine\Application\Tasks\TestTask::class),
```



