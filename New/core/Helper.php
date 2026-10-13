<?php
namespace Core;

class Helper
{
    public static function url(string $path = ''): string
    {
        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }
    public static function redirect(string $path): never
    {
        header('Location: ' . self::url($path));
        exit;
    }
    public static function asset(string $path): string
    {
        return self::url('assets/' . ltrim($path, '/'));
    }
}