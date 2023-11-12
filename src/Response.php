<?php declare(strict_types=1);

namespace EricSeibt\PiratePHP;


class Response implements ResponseInterface
{
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
        424 => "Method Failure",
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


    private string $body = '';
    private array $headers = [];
    private int $statusCode = 200;
    private string $statusMessage;


    /**
     * {@inheritdoc}
     */
    public function getStatusCode():int
    {
        return $this->statusCode;
    }


    /**
     * {@inheritdoc}
     */
    public function getStatusMessage():string
    {
        return $this->statusMessage;
    }

    
    /**
     * {@inheritdoc}
     */
    public function setStatus(int $code, string $message = null):static
    {
        $this->statusCode = $code;
        
        if(isset(self::HTTP_STATUS_CODES[$code])) {
            // if there's a default response code message available, start with that.
            $this->statusMessage = self::HTTP_STATUS_CODES[$code];
        } else {
            // otherwise, start with empty text for the defalt.
            $this->statusMessage = '';
        }

        // if a custom message was provided, use that instead
        if(isset($message)) {
            $this-> statusMessage = $message;
        }

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function getBody():string
    {
        return $this->body;
    }


    /**
     * {@inheritdoc}
     */
    public function setBody(string $content):static
    {
        $this->body = $content;
        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function getHeaders():array
    {
        return $this->headers;
    }


    /**
     * {@inheritdoc}
     */
    public function addHeader(string $name, string $value):static
    {
        $this->headers[$name] = $value;
        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function addHeaders(array $headers):static
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function send():void
    {
        if(!headers_sent()) {
            foreach($this->headers as $name => $value) {
                header("$name: $value");
            }
            http_response_code($this->statusCode);
        }
        echo $this->body;
    }
}