<?php

use Fefi\Vite\Vite;
use Kirby\Cms\App;

App::plugin('femundfilou/vite', [
    'options' => [
        'main' => 'frontend/index.ts',
        'manifest' => 'manifest.json',
        'server' => 'https://vite.test:3000',
        'dev' => false
    ]
]);

function vite()
{
    return new Vite();
}
