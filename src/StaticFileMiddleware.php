<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class StaticFileMiddleware
{
    private string $baseDir;
    private array $mimeTypes;

    private static array $defaultMimeTypes = [
        'html' => 'text/html',
        'htm' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'zip' => 'application/zip',
    ];


    private function __construct(string $baseDir, array $mimeTypes = [])
    {
        $this->baseDir = $baseDir;
        $this->mimeTypes = array_merge(self::$defaultMimeTypes, $mimeTypes);
    }


    public static function create(string $baseDir, array $mimeTypes = []): static
    {
        return new static($baseDir, $mimeTypes);
    }


    public function withBaseDir(string $baseDir): static
    {
        $new = clone $this;
        $new->baseDir = $baseDir;
        return $new;
    }


    public function withMimeTypes(array $mimeTypes): static
    {
        $new = clone $this;
        $new->mimeTypes = array_merge($new->mimeTypes, $mimeTypes);
        return $new;
    }


    public function __invoke(RequestInterface $request, callable $next): ResponseInterface
    {
        $uri = $request->getUri();

        // Block dotfiles (hidden files/directories like .htaccess, .env, .hidden/)
        $segments = explode('/', $uri);
        foreach ($segments as $segment) {
            if ($segment !== '' && str_starts_with($segment, '.')) {
                return $next($request);
            }
        }

        $filePath = $this->baseDir . $uri;
        $realBase = realpath($this->baseDir);
        $realFile = realpath($filePath);

        // Validate: realpath must resolve and must be within base dir
        if ($realBase === false || $realFile === false) {
            return $next($request);
        }

        if (!str_starts_with($realFile, $realBase)) {
            return $next($request);
        }

        if (!is_file($realFile)) {
            return $next($request);
        }

        // Determine content type from extension
        $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        $contentType = $this->mimeTypes[$ext] ?? 'application/octet-stream';

        return Response::create()
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Content-Length', (string) filesize($realFile))
            ->withBody(function () use ($realFile) {
                readfile($realFile);
            });
    }
}
