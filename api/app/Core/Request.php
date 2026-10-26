<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    public string $method;
    public string $path;
    public array $query;
    public array $body;
    public array $files;
    public array $headers;

    public static function capture(): self
    {
        $r = new self();
        $r->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($base && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        $r->path = '/' . trim($uri, '/');

        $r->query   = $_GET ?? [];
        $r->files   = $_FILES ?? [];
        $r->headers = self::readHeaders();

        $raw = file_get_contents('php://input') ?: '';

        // Case-insensitive header lookup (getallheaders() casing varies by SAPI)
        $ct = '';
        foreach ($r->headers as $k => $v) {
            if (strtolower($k) === 'content-type') {
                $ct = (string) $v;
                break;
            }
        }
        if ($ct === '' && isset($_SERVER['CONTENT_TYPE'])) {
            $ct = $_SERVER['CONTENT_TYPE'];
        }

        if (stripos($ct, 'application/json') !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            $r->body = is_array($decoded) ? $decoded : [];
        } else {
            $r->body = $_POST ?? [];
        }

        // DEBUG — remove after fixing
        error_log('[REQ] ct=' . $ct . ' raw=' . substr($raw, 0, 200) . ' parsed=' . json_encode($r->body));

        return $r;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = strtolower($name);
        foreach ($this->headers as $k => $v) {
            if (strtolower($k) === $key) return $v;
        }
        return $default;
    }

    private static function readHeaders(): array
    {
        if (function_exists('getallheaders')) return getallheaders() ?: [];
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))));
                $headers[$name] = $v;
            }
        }
        return $headers;
    }
}
