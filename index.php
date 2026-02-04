<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Context;
use App\Router;

$router = new Router();

$router->any('/api(/.*)?', fn() => [
    'message' => 'Hello World! Congrats! You accessed the API endpoint'
]);

$router->static('/', __DIR__ . '/dist');

$router->any('/react(/.*)?', function () {
    $host = '/';

    $manifest = json_decode(file_get_contents(__DIR__ . '/dist/.vite/manifest.json'), true);

    $entry = $manifest['react/main.tsx']['file'];
    $entryFile = rtrim($host, '/') . '/' . ltrim($entry, '/');
    $css = isset($manifest['react/main.tsx']['css']) ? $manifest['react/main.tsx']['css'] : [];

    $cssElems = '';
    foreach ($css as $cssFile) {
        $cssFile = rtrim($host, '/') . '/' . ltrim($cssFile, '/');
        $cssElems .= '<link rel="stylesheet" href="' . $cssFile . '">' . PHP_EOL;
    }

    return <<<HTML
        <!doctype html>
            <html lang="en">
            <head>
                <meta charset="UTF-8" />
                <link rel="icon" type="image/svg+xml" href="/logo.png" />
                <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                <title>React SPA</title>
                {$cssElems}
            </head>
            <body>
                <div id="root"></div>
                <script type="module" src="{$entryFile}"></script>
            </body>
            </html>
    HTML;
});

$router->any('/(.*)?', function () {
    $host = '/';

    $manifest = json_decode(file_get_contents(__DIR__ . '/dist/.vite/manifest.json'), true);

    $entry = $manifest['vue/main.js']['file'];
    $entryFile = rtrim($host, '/') . '/' . ltrim($entry, '/');
    $css = isset($manifest['vue/main.js']['css']) ? $manifest['vue/main.js']['css'] : [];

    $cssElems = '';
    foreach ($css as $cssFile) {
        $cssFile = rtrim($host, '/') . '/' . ltrim($cssFile, '/');
        $cssElems .= '<link rel="stylesheet" href="' . $cssFile . '">' . PHP_EOL;
    }

    return <<<HTML
        <html>
        <head>
            <title>Vue SPA</title>
            {$cssElems}
        </head>
        <body>
            <div id="app"></div>
            <script type="module" src="{$entryFile}"></script>
        </body>
        </html>
    HTML;
});

$router->run();
