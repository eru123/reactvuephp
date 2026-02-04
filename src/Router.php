<?php

namespace App;

class Router
{
    private $__staticRoutes__ = [];
    private $__routes__ = [];
    private $__prefix__ = '';
    private bool $__suppressExit__ = false;
    private $__lastResponse__ = null;
    private int $__lastStatus__ = 200;

    public function __construct(string $prefix = '')
    {
        $this->__prefix__ = $prefix;
    }

    public function prefix(string $prefix)
    {
        $this->__prefix__ = $prefix;

        return $this;
    }

    public function group(string $prefix, $callback): self
    {
        $router = new self($this->__prefix__ . $prefix);
        Callback::call($callback, [$router]);

        foreach ($router->__staticRoutes__ as $path => $route) {
            $fullPath = $path;
            if ($router->__prefix__ !== '' && $path !== $router->__prefix__ && !str_starts_with($path, $router->__prefix__ . '/')) {
                $fullPath = $router->__prefix__ . $path;
            }
            $this->static($fullPath, $route['directory']);
        }

        foreach ($router->__routes__ as $path => $routes) {
            foreach ($routes as $route) {
                $fullPath = $path;
                if ($router->__prefix__ !== '' && $path !== $router->__prefix__ && !str_starts_with($path, $router->__prefix__ . '/')) {
                    $fullPath = $router->__prefix__ . $path;
                }
                $this->add($fullPath, $route['method'], ...$route['handlers']);
            }
        }

        return $this;
    }

    public function suppressExit(bool $suppress = true): self
    {
        $this->__suppressExit__ = $suppress;
        return $this;
    }

    // For tests/debugging
    public function getRoutes(): array
    {
        return $this->__routes__;
    }

    public function isSuppressExit(): bool
    {
        return $this->__suppressExit__;
    }

    public function getLastResponse(): array
    {
        return [
            'status' => $this->__lastStatus__,
            'body' => $this->__lastResponse__
        ];
    }

    public function static(string $path, string $directory)
    {
        $this->__staticRoutes__[$path] = [
            'type' => 'static',
            'directory' => $directory
        ];

        return $this;
    }

    private $__middleware__ = [];

    public function use($middleware): self
    {
        $this->__middleware__[] = $middleware;
        return $this;
    }

    public function add(string $path, string $method, ...$handlers)
    {
        if (!isset($this->__routes__[$path])) {
            $this->__routes__[$path] = [];
        }

        // Prepend middleware to handlers
        $allHandlers = array_merge($this->__middleware__, $handlers);

        $this->__routes__[$path][] = [
            'type' => 'dynamic',
            'method' => $method,
            'handlers' => $allHandlers
        ];

        return $this;
    }

    public function any(string $path, ...$handlers): self
    {
        return $this->add($path, 'ANY', ...$handlers);
    }

    public function options(string $path, ...$handlers): self
    {
        return $this->add($path, 'OPTIONS', ...$handlers);
    }

    public function get(string $path, ...$handlers): self
    {
        return $this->add($path, 'GET', ...$handlers);
    }

    public function post(string $path, ...$handlers): self
    {
        return $this->add($path, 'POST', ...$handlers);
    }

    public function put(string $path, ...$handlers): self
    {
        return $this->add($path, 'PUT', ...$handlers);
    }

    public function delete(string $path, ...$handlers): self
    {
        return $this->add($path, 'DELETE', ...$handlers);
    }

    public function serveFile(string $path): bool
    {
        $file = realpath($path);
        if ($file && is_file($file)) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $mime = 'application/octet-stream';
            if (in_array($ext, ['html', 'css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'json', 'xml', 'txt', 'pdf', 'mp4', 'mp3', 'wav', 'ogg', 'webm', 'ico'])) {
                $mimeTypes = [
                    'html' => 'text/html',
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                    'json' => 'application/json',
                    'xml' => 'application/xml',
                    'txt' => 'text/plain',
                    'pdf' => 'application/pdf',
                    'mp4' => 'video/mp4',
                    'mp3' => 'audio/mpeg',
                    'wav' => 'audio/wav',
                    'ogg' => 'audio/ogg',
                    'webm' => 'video/webm',
                    'ico' => 'image/x-icon'
                ];
                $mime = $mimeTypes[$ext];
            } else if (function_exists('mime_content_type')) {
                $mime = mime_content_type($file);
            }

            $filesize = filesize($file);
            $etag = hash_file('sha256', $file);
            $tz = date_default_timezone_get();
            date_default_timezone_set('UTC');
            $last_modified = date('D, d M Y H:i:s T', filemtime($file));
            date_default_timezone_set($tz);

            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
                header('HTTP/1.1 304 Not Modified');
                exit;
            }

            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === $last_modified) {
                header('HTTP/1.1 304 Not Modified');
                exit;
            }

            header('Content-Type: ' . $mime);
            header('Content-Length: ' . $filesize);
            header('ETag: ' . $etag);
            header('Last-Modified: ' . $last_modified);
            header('Accept-Ranges: bytes');

            readfile($path);
            exit;
        }

