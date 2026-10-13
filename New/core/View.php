<?php
namespace Core;
class View {
    public static function render(string $view, array $data = [], string $layout = 'main'): string {
        extract($data);
        ob_start();
        $viewFile = VIEWS_PATH . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewFile)) {
            throw new \Exception("View $view not found");
        }
        include $viewFile;
        $content = ob_get_clean();
        if ($layout) {
            $layoutFile = VIEWS_PATH . "layouts/{$layout}.php";
            if (file_exists($layoutFile)) {
                ob_start();
                include $layoutFile;
                return ob_get_clean();
            }
        }
        return $content;
    }
}