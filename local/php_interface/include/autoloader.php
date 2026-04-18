<?php

class Autoloader
{
    private $prefix;
    private $path;

    public function __construct($prefix, $path)
    {
        $this->prefix = $prefix;
        $this->path = $path;
        spl_autoload_register([$this, 'loadClass']);
    }

    public function loadClass(string $className): void
    {
        if (strpos($className, $this->prefix) !== 0) {
            return;
        }

        $relativeClass = substr($className, strlen($this->prefix));
        $filePath = $this->path . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($filePath)) {
            require_once $filePath;
        }
    }
}

// Регистрируем автозагрузчик
new Autoloader('Api', __DIR__ . '/../../modules/api/lib/');
new Autoloader('Query', __DIR__ . '/../../modules/laravel.query/lib/');
\Bitrix\Main\Loader::includeModule('main');
\Bitrix\Main\Loader::includeModule('iblock');