<?php
namespace Infrastructure\Routes;

class Router {
    private array $routes = [];

    public function post(string $path,  $action): void
    {
        $this->routes['POST'][$path] = $action;
    }

    public function get(string $path, $action): void
    {
        $this->routes['GET'][$path] = $action;
    }

    public function dispatch(): ?array
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (!isset($this->routes[$method][$uri])) {
            http_response_code(404);
            return [
                'view' => 'errors/404.php',
                'data' => []
            ];
        }

        $action = $this->routes[$method][$uri];

        if (is_array($action)) {
            [$controller, $method] = $action;
            return $controller->$method();
        }

        if (is_callable($action)) {
            return $action();
        }

        throw new \Exception("Invalid route action");
    }

}