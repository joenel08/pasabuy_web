<?php

namespace Admin\Core;

use App\Core\Request;

class Router
{
    private array $routes = [];

    public function get(string $path, array $h): void
    {
        $this->add('GET', $path, $h);
    }
    public function post(string $path, array $h): void
    {
        $this->add('POST', $path, $h);
    }
    public function put(string $path, array $h): void
    {
        $this->add('PUT', $path, $h);
    }
    public function delete(string $path, array $h): void
    {
        $this->add('DELETE', $path, $h);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[$method][] = [
            'pattern' => '#^' . $pattern . '/?$#',
            'handler' => $handler,
        ];
    }
    public function dispatch(Request $request): void
    {
        // --- ADD THIS BLOCK ---
        // Strip the subdirectory prefix (e.g. "/pasabuy") from the path
        $basePath = '/pasabuy'; // change if you deploy elsewhere
        $path = $request->path;

        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        if ($path === '' || $path === false) {
            $path = '/';
        }
        // ----------------------

        $routes = $this->routes[$request->method] ?? [];
        foreach ($routes as $route) {
            if (preg_match($route['pattern'], $path, $m)) {   // <-- use $path
                $params = array_filter($m, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
                [$class, $action] = $route['handler'];
                (new $class())->$action($request, $params);
                return;
            }
        }

        http_response_code(404);
        echo '<h1 style="font-family:sans-serif;padding:40px">404 — Page not found</h1>';
    }
}
