<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class LoggingMiddleware
{
    private LoggerInterface $logger;


    private function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    public static function create(LoggerInterface $logger): static
    {
        return new static($logger);
    }


    public function __invoke(RequestInterface $request, callable $next): ResponseInterface
    {
        $start = microtime(true);

        $response = $next($request);

        $elapsed = (microtime(true) - $start) * 1000;
        $date = date('Y-m-d H:i:s');
        $method = $request->getMethod() ?? 'UNKNOWN';
        $uri = $request->getUri() ?? '/';
        $status = $response->getStatusCode();
        $ms = round($elapsed);

        try {
            $this->logger->log("[{$date}] {$method} {$uri} {$status} {$ms}ms");
        } catch (\Throwable $e) {
            // silently swallow logger failures
        }

        return $response;
    }
}
