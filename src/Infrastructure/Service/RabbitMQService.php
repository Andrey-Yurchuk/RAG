<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\Service;

use RagSystem\Domain\Service\QueueServiceInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use Psr\Log\LoggerInterface;
use Exception;
use RuntimeException;

final class RabbitMQService implements QueueServiceInterface
{
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;
    private LoggerInterface $logger;
    private bool $isConnected = false;

    public function __construct(
        string $host,
        int $port,
        string $user,
        string $password,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->connectWithRetry($host, $port, $user, $password);
    }

    /**
     * Устанавливает соединение с RabbitMQ с retry механизмом
     */
    private function connectWithRetry(string $host, int $port, string $user, string $password): void
    {
        $maxRetries = 10;
        $retryDelay = 5; // секунды
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $this->connection = new AMQPStreamConnection($host, $port, $user, $password);
                $this->channel = $this->connection->channel();
                $this->isConnected = true;

                $this->logger->info('RabbitMQ connection established', [
                    'host' => $host,
                    'port' => $port,
                    'user' => $user,
                    'attempt' => $attempt
                ]);
                return; // Успешное подключение
                
            } catch (Exception $e) {
                $this->logger->warning("RabbitMQ connection attempt {$attempt}/{$maxRetries} failed", [
                    'error' => $e->getMessage(),
                    'host' => $host,
                    'port' => $port
                ]);
                
                if ($attempt === $maxRetries) {
                    $this->logger->error('Failed to connect to RabbitMQ after all retries', [
                        'error' => $e->getMessage(),
                        'host' => $host,
                        'port' => $port,
                        'total_attempts' => $maxRetries
                    ]);
                    throw $e;
                }
                
                $this->logger->info("Waiting {$retryDelay} seconds before retry...");
                sleep($retryDelay);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function publish(string $queueName, array $message): void
    {
        if (!$this->isConnected) {
            throw new Exception('RabbitMQ connection is not established');
        }

        try {
            $this->channel->queue_declare($queueName, false, true, false, false);

            $msg = new AMQPMessage(
                json_encode($message, JSON_THROW_ON_ERROR),
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );

            $this->channel->basic_publish($msg, '', $queueName);

            $this->logger->info('Message published to queue', [
                'queue' => $queueName,
                'message_type' => $message['type'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to publish message', [
                'queue' => $queueName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function consume(string $queueName, callable $callback): void
    {
        if (!$this->isConnected) {
            throw new RuntimeException('RabbitMQ connection is not established');
        }

        try {
            $this->channel->queue_declare($queueName, false, true, false, false);

            $this->logger->info("Starting to consume messages from queue", ['queue' => $queueName]);

            $this->channel->basic_consume(
                $queueName,
                '',
                false,
                false,
                false,
                false,
                function (AMQPMessage $msg) use ($callback, $queueName) {
                    try {
                        $body = json_decode($msg->getBody(), true, 512, JSON_THROW_ON_ERROR);

                        $this->logger->info("Received message from queue", [
                            'queue' => $queueName,
                            'message_type' => $body['type'] ?? 'unknown'
                        ]);

                        $callback($body);

                        $msg->ack();

                        $this->logger->info("Message processed successfully");
                    } catch (Exception $e) {
                        $this->logger->error("Error processing message", [
                            'error' => $e->getMessage(),
                            'queue' => $queueName
                        ]);

                        $msg->reject();
                    }
                }
            );

            while ($this->channel->is_consuming()) {
                $this->channel->wait();
            }
        } catch (Exception $e) {
            $this->logger->error('Failed to consume messages', [
                'queue' => $queueName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueStatus(string $queueName): array
    {
        if (!$this->isConnected) {
            throw new RuntimeException('RabbitMQ connection is not established');
        }

        try {
            $this->channel->queue_declare($queueName, false, true, false, false);
            $queueInfo = $this->channel->queue_declare($queueName, false, true, false, false);

            return [
                'queue_name' => $queueName,
                'message_count' => $queueInfo[1] ?? 0,
                'consumer_count' => $queueInfo[2] ?? 0
            ];
        } catch (Exception $e) {
            $this->logger->error('Failed to get queue status', [
                'queue' => $queueName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    /**
     * Закрывает соединение с RabbitMQ
     */
    public function __destruct()
    {
        if ($this->isConnected) {
            try {
                $this->channel->close();
                $this->connection->close();
                $this->logger->info('RabbitMQ connection closed');
            } catch (Exception $e) {
                $this->logger->error('Error closing RabbitMQ connection', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
