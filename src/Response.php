<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class Response implements ResponseInterface
{
    use HasAttributes;

    const HTTP_STATUS_CODES = [
        100 => "Continue",
        101 => "Switching Protocols",
        102 => "Processing",
        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        203 => "Non-Authoritative Information",
        204 => "No Content",
        205 => "Reset Content",
        206 => "Partial Content",
        207 => "Multi-statusCode",
        300 => "Multiple Choices",
        301 => "Moved Permanently",
        302 => "Found",
        303 => "See Other",
        304 => "Not Modified",
        305 => "Use Proxy",
        306 => "(Unused)",
        307 => "Temporary Redirect",
        308 => "Permanent Redirect",
        400 => "Bad Request",
        401 => "Unauthorized",
        402 => "Payment Required",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        406 => "Not Acceptable",
        407 => "Proxy Authentication Required",
        408 => "Request Timeout",
        409 => "Conflict",
        410 => "Gone",
        411 => "Length Required",
        412 => "Precondition Failed",
        413 => "Request Entity Too Large",
        414 => "Request-URI Too Long",
        415 => "Unsupported Media Type",
        416 => "Requested Range Not Satisfiable",
        417 => "Expectation Failed",
        418 => "I'm a teapot",
        419 => "Authentication Timeout",
        420 => "Enhance Your Calm",
        422 => "Unprocessable Entity",
        423 => "Locked",
        424 => "Failed Dependency",
        425 => "Unordered Collection",
        426 => "Upgrade Required",
        428 => "Precondition Required",
        429 => "Too Many Requests",
        431 => "Request Header Fields Too Large",
        444 => "No Response",
        449 => "Retry With",
        450 => "Blocked by Windows Parental Controls",
        451 => "Unavailable For Legal Reasons",
        494 => "Request Header Too Large",
        495 => "Cert Error",
        496 => "No Cert",
        497 => "HTTP to HTTPS",
        499 => "Client Closed Request",
        500 => "Internal Server Error",
        501 => "Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout",
        505 => "HTTP Version Not Supported",
        506 => "Variant Also Negotiates",
        507 => "Insufficient Storage",
        508 => "Loop Detected",
        509 => "Bandwidth Limit Exceeded",
        510 => "Not Extended",
        511 => "Network Authentication Required",
        598 => "Network read timeout error",
        599 => "Network connect timeout error"
    ];


    private string|\Closure $body;
    private array $headers;
    private int $code;
    private string | null $message;


    private function __construct()
    {
        $this->body = '';
        $this->headers = [];
        $this->code = 200;
        $this->message = null;
    }


    public static function create():static
    {
        return new static();
    }


    public static function json(mixed $data, int $status = 200):static
    {
        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return static::create()->withStatus(500)->withBody('JSON encoding error');
        }
        return static::create()->withStatus($status)->withBody($json)
            ->withHeader('Content-Type', 'application/json');
    }


    public static function redirect(string $uri, int $status = 302):static
    {
        return static::create()->withStatus($status)->withHeader('Location', $uri);
    }


    public function withStatus(int $code, string $message = null):static
    {
        $new = clone $this;
        $new->code = $code;
        $new->message = $message;
        return $new;
    }


    public function withBody(string|callable $content):static
    {
        $new = clone $this;
        if (is_callable($content) && !($content instanceof \Closure)) {
            $new->body = \Closure::fromCallable($content);
        } else {
            $new->body = $content;
        }
        return $new;
    }


    public function withHeader(string $name, string $value):static
    {
        $new = clone $this;
        $new->headers[$name] = [$value];
        return $new;
    }


    public function withAddedHeader(string $name, string $value):static
    {
        $new = clone $this;
        $new->headers[$name] = [...($this->headers[$name] ?? []), $value];
        return $new;
    }


    public function withHeaders(array $headers):static
    {
        $new = clone $this;
        foreach ($headers as $name => $value) {
            if (is_array($value)) {
                $new->headers[$name] = $value;
            } else {
                $new->headers[$name] = [$value];
            }
        }
        return $new;
    }


    public function withoutHeaders(string ...$names):static
    {
        $new = clone $this;
        foreach($names as $name) {
            unset($new->headers[$name]);
        }
        return $new;
    }


    public function getStatusCode():int
    {
        return $this->code;
    }


    public function getStatusMessage():string
    {
        if(isset($this->message)) {
            return $this->message;
        }
        if(isset(self::HTTP_STATUS_CODES[$this->code])) {
            return self::HTTP_STATUS_CODES[$this->code];
        }
        // fallback
        return "";
    }


    public function getBody():string|\Closure
    {
        return $this->body;
    }


    public function getHeader(string $name):mixed
    {
        if (isset($this->headers[$name]) && count($this->headers[$name]) > 0) {
            return $this->headers[$name][0];
        }
        return null;
    }


    public function getHeaderArray(string $name):array
    {
        return $this->headers[$name] ?? [];
    }


    public function getHeaders():array
    {
        return $this->headers;
    }


    public function send():void
    {
        if(!headers_sent()) {
            foreach($this->headers as $name => $values) {
                foreach ($values as $value) {
                    header("$name: $value", false);
                }
            }
            http_response_code($this->getStatusCode());
        }
        if ($this->body instanceof \Closure) {
            ($this->body)();
        } else {
            echo $this->body;
        }
    }
}
