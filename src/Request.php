<?php declare(strict_types=1);

namespace EricSeibt\PiratePHP;


class Request implements RequestInterface
{
    private array $headers = [];
    private array $postData = [];
    private array $queryParams = [];
    private array $serverData = [];


    const HTTP_CONNECT = 'CONNECT';
    const HTTP_DELETE = 'DELETE';
    const HTTP_GET = 'GET';
    const HTTP_HEAD = 'HEAD';
    const HTTP_OPTIONS = 'OPTIONS';
    const HTTP_PATCH = 'PATCH';
    const HTTP_POST = 'POST';
    const HTTP_PUT = 'PUT';
    const HTTP_TRACE = 'TRACE';
    

    function __construct()
    {
        $this->serverData = $_SERVER;
        $this->queryParams = $_GET;
        $this->postData = $_POST;
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


    /**
     * {@inheritdoc}
     */
    public function getAllQueryParams():array
    {
        return $this->queryParams;
    }

    
    /**
     * {@inheritdoc}
     */
    public function getQueryParam(string $key, $default=null):mixed
    {
        return $this->queryParams[$key] ?? $default;
    }


    /**
     * {@inheritdoc}
     */
    public function getAllPostData():array
    {
        return $this->postData;
    }


    /**
     * {@inheritdoc}
     */
    public function getPostData(string $key, $default=null):mixed
    {
        return $this->postData[$key] ?? $default;
    }


    /**
     * {@inheritdoc}
     */
    public function getAllServerData():array
    {
        return $this->serverData;
    }

    
    /**
     * {@inheritdoc}
     */
    public function getServerData(string $key, $default=null):mixed
    {
        return $this->serverData[$key] ?? $default;
    }


    /**
     * {@inheritdoc}
     */
    public function getMethod():string
    {
        return $this->getServerData('REQUEST_METHOD', '');
    }


    /**
     * {@inheritdoc}
     */
    public function isConnect():bool
    {
        return strtoupper($this->getMethod()) === self::HTTP_CONNECT;
    }


    /**
     * {@inheritdoc}
     */
    public function isDelete():bool
    {
        return strtoupper($this->getMethod()) === self::HTTP_DELETE;
    }


    /**
     * {@inheritdoc}
     */
    public function isGet():bool
    {
        return strtoupper($this->getMethod()) === self::HTTP_GET;
    }


    /**
     * {@inheritdoc}
     */
    public function isHead():bool
    {
        return strtoupper($this->getMethod()) === self::HTTP_HEAD;
    }


    /**
     * {@inheritdoc}
     */
    public function isOptions():bool
    {
        return strtoupper($this->getMethod()) === self::HTTP_OPTIONS;
    }


    /**
     * {@inheritdoc}
     */
    public function isPatch():bool
    {
        return strtoupper($this->getMethod()) === self::HTTP_PATCH;
    }


    /**
     * {@inheritdoc}
     */
    public function isPost():bool
    {
        return strtoupper($this->getMethod()) === self::HTTP_POST;
    }


    /**
     * {@inheritdoc}
     */
    public function isPut():bool
    {
        return strtoupper($this->getMethod()) === self::HTTP_PUT;
    }


    /**
     * {@inheritdoc}
     */
    public function isTrace():bool
    {
        return strtoupper($this->getMethod()) === self::HTTP_TRACE;
    }
}