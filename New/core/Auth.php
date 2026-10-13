<?php
namespace Core;
class Auth {
    public function login(array $user): void {
        Application::$app->session->set('user', $user);
    }
    public function logout(): void {
        Application::$app->session->remove('user');
    }
    public function user(): ?array {
        return Application::$app->session->get('user');
    }
    public function check(): bool {
        return $this->user() !== null;
    }
    public function role(): ?string {
        return $this->user()['role'] ?? null;
    }
}