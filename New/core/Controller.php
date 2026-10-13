<?php
namespace Core;
abstract class Controller {
    protected function render(string $view, array $data = [], string $layout = 'main'): string {
        return View::render($view, $data, $layout);
    }
    protected function redirect(string $url): never {
        header('Location: ' . $url);
        exit;
    }
    protected function json(array $data, int $code = 200): never {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}