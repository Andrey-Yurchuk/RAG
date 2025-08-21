<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\Http;

use RagSystem\Infrastructure\DependencyInjection\Container;

class Router
{
    private array $routes = [];

    public function __construct(private Container $container) {}

    /**
     * Добавляет GET маршрут
     */
    public function get(string $path, $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Добавляет POST маршрут
     */
    public function post(string $path, $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Добавляет PUT маршрут
     */
    public function put(string $path, $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Добавляет DELETE маршрут
     */
    public function delete(string $path, $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Добавляет маршрут в коллекцию
     */
    private function addRoute(string $method, string $path, $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }
}