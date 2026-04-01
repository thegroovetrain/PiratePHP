<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class ContentNegotiationMiddleware
{
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

        $data = $response->getAttribute('_data');
        if ($data === null) {
            return $response;
        }

        // Check Accept header
        $accept = $request->getHeader('Accept') ?? $request->getHeader('accept') ?? '';

        if (str_contains($accept, 'application/json')) {
            return Response::json($data);
        }

        // Render with template
        try {
            $rendered = $this->renderer->render($this->defaultTemplate, is_array($data) ? $data : ['data' => $data]);
            return Response::create()->withBody($rendered)->withHeader('Content-Type', 'text/html');
        } catch (\Throwable $e) {
            return Response::create()->withStatus(500)->withBody('Template rendering error');
        }
    }
}
