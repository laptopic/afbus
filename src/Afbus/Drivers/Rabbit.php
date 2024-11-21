<?php

namespace Afbus\Drivers;

use Afbus\Task;
use RuntimeException;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use PhpAmqpLib\Connection\AMQPConnectionFactory;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Container\ContainerInterface;
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

    public function getOptions(): array
    {
        return $this->_options;
    }


    public function setOptions(array $options): Rabbit
    {
        $this->_options = $options;

        return $this;
    }

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
                arguments: [],
                ticket: null,
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
            } catch (\Throwable $e) {
//            throw new \Exception("Unable to connect to Rabbit server");
                throw new RuntimeException('Failed to connect to RabbitMQ.');
            }
        }

        return $this->_channel;
    }

    public function clear(): bool
    {
        return true;
    }

    public function getTask(string $service = null, callable|null $callback)
    {
        $queue = $this->_createQueueName($service);
        $this->declareQueue($queue);

        $libCallback = function (AMQPMessage $message) use ($callback){
            $receivedMessage = unserialize($message->getBody());
            if (isset($callback) && false === call_user_func($callback, $receivedMessage)) {
                return false;
            }
            $message->ack();
            return $receivedMessage;
        };
//        var_dump($callback); die();
        if(empty($callback)){
            $message = $this->_getRabbit()->basic_get($queue);
            if (empty($message)) {
                return null;
            }
            $task = $libCallback($message);
            return $task;
        }

        try {
            $this->_getRabbit()->basic_consume(
                $queue,
                consumer_tag: 'consumer_' . getmypid(),
                no_local: false,
                no_ack: false,
                exclusive: false,
                nowait: false,
                callback: $libCallback,
            );

            $this->_getRabbit()->consume();
            while ($this->_getRabbit()->is_consuming()) {
                $this->_getRabbit()->wait();
            }
        } catch (Throwable $exception) {
            $this->logger->error(sprintf('[%s] Failed to consume message1.', __METHOD__), [
                'ex' => (string) $exception,
                'queue' => $queue,
            ]);
        }


    }



}