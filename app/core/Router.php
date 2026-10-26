<?php

/*
 * Maps ?url=<route> to Controller@method using config/routes.php
 */
class Router
{
    private array $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function dispatch(string $url): void
    {
        $route = trim($url, '/');

        if ($route === '') {
            $route = DEFAULT_ROUTE;
        }

        if (!isset($this->routes[$route])) {
            http_response_code(404);
            require APP_PATH . '/views/errors/404.php';
            return;
        }

        $GLOBALS['currentRoute'] = $route;

        [$controllerName, $method] = explode('@', $this->routes[$route]);

        require_once APP_PATH . '/controllers/' . $controllerName . '.php';

        $controller = new $controllerName();
        $controller->$method();
    }
}
