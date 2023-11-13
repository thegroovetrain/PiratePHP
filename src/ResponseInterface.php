<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


interface ResponseInterface
{
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
    public function setStatus(int $status, string|null $message):static;

    /**
     * gets the current response body
     * 
     * @return string   the current response body
     */
    public function getBody():string;

    /**
     * sets the response body and returns itself
     * 
     * @param string    $text   the desired response body
     * @return static   $this
     */
    public function setBody(string $body):static;

    /**
     * gets the current response headers
     * 
     * @return array    the response headers as [$name => $value] pairs
     */
    public function getHeaders():array;

    /**
     * adds a header to the response and returns itself
     * 
     * @param string    $name   name of the header
     * @param string    $value  value of the header
     * @return static   $this   
     */
    public function addHeader(string $name, string $value):static;

    /**
     * adds an array of headers to the response and returns itself
     * 
     * @param array     $headers    array of headers [$name => $value]
     * @return static   $this
     */
    public function addHeaders(array $headers):static;

    /**
     * sends the current response
     * 
     * @return void
     */
    public function send():void;
}