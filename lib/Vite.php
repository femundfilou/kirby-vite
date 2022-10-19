<?php

declare(strict_types = 1);

namespace Fefi\Vite;

use Kirby\Data\Json;
use Kirby\Filesystem\F;
use Kirby\Toolkit\A;

class Vite
{
    /**
     * reads vite manifest.json file
     * @return array|null JSON Manifest
     * @var string $manifestPath Path to manifest.json, relative from kirby root
     */
    private function readManifest(): array|null
    {
        static $manifest = null;
        // Check if manifest exists
        $manifestPath = kirby()->option('femundfilou.vite.manifest');
        if (!F::exists(kirby()->root() . DS . $manifestPath)) {
            return null;
        }
        // Read manifest
        if (!$manifest) {
            $manifest = Json::read(kirby()->root() . DS . $manifestPath);
        }
        return $manifest;
    }
    /**
     * Checks whether currently in development mode or not
     * @return bool In Development Mode
     */
    private function inDevelopment(): bool
    {
        return kirby()->option('femundfilou.vite.dev');
    }

    /**
     * Generate link tag of given source from manifest, if $template is null, all css files inside manifest.json will be used.
     * @param string $template path to template, as referenced in manifest.json
     * @param array $options Pass an array of attributes for the link tag
     * @return string|null Script Tag
     */
    public function css(string|null $template = '', array $options = []): string|null
    {
        // No styles in development
        if (self::inDevelopment()) {
            return null;
        }
        // Use default if empty
        $template = $template === '' ? kirby()->option('femundfilou.vite.main') : $template;
        // Get manifest.json
        $manifest = self::readManifest();
        if (!$manifest) {
            return null;
        }
        if (!$template) {
            $cssFiles = A::pluck(A::map($manifest, function ($entry) {
                if (!isset($entry['isEntry']) || $entry['isEntry'] === false) {
                    return A::get($entry, 'css');
                }
            }), '0');
        } else {
            // Get entry
            $entry = A::get($manifest, $template);
            if (!$entry || !isset($entry['css'])) {
                return null;
            }
            $cssFiles = $entry['css'];
        }
        // Generate link tags
        return css($cssFiles, $options);
    }
    /**
     * Generate script tag of given source from manifest
     * @param string $template path to template, as referenced in manifest.json
     * @param array $options Pass an array of attributes for the script tag, ['type' => 'module'] included by default
     * @return string|null Script Tag
     */
    public function js(string|null $template = '', array $options = []): string|null
    {
        // Use default if empty
        $template = $template === '' ? kirby()->option('femundfilou.vite.main') : $template;

        // Add vite server in development
        if (self::inDevelopment()) {
            static $isClientAdded = false;
            $server = kirby()->option('femundfilou.vite.server');
            if ($isClientAdded) {
                return js(["${server}/${template}"], ['type' => 'module']);
            } else {
                $isClientAdded = true;
                return js(["${server}/@vite/client", "${server}/${template}"], ['type' => 'module']);
            }
        }
        // Get manifest
        $manifest = self::readManifest();
        if (!$manifest) {
            return null;
        }
        // Get given entry
        $entry = A::get($manifest, $template);
        if (!$entry) {
            return null;
        }
        // Return script tag
        return js($entry['file'], A::merge(['type' => 'module'], $options));
    }
}
