<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class ContentNegotiationMiddleware
{
    public const ATTR_DATA = '_pirate_data';

    private RendererInterface $renderer;
    private string $defaultTemplate;


    private function __construct(RendererInterface $renderer, string $defaultTemplate)
    {
        $this->renderer = $renderer;
        $this->defaultTemplate = $defaultTemplate;
    }


    public static function create(RendererInterface $renderer, string $defaultTemplate): static
    {
        return new static($renderer, $defaultTemplate);
    }


    public function withRenderer(RendererInterface $renderer): static
    {
        $new = clone $this;
        $new->renderer = $renderer;
        return $new;
    }


    public function withDefaultTemplate(string $defaultTemplate): static
    {
        $new = clone $this;
        $new->defaultTemplate = $defaultTemplate;
        return $new;
    }


    public function __invoke(RequestInterface $request, callable $next): ResponseInterface
    {
        $response = $next($request);

        $data = $response->getAttribute(self::ATTR_DATA);
        if ($data === null) {
            return $response;
        }

        // Check Accept header
        $accept = $request->getHeader('accept') ?? '';

        // Preserve original status code and headers
        $status = $response->getStatusCode();
        $originalHeaders = $response->getHeaders();

        if (str_contains($accept, 'application/json')) {
            try {
                $json = json_encode($data, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                return Response::create()->withStatus(500)->withBody('JSON encoding error');
            }
            $result = Response::create()->withStatus($status)->withBody($json)
                ->withHeader('Content-Type', 'application/json');
        } else {
            try {
                $rendered = $this->renderer->render($this->defaultTemplate, is_array($data) ? $data : ['data' => $data]);
                $result = Response::create()->withStatus($status)->withBody($rendered)
                    ->withHeader('Content-Type', 'text/html');
            } catch (\Throwable $e) {
                return Response::create()->withStatus(500)->withBody('Template rendering error');
            }
        }

        // Copy original headers (except Content-Type which we set above)
        foreach ($originalHeaders as $name => $values) {
            if (strtolower($name) !== 'content-type') {
                foreach ($values as $value) {
                    $result = $result->withAddedHeader($name, $value);
                }
            }
        }

        return $result;
    }
}
