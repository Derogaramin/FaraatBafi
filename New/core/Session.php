<?php
namespace Core;
class Session {
    public function set(string $key, $value): void {
        $_SESSION[$key] = $value;
    }
    public function get(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    public function remove(string $key): void {
        unset($_SESSION[$key]);
    }
    public function setFlash(string $key, string $message): void {
        $_SESSION['_flash'][$key] = $message;
    }
    public function getFlash(string $key): ?string {
        $flash = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $flash;
    }
}