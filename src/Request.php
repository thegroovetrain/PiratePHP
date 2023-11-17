<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class Request implements RequestInterface
{
    private array $headers;
    private array $postData;
    private array $queryParams;
    private array $serverData;
    private array $attributes = [];


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
        return new static($_GET, $_POST, $_SERVER);
    }
    

    private function __construct(array $get = [], array $post = [], array $server = [])
    {
        $this->serverData = $server;
        $this->queryParams = $get;
        $this->postData = $post;
        $this->headers = $this->getAllHeaders($server);
    }


    /**
     * provides a fallback for servers that do not have getallheaders()
     * 
     * @return array either the output of getallheaders() or request headers parsed from $_SERVER
     */
    private function getAllHeaders(array $server):array
    {
        if(function_exists('getallheaders')) {
            return getallheaders();
        }
        $headers = [];
        foreach($server as $key => $value) {
            if(substr($key, 0, 5) == 'HTTP_') {
                $key = str_replace('_', ' ', substr($key, 5));
                $key = str_replace(' ', '-', ucwords(strtolower($key)));
                $headers[$key] = $value;
            }
        }
        return $headers;
    }


    public function withAttribute(string $key, mixed $value):static
    {
        $new = clone $this;
        $new->attributes[$key] = $value;
        return $new;
    }


    public function withoutAttributes(string ...$keys):static
    {
        $new = clone $this;
        foreach($keys as $key) {
            unset($new->attributes[$key]);
        }
        return $new;
    }


    public function getAttribute(string $key):mixed
    {
        return $this->attributes[$key] ?? null;
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
        return $this->headers[$key] ?? $default;
    }


    public function getUri():mixed
    {
        return $this->getServerDatum('REQUEST_URI');
    }


    public function getMethod():mixed
    {
        return $this->getServerDatum('REQUEST_METHOD');
    }
}