        return false;
    }

    private function joinUrl(string ...$paths): string
    {
        $paths = array_filter($paths, function ($path) {
            return !empty(trim($path, " \n\r\t\v\0\\/")) && (bool) preg_match('//u', $path);
        });

        $paths = array_map(function ($path) {
            return trim($path, " \n\r\t\v\0\\/");
        }, $paths);

        $path = implode('/', $paths);

        if ('/' == substr($path, -1)) {
            $path = substr($path, 0, -1);
        }

        return '/' . $path;
    }

    private function isUriMatch(string $path, array &$params = [], bool $isStatic = false): bool
    {
        $rgxSuffix = $isStatic ? '\\/?(?P<resource>.*)?$/' : '\\/?$/';
        $cpath = $this->joinUrl($this->__prefix__, $path);
        if ('$' == substr($cpath, -1)) {
            $cpath = substr($cpath, 0, -1);
            $rgxSuffix = '\\/?$/';
        }

        $uri = preg_replace('/\//', '\\\/', $cpath);
        $rgx = '/\{([a-zA-Z_]([a-zA-Z0-9_]+)?)\}|\$([a-zA-Z_]([a-zA-Z0-9_]+)?)|\:([a-zA-Z_]([a-zA-Z0-9_]+)?)/';
        $rgx = preg_replace_callback($rgx, fn($m) => '(?P<' . ($m[1] ?: $m[3] ?: $m[5]) . '>[^\\/\\?]+)', $uri);
        $rgx = '/^' . $rgx . $rgxSuffix;

        $match = (bool) preg_match($rgx, $this->uri(), $params);
        return $match;
    }

    private function uri(): string
    {
        $uri = preg_replace('/\?.*$/', '', (string) $_SERVER['REQUEST_URI']);
        $uri = '/' . trim($uri, '/');

        return $uri;
    }

    private function getRequestHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[ucwords(strtolower($name), '-')] = $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }

        return $headers;
    }

    private function readRawBody(): string
    {
        if (isset($GLOBALS['__RAW_INPUT__'])) {
            return (string) $GLOBALS['__RAW_INPUT__'];
        }
        if (isset($GLOBALS['__TEST_RAW_INPUT__'])) {
            return (string) $GLOBALS['__TEST_RAW_INPUT__'];
        }

        $raw = file_get_contents('php://input');
        $GLOBALS['__RAW_INPUT__'] = $raw === false ? '' : $raw;
        return (string) $GLOBALS['__RAW_INPUT__'];
    }

    private function getRequestPayload(): mixed
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $raw = $this->readRawBody();

        if (stripos($contentType, 'application/json') !== false) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : $raw;
        }

        if (stripos($contentType, 'application/x-www-form-urlencoded') !== false || stripos($contentType, 'multipart/form-data') !== false) {
            return $_POST ?: ($raw !== '' ? $raw : null);
        }

        return $raw !== '' ? $raw : null;
    }

    private function logDebug(string $message, array $context): void
    {
        error_log($message . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function manual(string $path, $route)
    {
        $params = [];
        if (!$this->isUriMatch($path, $params, $route['type'] === 'static')) {
            return false;
        }

        $params = array_filter($params, fn($key) => !is_int($key), ARRAY_FILTER_USE_KEY);
        $ctx = new Context($this, $params);

        if ('static' == $route['type']) {
            $resource = $params['resource'] ?? '';
            if (empty($resource)) {
                $resource = 'index.html';
            }
            $baseDirectory = realpath($route['directory']);
            if ($baseDirectory === false) {
                return false;
            }

            $filePath = realpath($baseDirectory . DIRECTORY_SEPARATOR . ltrim($resource, '/\\'));
            if ($filePath === false) {
                return false;
            }

            // Prevent directory traversal by ensuring resolved path stays inside the static directory
            if (strpos($filePath, $baseDirectory) !== 0) {
                return false;
            }
            if ($this->serveFile($filePath)) return true;
            return false;
        }

        if ('dynamic' == $route['type']) {
            $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

            if ('ANY' != strtoupper($route['method']) && $method != strtoupper($route['method'])) {
                return false;
            }

            $uri = $this->uri();
            $debugErrors = [];
            $debugException = null;
            $hasDebugHandler = false;
            $debugPayload = null;

            $res = null;
            try {
                foreach ($route['handlers'] as $handler) {
                    if (false === $handler) return false;
                    $res = Callback::call($handler, [$ctx]);

                    if ($res === false || $res === null) return false;
                    if ($res === true) continue;

                    // If we have a response, stop the chain
                    break;
                }
            } catch (\Throwable $e) {
                $ctx->http(500);
                $res = [
                    'error' => true,
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                ];
            }

            if ($res) {
                if ($this->__suppressExit__) {
                    $this->__lastResponse__ = $res;
                    $this->__lastStatus__ = http_response_code() ?: 200;
                    return true;
                }
                if (is_string($res) || is_numeric($res)) {
                    echo $res;
                    exit;
                } elseif (is_array($res) || is_object($res)) {
                    header('Content-Type: application/json');
                    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }

                header('HTTP/1.1 204 No Content');
                exit;
            }

            return true;
        }
    }

    public function run(): void
    {
        // Reset last captured response for a fresh run
        $this->__lastResponse__ = null;
        $this->__lastStatus__ = 200;

        if ($this->__suppressExit__) {
            ob_start();
        }

        foreach ($this->__staticRoutes__ as $path => $route) {
            if ($this->manual($path, $route)) {
                if ($this->__suppressExit__) {
                    ob_end_clean();
                }
                return;
            }
        }
        foreach ($this->__routes__ as $path => $routes) {
            foreach ($routes as $route) {
                if ($this->manual($path, $route)) {
                    if ($this->__suppressExit__) {
                        ob_end_clean();
                    }
                    return;
                }
            }
        }

        if ($this->__suppressExit__) {
            ob_end_clean();
        }
    }
}
