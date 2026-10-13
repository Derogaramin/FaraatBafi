<?php
namespace Core;
class Application {
    public Router $router;
    public static Application $app;
    public Database $db;
    public Session $session;
    public Auth $auth;
    public function __construct() {
        self::$app = $this;
        $this->loadConfig();
        $this->db = new Database();
        $this->session = new Session();
        $this->auth = new Auth();
        $this->router = new Router();
        $this->loadRoutes();
    }
    private function loadConfig(): void {
        define('BASE_URL', $_ENV['BASE_URL'] ?? 'http://localhost:8000/');
        define('VIEWS_PATH', __DIR__ . '/../app/Views/');
    }
    private function loadRoutes(): void {
        require __DIR__ . '/../app/routes.php';
    }
    public function run(): void {
        echo $this->router->resolve();
    }
}