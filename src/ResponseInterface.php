<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


interface ResponseInterface
{
    /**
     * returns an empty Response instance.  This should be used as the constructor.
     *
     * the default response code is 200.
     *
     * @return static   the instance.
     */
    public static function create():static;

    /**
     * creates a JSON response
     *
     * @param mixed     $data       the data to encode as JSON
     * @param int       $status     the HTTP status code
     * @return static   the response.
     */
    public static function json(mixed $data, int $status = 200):static;

    /**
     * creates a redirect response
     *
     * @param string    $uri        the URI to redirect to
     * @param int       $status     the HTTP status code (default 302)
     * @return static   the response.
     */
    public static function redirect(string $uri, int $status = 302):static;

    /**
     * clones the current instance with the new given status code and message.
     *
     * @param int       $code       the new status code.
     * @param string    $message    the new status message.
     * @return static   the clone.
     */
    public function withStatus(int $code, string $message):static;

    /**
     * clones the current instance with the new given body.
     *
     * @param string|callable    $content    the new response body content.
     * @return static   the clone.
     */
    public function withBody(string|callable $content):static;

    /**
     * clones the current instance with the new header replacing any existing values
     *
     * @param string    $name   the new header's name.
     * @param string    $value  the new header's value.
     * @return static   the clone.
     */
    public function withHeader(string $name, string $value):static;

    /**
     * clones the current instance with the new header value appended
     *
     * @param string    $name   the header's name.
     * @param string    $value  the value to append.
     * @return static   the clone.
     */
    public function withAddedHeader(string $name, string $value):static;

    /**
     * clones the current instance with the given headers appended to the list of headers.
     *
     * @param array     $headers    an array of headers in the format [$name => $value] or [$name => $value[]]
     * @return static   the clone.
     */
    public function withHeaders(array $headers):static;

    /**
     * clones the current instance without the given headers in its list.
     *
     * @param string     ...$names
     * @return static   the clone.
     */
    public function withoutHeaders(string ...$names):static;

    /**
     * gets the current status code
     *
     * @return int  the current HTTP status code
     */
    public function getStatusCode():int;

    /**
     * gets the current status message
     *
     * @return string   the current status message
     */
    public function getStatusMessage():string;

    /**
     * gets the response body
     *
     * @return string|callable   the body
     */
    public function getBody():string|\Closure;

    /**
     * gets the first value of the named header.
     *
     * @param string    $name
     * @return string|null   the value of the header or null if it is not found.
     */
    public function getHeader(string $name):mixed;

    /**
     * gets all values for the named header.
     *
     * @param string    $name
     * @return array    the header values
     */
    public function getHeaderArray(string $name):array;

    /**
     * gets the current response headers
     *
     * @return array    the response headers as [$name => $value[]] pairs
     */
    public function getHeaders():array;

    /**
     * sends the current response
     *
     * @return void
     */
    public function send():void;

    /**
     * returns a clone with the given attribute set
     *
     * @param string    $key
     * @param mixed     $value
     * @return static
     */
    public function withAttribute(string $key, mixed $value):static;

    /**
     * returns a clone without the given attributes
     *
     * @param string    ...$keys
     * @return static
     */
    public function withoutAttribute(string ...$keys):static;

    /**
     * gets the value of the named attribute
     *
     * @param string    $key
     * @return mixed
     */
    public function getAttribute(string $key):mixed;
}
