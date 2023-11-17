<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


trait HasNormalizeUriPath
{
    private function normalizeUriPath(string $uriPath):string
    {
        // Ensure the path/uriPath starts with a slash
        $uriPath = '/' . ltrim($uriPath,'/');

        // Remove trailing slash if it exists
        $uriPath = rtrim($uriPath, '/');

        // make sure its not an empty string
        if ($uriPath === '') {
            $uriPath = '/';
        }

        return $uriPath;
    }
}