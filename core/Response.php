<?php
declare(strict_types=1);

class Response
{
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    public static function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $_POST;
    }

    public static function render(string $viewFile, array $vars = [], ?string $layout = null): void
    {
        $vars['saas'] = $vars['saas'] ?? Tenant::saasSettings();
        $vars['csrfToken'] = Security::csrfToken();
        extract($vars, EXTR_SKIP);

        $viewPath = APP_ROOT . '/views/' . ltrim($viewFile, '/');
        if (!str_ends_with($viewPath, '.php')) {
            $viewPath .= '.php';
        }

        if ($layout === null) {
            require $viewPath;
            return;
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        $layoutPath = APP_ROOT . '/views/layouts/' . ltrim($layout, '/');
        if (!str_ends_with($layoutPath, '.php')) {
            $layoutPath .= '.php';
        }
        require $layoutPath;
    }
}
