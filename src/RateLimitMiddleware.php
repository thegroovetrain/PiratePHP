<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class RateLimitMiddleware
{
    private int $maxRequests;
    private int $windowSeconds;
    private string $storagePath;


    private function __construct(int $maxRequests, int $windowSeconds, string $storagePath)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->storagePath = $storagePath;
    }


    public static function create(int $maxRequests, int $windowSeconds, string $storagePath): static
    {
        return new static($maxRequests, $windowSeconds, $storagePath);
    }


    public function withMaxRequests(int $maxRequests): static
    {
        $new = clone $this;
        $new->maxRequests = $maxRequests;
        return $new;
    }


    public function withWindowSeconds(int $windowSeconds): static
    {
        $new = clone $this;
        $new->windowSeconds = $windowSeconds;
        return $new;
    }


    public function withStoragePath(string $storagePath): static
    {
        $new = clone $this;
        $new->storagePath = $storagePath;
        return $new;
    }


    public function __invoke(RequestInterface $request, callable $next): ResponseInterface
    {
        $serverData = $request->getServerData();
        $ip = $serverData['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = md5($ip);
        $file = $this->storagePath . '/' . $key;

        try {
            if (!is_dir($this->storagePath)) {
                @mkdir($this->storagePath, 0755, true);
            }

            $handle = @fopen($file, 'c+');
            if ($handle === false) {
                // Storage failure: degrade gracefully, allow through
                return $next($request);
            }

            if (!flock($handle, LOCK_EX)) {
                fclose($handle);
                return $next($request);
            }

            $content = stream_get_contents($handle);
            $data = $content !== '' ? json_decode($content, true) : null;

            $now = time();

            if (!is_array($data) || !isset($data['window_start']) || !isset($data['count'])) {
                $data = ['window_start' => $now, 'count' => 0];
            }

            // Check if window has expired
            if (($now - $data['window_start']) >= $this->windowSeconds) {
                $data = ['window_start' => $now, 'count' => 0];
            }

            $data['count']++;

            // Write updated data
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($data));
            flock($handle, LOCK_UN);
            fclose($handle);

            if ($data['count'] > $this->maxRequests) {
                return Response::create()->withStatus(429)->withBody('Too Many Requests');
            }

            return $next($request);
        } catch (\Throwable $e) {
            // Storage failure: degrade gracefully, allow through
            return $next($request);
        }
    }
}
