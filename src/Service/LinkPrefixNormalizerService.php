<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Service;

class LinkPrefixNormalizerService
{
    public function execute(string $baseUrl, string $basePath): string
    {
        // normalize slashes
        $baseUrl = rtrim($baseUrl, '/');
        $basePath = trim($basePath, '/');

        // set default base path if not present
        if ($basePath === '') {
            $basePath = '/';
        } else {
            $basePath = sprintf('/%s/', $basePath);
        }

        // both empty
        if ($baseUrl === '') {
            return $basePath;
        }

        return $baseUrl . $basePath;
    }
}
