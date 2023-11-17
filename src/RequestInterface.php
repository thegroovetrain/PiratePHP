<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


interface RequestInterface
{
    /**
     * creates a request object from the incoming request.
     * 
     * @return static
     */
    public static function create():static;

    /**
     * returns a clone of the request with the added attribute.
     * 
     * @param string    $key
     * @param mixed     $value
     * @return static
     */
    public function withAttribute(string $key, mixed $value):static;

    /**
     * returns a clone of the request without the given attributes
     * 
     * @param string    ...$keys
     * @return static
     */
    public function withoutAttributes(string ...$keys):static;

    /**
     * gets the value of the named attribute
     * 
     * @param string    $key
     * @return mixed
     */
    public function getAttribute(string $key):mixed;

    /**
     * get all of the query params
     * 
     * this is basically a wrapper for $_GET
     * 
     * @return array containing all sanitized query params
     */
    public function getQueryParams():array;

    /**
     * get a specific query param by key
     * 
     * @param string $key the key of the desired query param
     * @param string|null $default (optional) can be used to set a default value if the key is not found
     * @return string|null the value of the query param with the given key, or $default if it is not found (null)
     */
    public function getQueryParam(string $key, string|null $default=null):mixed;

    /**
     * get all of the post data
     * 
     * this is basically a wrapper for $_POST
     * 
     * @return array containing all post data
     */
    public function getPostData():array;

    /**
     * get a specific post data by key
     * 
     * @param string $key the key of the desired post data
     * @param string|null $default (optional) can be used to set a default value if the key is not found
     * @return string|null the value of the post data with the given key, or $default if it is not found (null)
     */
    public function getPostDatum(string $key, string|null $default=null):mixed;

    /**
     * get all of the server data
     * 
     * this is basically a wrapper for $_SERVER
     * 
     * @return array containing all server data
     */
    public function getServerData():array;

    /**
     * get a specific server data by key
     * 
     * @param string $key the key of the desired post data
     * @param string|null $default (optional) can be used to set a default value if the key is not found
     * @return string|null the value of the server data with the given key, or $default if it is not found (null)
     */
    public function getServerDatum(string $key, string|null $default=null):mixed;


    /**
     * get all of the request headers
     * 
     * @return array containing all request headers
     */
    public function getHeaders():array;


    /**
     * get a specific header by key
     * 
     * @param string $key the key of the desired header
     * @param string|null $default (optional) can be used to set a default value if the key is not found
     * @return string|null the value of the header with the given key, or $default if not found (null)
     */
    public function getHeader(string $key, string|null $default=null):mixed;


    /**
     * returns the uri hit by the request (request_uri)
     * 
     * @return string the request uri, or null if unset.
     */
    public function getUri():mixed;

    /**
     * returns the request method
     * 
     * @return string the request method, or null if unset.
     */
    public function getMethod():mixed;
}