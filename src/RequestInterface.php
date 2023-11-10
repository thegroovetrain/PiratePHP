<?php declare(strict_types=1);

namespace EricSeibt\PiratePHP;


interface RequestInterface
{
    /**
     * get all of the query params
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
     * get all of the query params
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
}