<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class Request implements RequestInterface
{
    use HasAttributes;
    use HasNormalizeUriPath;

    private array $headers;
    private array $postData;
    private array $queryParams;
    private array $serverData;
    private string $rawBody;
    private mixed $parsedBody;
    private bool $parsedBodyCached;


    const HTTP_CONNECT = 'CONNECT';
    const HTTP_DELETE = 'DELETE';
    const HTTP_GET = 'GET';
    const HTTP_HEAD = 'HEAD';
    const HTTP_OPTIONS = 'OPTIONS';
    const HTTP_PATCH = 'PATCH';
    const HTTP_POST = 'POST';
    const HTTP_PUT = 'PUT';
    const HTTP_TRACE = 'TRACE';


    public static function create():static
    {
        $rawBody = file_get_contents('php://input') ?: '';
        return new static($_GET, $_POST, $_SERVER, [], $rawBody);
    }


    public static function createFromArrays(
        array $query = [],
        array $post = [],
        array $server = [],
        array $headers = [],
        string $body = ''
    ):static
    {
        return new static($query, $post, $server, $headers, $body);
    }


    private function __construct(
        array $get = [],
        array $post = [],
        array $server = [],
        array $headers = [],
        string $body = ''
    )
    {
        $this->serverData = $server;
        $this->queryParams = $get;
        $this->postData = $post;
        $this->rawBody = $body;
        $this->parsedBodyCached = false;
        $this->parsedBody = null;

        if (!empty($headers)) {
            $normalized = [];
            foreach ($headers as $key => $value) {
                $normalized[strtolower($key)] = $value;
            }
            $this->headers = $normalized;
        } else {
            $this->headers = $this->getAllHeaders($server);
        }
    }

    /**
     * provides a fallback for servers that do not have getallheaders()
     *
     * @return array either the output of getallheaders() or request headers parsed from $_SERVER
     */
    private function getAllHeaders(array $server):array
    {
        if(function_exists('getallheaders')) {
            $raw = getallheaders();
            $headers = [];
            foreach ($raw as $key => $value) {
                $headers[strtolower($key)] = $value;
            }
            return $headers;
        }
        $headers = [];
        foreach($server as $key => $value) {
            if(substr($key, 0, 5) == 'HTTP_') {
                $key = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$key] = $value;
            } elseif ($key === 'CONTENT_TYPE') {
                $headers['content-type'] = $value;
            } elseif ($key === 'CONTENT_LENGTH') {
                $headers['content-length'] = $value;
            }
        }
        return $headers;
    }


    public function getQueryParams():array
    {
        return $this->queryParams;
    }


    public function getQueryParam(string $key, string|null $default=null):mixed
    {
        return $this->queryParams[$key] ?? $default;
    }


    public function getPostData():array
    {
        return $this->postData;
    }


    public function getPostDatum(string $key, string|null $default=null):mixed
    {
        return $this->postData[$key] ?? $default;
    }


    public function getServerData():array
    {
        return $this->serverData;
    }


    public function getServerDatum(string $key, string|null $default=null):mixed
    {
        return $this->serverData[$key] ?? $default;
    }


    public function getHeaders():array
    {
        return $this->headers;
    }


    public function getHeader(string $key, string|null $default=null):mixed
    {
        return $this->headers[strtolower($key)] ?? $default;
    }


    public function getUri():mixed
    {
        $uri = $this->getServerDatum('REQUEST_URI') ?? '/';
        $uri = strtok($uri, '?');
        if ($uri === false) {
            $uri = '/';
        }
        return $this->normalizeUriPath($uri);
    }


    public function getMethod():mixed
    {
        return $this->getServerDatum('REQUEST_METHOD');
    }


    public function getRawBody():string
    {
        return $this->rawBody;
    }


    public function getParsedBody():mixed
    {
        if ($this->parsedBodyCached) {
            return $this->parsedBody;
        }
        $this->parsedBodyCached = true;
        $contentType = $this->getHeader('content-type') ?? '';
        if (str_contains($contentType, 'application/json')) {
            try {
                $this->parsedBody = json_decode($this->rawBody, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->parsedBody = null;
            }
        }
        return $this->parsedBody;
    }
}
