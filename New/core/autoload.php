<?php
spl_autoload_register(function (string $class) {
    // Core classes: core/
    $prefix = 'Core\\';
    if (strncmp($prefix, $class, strlen($prefix)) === 0) {
        $relativeClass = substr($class, strlen($prefix));
        $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
        return;
    }

    // App\Modules: app/Modules/ModuleName/...
    $modulePrefix = 'App\\Modules\\';
    if (strncmp($modulePrefix, $class, strlen($modulePrefix)) === 0) {
        $relativeClass = substr($class, strlen($modulePrefix));
        $parts = explode('\\', $relativeClass);
        $module = $parts[0] ?? '';
        $subPath = implode('/', array_slice($parts, 1));
        $file = __DIR__ . '/../app/Modules/' . $module . '/' . $subPath . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
        return;
    }

    // Other App\ classes (Controllers, Models, etc.): app/
    $appPrefix = 'App\\';
    if (strncmp($appPrefix, $class, strlen($appPrefix)) === 0) {
        $relativeClass = substr($class, strlen($appPrefix));
        $file = __DIR__ . '/../app/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});