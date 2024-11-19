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
class RabbitAsyn implements DriversInterface
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


    public function setOptions(array $options): RabbitAsyn
    {
        $this->_options = $options;

        return $this;
    }

    public function addTask(Task $task): RabbitAsyn
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

    public function consume(string $queue): void
    {
        $this->declareQueue($queue);
        try {
            $this->_getRabbit()->basic_consume(
                $queue,
                consumer_tag: 'consumer_' . getmypid(),
                no_local: false,
                no_ack: false,
                exclusive: false,
                nowait: false,
                callback: fn ($message) => $this->callback($message),
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

    public function clear(): bool
    {
        return true;
    }

    public function _universalize(AMQPMessage  $message): Task{
        return unserialize($message->getBody());
    }

    private function callback(AMQPMessage $message): void
    {
        $task = $this->_universalize($message);
        $taskClassName  = $task->getClassName();
        if (!class_exists($taskClassName)) {
            throw new \InvalidArgumentException(sprintf('Task class "%s" not found', $taskClassName));
        }
        $taskObject = $this->c->get($taskClassName);

        try {
            $taskObject->setData($task->getData());
            $taskObject->run();
            $message->ack();
        } catch (Throwable $ex) {
            $this->logger->critical(sprintf('[%s] Failed to connect to RabbitMQ', __METHOD__), [
                'ex' => (string) $ex
            ]);
            throw new \Exception("Unable to connect to Rabbit server");
        }
    }

    public function getTask(string $service = null)
    {
        if($service === null){
            return null;
        }

        $queue = $this->_createQueueName($service);

        $this->consume($queue);
    }

}