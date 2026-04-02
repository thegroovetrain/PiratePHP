<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use thegroovetrain\PiratePHP\{LoggingMiddleware, LoggerInterface, Request, Response, RequestInterface, ResponseInterface};


final class LoggingMiddlewareTest extends TestCase
{
    public function testLogFormat(): void
    {
        $loggedMessages = [];
        $logger = new class($loggedMessages) implements LoggerInterface {
            private array $messages;
            public function __construct(array &$messages)
            {
                $this->messages = &$messages;
            }
            public function log(string $message): void
            {
                $this->messages[] = $message;
            }
        };

        $middleware = LoggingMiddleware::create($logger);
        $request = Request::createFromArrays([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/hello',
        ]);

        $response = $middleware($request, function (RequestInterface $req): ResponseInterface {
            return Response::create()->withStatus(200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $loggedMessages);

        // Verify format: [Y-m-d H:i:s] METHOD /uri STATUS XXms
        $msg = $loggedMessages[0];
        $this->assertMatchesRegularExpression(
            '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] GET \/hello 200 \d+ms$/',
            $msg
        );
    }


    public function testLoggerFailureSilentlySwallowed(): void
    {
        $logger = new class implements LoggerInterface {
            public function log(string $message): void
            {
                throw new \RuntimeException('Logger broken');
            }
        };

        $middleware = LoggingMiddleware::create($logger);
        $request = Request::createFromArrays([], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/submit',
        ]);

        // Should not throw even though logger fails
        $response = $middleware($request, function (RequestInterface $req): ResponseInterface {
            return Response::create()->withStatus(201);
        });

        $this->assertSame(201, $response->getStatusCode());
    }
}
