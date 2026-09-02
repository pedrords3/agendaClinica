<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\Middleware;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, array $middleware = []): self
    {
        return $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): self
    {
        return $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, array $handler, array $middleware): self
    {
        $this->routes[] = compact('method', 'path', 'handler', 'middleware');
        return $this;
    }

    public function dispatch(Request $request): never
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $route['path']);
            if (!preg_match('#^' . $pattern . '/?$#', $request->path, $matches)) {
                continue;
            }
            $request->params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            Middleware::handle($route['middleware'], $request);
            [$class, $method] = $route['handler'];
            $result = (new $class())->{$method}($request);
            if (is_string($result)) {
                echo $result;
            }
            exit;
        }
        Response::abort(404);
    }
}

