<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class ErrorMiddleware
{
    private ?\Closure $errorHandler;


    private function __construct()
    {
        $this->errorHandler = null;
    }


    public static function create(): static
    {
        return new static();
    }


    public function withErrorHandler(callable $handler): static
    {
        $new = clone $this;
        $new->errorHandler = \Closure::fromCallable($handler);
        return $new;
    }


    public function __invoke(RequestInterface $request, callable $next): ResponseInterface
    {
        try {
            return $next($request);
        } catch (\Throwable $e) {
            if ($this->errorHandler !== null) {
                try {
                    return ($this->errorHandler)($e, $request);
                } catch (\Throwable $handlerError) {
                    return Response::create()->withStatus(500)->withBody('Internal Server Error')
                        ->withHeader('Content-Type', 'text/plain');
                }
            }
            return Response::create()->withStatus(500)->withBody('Internal Server Error')
                ->withHeader('Content-Type', 'text/plain');
        }
    }
}
