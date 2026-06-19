<?php

namespace App\Core;

class Router
{
    /** @var array<string, list<array{pattern: string, action: string, middlewares: list<string>}>> */
    protected array $routes = [];

    public function get(string $route, string $controllerAction, array $middlewares = []): void
    {
        $this->addRoute('GET', $route, $controllerAction, $middlewares);
    }

    public function post(string $route, string $controllerAction, array $middlewares = []): void
    {
        $this->addRoute('POST', $route, $controllerAction, $middlewares);
    }

    public function put(string $route, string $controllerAction, array $middlewares = []): void
    {
        $this->addRoute('PUT', $route, $controllerAction, $middlewares);
    }

    public function delete(string $route, string $controllerAction, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $route, $controllerAction, $middlewares);
    }

    public function patch(string $route, string $controllerAction, array $middlewares = []): void
    {
        $this->addRoute('PATCH', $route, $controllerAction, $middlewares);
    }

    protected function addRoute(string $method, string $route, string $controllerAction, array $middlewares = []): void
    {
        $routeRegex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_\-\.]+)', $route);
        $routeRegex = '#^' . $routeRegex . '$#';

        $this->routes[$method][] = [
            'pattern' => $routeRegex,
            'action'  => $controllerAction,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(string $url): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $url = '/' . ltrim($url, '/');

        $methodRoutes = $this->routes[$method] ?? [];

        foreach ($methodRoutes as $route) {
            if (!preg_match($route['pattern'], $url, $matches)) {
                continue;
            }

            // Execute middlewares registered for this route
            foreach ($route['middlewares'] as $middlewareClass) {
                if (class_exists($middlewareClass)) {
                    $middleware = new $middlewareClass();
                    $middleware->handle();
                } else {
                    throw new \RuntimeException("Middleware class {$middlewareClass} not found.");
                }
            }

            $params = array_values(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
            $this->executeAction($route['action'], $params);

            return;
        }

        $this->send404($url);
    }

    protected function executeAction(string $action, array $params = []): void
    {
        [$controllerName, $methodName] = explode('@', $action);
        $controllerClass = 'App\\Controllers\\' . $controllerName;

        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller class {$controllerClass} not found.");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $methodName)) {
            throw new \RuntimeException("Method {$methodName} not found in controller {$controllerClass}.");
        }

        call_user_func_array([$controller, $methodName], $params);
    }

    protected function send404(string $url): void
    {
        http_response_code(404);
        $not_found_path = ltrim($url, '/');
        require VIEW_DIR . '/errors/404.php';
        exit;
    }
}

