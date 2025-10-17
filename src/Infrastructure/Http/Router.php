<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\Http;

use InvalidArgumentException;
use RagSystem\Infrastructure\DependencyInjection\Container;
use Throwable;

class Router
{
    private array $routes = [];

    public function __construct(private Container $container)
    {
    }

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
     * Добавляет PATCH маршрут
     */
    public function patch(string $path, $handler): void
    {
        $this->addRoute('PATCH', $path, $handler);
    }

    /**
     * Добавляет маршрут в коллекцию
     */
    private function addRoute(string $method, string $path, $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }

    /**
     * Группирует маршруты с общим префиксом
     */
    public function group(string $prefix, callable $callback): void
    {
        $originalRoutes = $this->routes;
        $this->routes = [];

        $callback($this);

        $groupRoutes = $this->routes;
        $this->routes = $originalRoutes;

        /** @phpstan-ignore-next-line */
        foreach ($groupRoutes as $method => $routes) {
            foreach ($routes as $path => $handler) {
                $this->addRoute($method, $prefix . $path, $handler);
            }
        }
    }

    /**
     * Обрабатывает HTTP запрос и возвращает ответ
     */
    public function handle(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = parse_url($request->getUri(), PHP_URL_PATH);

        if (isset($this->routes[$method][$uri])) {
            return $this->callHandler($this->routes[$method][$uri], $request);
        }

        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            if ($params = $this->matchPattern($pattern, $uri)) {
                return $this->callHandler($handler, $request, $params);
            }
        }

        return Response::notFound();
    }

    /**
     * Проверяет соответствие URI шаблону маршрута
     */
    private function matchPattern(string $pattern, string $uri): ?array
    {
        // Convert pattern like /users/{id} to regex
        $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            array_shift($matches); // Remove full match
            return $matches;
        }

        return null;
    }

    /**
     * Вызывает обработчик маршрута
     */
    private function callHandler($handler, Request $request, array $params = []): Response
    {
        try {
            $pathParams = [];
            if (!empty($params)) {
                $pattern = $this->getPatternForHandler($handler);
                if ($pattern) {
                    preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames);
                    foreach ($paramNames[1] as $index => $paramName) {
                        if (isset($params[$index])) {
                            $pathParams[$paramName] = $params[$index];
                        }
                    }
                }
            }

            $requestWithParams = Request::withPathParams($pathParams);

            if (is_array($handler)) {
                [$class, $method] = $handler;
                $controller = $this->container->get($class);

                $args = [$requestWithParams];
                foreach ($pathParams as $param) {
                    $args[] = $param;
                }
                
                return call_user_func_array([$controller, $method], $args);
            }

            if (is_callable($handler)) {
                return $handler($requestWithParams);
            }

            throw new InvalidArgumentException('Invalid handler');
        } catch (Throwable $e) {
            return Response::internalServerError($e->getMessage());
        }
    }

    /**
     * Получает паттерн маршрута для обработчика
     */
    private function getPatternForHandler($handler): ?string
    {
        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $pattern => $routeHandler) {
                if ($routeHandler === $handler) {
                    return $pattern;
                }
            }
        }
        return null;
    }
}
