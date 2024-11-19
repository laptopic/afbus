<?php

namespace Afbus\Drivers;

use Afbus\Task;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use PhpAmqpLib\Connection\AMQPConnectionFactory;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;
use Psr\Log\LoggerInterface;
/**
 * Rabbit
 *
 * @author laptopic
 */
class Rabbit implements DriversInterface
{

    private $_options = array();
    private $_channel;

    public function __construct(
        private LoggerInterface $logger,
        protected ContainerInterface $c,
    ) {

    }

    /**
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     *
     * @param array $options
     *
     * @return \Afbus\Drivers\Rabbit
     */
    public function setOptions(array $options): Rabbit
    {
        $this->_options = $options;

        return $this;
    }

    /**
     * Add task to queue
     *
     * @param \Afbus\Task $task
     *
     * @return \Afbus\Drivers\Redis
     */
    public function addTask(Task $task): Rabbit
    {
        $queues = (array) $task->getService();
        foreach($queues as $value){
            $task->setService($value);
            $data = serialize($task);
            $queue = $this->_createQueueName($value);
            $this->publish($data, $queue);
        }

        return $this;
    }

    /**
     *
     * @param string|null $service
     *
     * @return \Afbus\Task|null
     */
    public function getTask(string $service = null)
    {
        if($service === null){
            return null;
        }

        $queue = $this->_createQueueName($service);
        $this->declareQueue($queue);
        $message = $this->_getRabbit()->basic_get($queue);
        if (empty($message)) {
            return null;
        }

        $task = unserialize($message->getBody());

        if (null !== $service && $task->getService() !== $service) {
            return null;
        }
        $message->ack();
//        $this->_getRabbit()->basic_ack($message->getDeliveryTag());
        return $task;
    }

    /**
     * Clear queue
     *
     * @return boolean
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * Create queue name, which is in fact a service filter
     *
     * @param string $service
     *
     * @return string
     */
    protected function _createQueueName($service): string
    {
        return 'queue:service_'. $service;
    }

    public function publish(mixed $message, string $queue): void
    {
        $this->declareQueue($queue);

        try {
            $serialized = new AMQPMessage($message);
            $this->_getRabbit()->basic_publish($serialized, routing_key: $queue);
        } catch (Throwable $exception) {
            throw $exception;
        }

        $this->logger->info(sprintf('[%s] Message published successfully.', __METHOD__), [
            'queue' => $queue,
        ]);
    }

    private function declareQueue(string $queue): void
    {
        try {
            $this->_getRabbit()->queue_declare(
                $queue,
                passive: false,
                durable: true,
                exclusive: false,
                auto_delete: false,
                nowait: false,
                arguments: []
            );
        } catch (\Throwable $exception) {
            $this->logger->critical(sprintf('[%s] Failed to declare queue.', __METHOD__), [
                'ex' => (string) $exception,
                'queue' => $queue,
            ]);

            throw $exception;
        }
    }

    private function _getRabbit()
    {
        if (null === $this->_channel) {
            $host = $this->_options['host'];
            $port = (integer) $this->_options['port'];
            $username = $this->_options['username'];
            $password = $this->_options['password'];
            $vhost = $this->_options['vhost'];
            $tlsCaCert = $this->_options['tlsCaCert'];
            $tlsCert = $this->_options['tlsCert'];
            $tlsKey = $this->_options['tlsKey'];
            $tlsPhrase = $this->_options['tlsPhrase'];

            try {
                $amqpConfig = new AMQPConnectionConfig();
                $amqpConfig->setHost($host);
                $amqpConfig->setPort($port);
                $amqpConfig->setUser($username);
                $amqpConfig->setPassword($password);
                $amqpConfig->setIsSecure(true);
                $amqpConfig->setSslVerifyName(false);
                $amqpConfig->setSslVerify(true);
                $amqpConfig->setSslCaCert($tlsCaCert);
                $amqpConfig->setSslCert($tlsCert);
                $amqpConfig->setSslKey($tlsKey);
                $amqpConfig->setSslPassPhrase($tlsPhrase);
                $amqpConfig->setVhost($vhost);
                $rabbit = AMQPConnectionFactory::create($amqpConfig);
                $this->_channel = $rabbit->channel();
            } catch (\Throwable $ex) {
                $this->logger->critical(sprintf('[%s] Failed to connect to RabbitMQ', __METHOD__), [
                    'ex' => (string) $ex
                ]);
                throw new RuntimeException('Failed to connect to RabbitMQ.');
            }
        }

        return $this->_channel;
    }

}