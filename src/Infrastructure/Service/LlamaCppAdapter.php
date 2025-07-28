<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

class LlamaCppAdapter
{
    private string $llamaCppUrl;
    private string $modelName;
    private array $embeddingCache = [];
    private bool $modelLoaded = false;

    public function __construct(
        private ?Client $httpClient = null,
        private ?Logger $logger = null,
        private array $config = []
    ) {
        $this->httpClient ??= new Client(['timeout' => 60]);
        $this->logger ??= new Logger('llm-adapter');
        $this->llamaCppUrl = $this->config['llm']['service_url'];
        $this->modelName = $this->config['llm']['model_name'];
        $logPath = $this->config['storage']['log_path'];
        $this->logger->pushHandler(new StreamHandler($logPath, Level::Info));
    }
}