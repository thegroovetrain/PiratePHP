<?php declare(strict_types=1);

namespace EricSeibt\PiratePHP;


interface RequestInterface
{
    /**
     * get all of the query params
     * 
     * this is basically a wrapper for $_GET
     * 
     * @return array containing all sanitized query params
     */
    public function getAllQueryParams():array;

    /**
     * get a specific query param by key
     * 
     * @param string $key the key of the desired query param
     * @param string|null $default (optional) can be used to set a default value if the key is not found
     * @return string|null the value of the query param with the given key, or $default if it is not found (null)
     */
    public function getQueryParam(string $key, $default=null):mixed;

    /**
     * get all of the post data
     * 
     * this is basically a wrapper for $_POST
     * 
     * @return array containing all sanitized query params
     */
    public function getAllPostData():array;

    /**
     * get a specific post data by key
     * 
     * @param string $key the key of the desired post data
     * @param string|null $default (optional) can be used to set a default value if the key is not found
     * @return string|null the value of the post data with the given key, or $default if it is not found (null)
     */
    public function getPostData(string $key, $default=null):mixed;

    /**
     * get all of the server data
     * 
     * this is basically a wrapper for $_SERVER
     * 
     * @return array containing all sanitized query params
     */
    public function getAllServerData():array;

    /**
     * get a specific server data by key
     * 
     * @param string $key the key of the desired post data
     * @param string|null $default (optional) can be used to set a default value if the key is not found
     * @return string|null the value of the post data with the given key, or $default if it is not found (null)
     */
    public function getServerData(string $key, $default=null):mixed;

    /**
     * returns the request method
     * 
     * @return string the request method, or '' if none is set.
     */
    public function getMethod():string;

    /**
     * returns whether or not the method is a CONNECT request
     * 
     * @return bool true if request method is CONNECT, otherwise false
     */
    public function isConnect():bool;

    /**
     * returns whether or not the method is a DELETE request
     * 
     * @return bool true if request method is DELETE, otherwise false
     */
    public function isDelete():bool;

    /**
     * returns whether or not the method is a GET request
     * 
     * @return bool true if request method is GET, otherwise false
     */
    public function isGet():bool;

    /**
     * returns whether or not the method is a HEAD request
     * 
     * @return bool true if request method is HEAD, otherwise false
     */
    public function isHead():bool;

    /**
     * returns whether or not the method is an OPTIONS request
     * 
     * @return bool true if request method is OPTIONS, otherwise false
     */
    public function isOptions():bool;

    /**
     * returns whether or not the method is a PATCH request
     * 
     * @return bool true if request method is PATCH, otherwise false
     */
    public function isPatch():bool;

    /**
     * returns whether or not the method is a POST request
     * 
     * @return bool true if request method is POST, otherwise false
     */
    public function isPost():bool;

    /**
     * returns whether or not the method is a PUT request
     * 
     * @return bool true if request method is PUT, otherwise false
     */
    public function isPut():bool;

    /**
     * returns whether or not the method is a TRACE request
     * 
     * @return bool true if request method is TRACE, otherwise false
     */
    public function isTrace():bool;
}