<?php
namespace Core;
class Router {
    protected array $routes = [];
    public function get(string $path, array $handler, array $middleware = []): void {
        $this->addRoute('GET', $path, $handler, $middleware);
    }
    public function post(string $path, array $handler, array $middleware = []): void {
        $this->addRoute('POST', $path, $handler, $middleware);
    }
    protected function addRoute(string $method, string $path, array $handler, array $middleware): void {
        $this->routes[] = compact('method', 'path', 'handler', 'middleware');
    }
    public function resolve() {
        $method = $_SERVER['REQUEST_METHOD'];
        $url = '/' . trim(parse_url($_GET['url'] ?? '', PHP_URL_PATH), '/');
        foreach ($this->routes as $route) {
            $pattern = $this->compilePattern($route['path']);
            if ($route['method'] === $method && preg_match($pattern, $url, $matches)) {
                array_shift($matches);
                // Middleware
                foreach ($route['middleware'] as $mw) {
                    (new $mw())->handle();
                }
                [$controllerClass, $action] = $route['handler'];
                $controller = new $controllerClass();
                return call_user_func_array([$controller, $action], $matches);
            }
        }
        http_response_code(404);
        return View::render('errors/404');
    }
    protected function compilePattern(string $path): string {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
}