<?php declare(strict_types=1);

namespace EricSeibt\PiratePHP;


class Request implements RequestInterface
{
    private array $headers = [];
    private array $postData = [];
    private array $queryParams = [];
    private array $serverData = [];
    

    function __construct()
    {
        $this->serverData = $_SERVER;
        $this->queryParams = $_GET;
        $this->postData = $_POST;
        $this->serverData = $_SERVER;
        $this->headers = $this->getAllHeaders();
    }

    /**
     * provides a fallback for servers that do not have getallheaders()
     * 
     * @return array either the output of getallheaders() or request headers parsed from $_SERVER
     */
    private function getAllHeaders():array
    {
        if(function_exists('getallheaders')) {
            return getallheaders();
        }
        $headers = [];
        foreach($_SERVER as $key => $value) {
            if(substr($key, 0, 5) == 'HTTP_') {
                $key = str_replace('_', ' ', substr($key, 5));
                $key = str_replace(' ', '-', ucwords(strtolower($key)));
                $headers[$key] = $value;
            }
        }
        return $headers;
    }


    public function getAllQueryParams():array
    {
        return $this->queryParams;
    }

    
    public function getQueryParam(string $key, $default=null):mixed
    {
        return $this->queryParams[$key] ?? $default;
    }


    public function getAllPostData():array
    {
        return $this->postData;
    }


    public function getPostData(string $key, $default=null):mixed
    {
        return $this->postData[$key] ?? $default;
    }


    public function getAllServerData():array
    {
        return $this->serverData;
    }
}