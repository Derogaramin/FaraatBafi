<?php

namespace Core;

abstract class Middleware {
    abstract public function handle(): void;
}

class AuthMiddleware extends Middleware {
    public function handle(): void {
        if (!Application::$app->auth->check()) {
            header('Location: ' . url('/login'));
            exit;
        }
    }
}

class RoleMiddleware extends Middleware {
    private array $roles;
    public function __construct(...$roles) {
        $this->roles = $roles;
    }
    public function handle(): void {
        $userRole = Application::$app->auth->role();
        if (!in_array($userRole, $this->roles)) {
            header('Location: ' . url('/forbidden'));
            exit;
        }
    }
}