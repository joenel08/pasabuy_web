<?php
namespace Admin\Core;

class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'layout'): void
    {
        $viewsDir = __DIR__ . '/../views/';
        extract($data, EXTR_SKIP);

        ob_start();
        require $viewsDir . str_replace('.', '/', $view) . '.php';
        $content = ob_get_clean();

        if ($layout) {
            require $viewsDir . $layout . '.php';
        } else {
            echo $content;
        }
    }

    public static function e(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
}