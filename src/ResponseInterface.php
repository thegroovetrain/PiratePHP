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
    public static function prepare():static;

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
     * @param string    $contet    the new response body content.
     * @return static   the clone.
     */
    public function withBody(string $contet):static;

    /**
     * clones the current instance with the new header added to the list of headers
     * 
     * @param string    $name   the new header's name.
     * @param string    $value  the new header's value.
     * @return static   the clone.
     */
    public function withHeader(string $name, string $value):static;

    /**
     * clones the current instance with the given headers appended to the list of headers.
     * 
     * @param array     $headers    an array of headers in the format [$name => $value]
     * @return static   the clone.
     */
    public function withHeaders(array $headers):static;

    /**
     * clones the current instance without the given header in its list.
     * 
     * @param string    $name
     * @return static   the clone.
     */
    public function withoutHeader(string $name):static;

    /**
     * clones the current instance without the given headers in its list.
     * 
     * @param array     $names
     * @return static   the clone.
     */
    public function withoutHeaders(array $names):static;

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
     * sets the status code and message and returns itself
     * 
     * @param int       $status     the desired status code
     * @param string    $message       overrides the default status message, if any
     * @return static   $this
     */
 
    public function getBody():string;

    /**
     * gets the value of the named header.
     * 
     * @param string    $name
     * @return string|null   the value of the header or null if it is not found.
     */
    public function getHeader(string $name):mixed;

    /**
     * gets the current response headers
     * 
     * @return array    the response headers as [$name => $value] pairs
     */
    public function getHeaders():array;


    /**
     * sends the current response
     * 
     * @return void
     */
    public function send():void;
}