<?php declare(strict_types=1);

namespace EricSeibt\PiratePHP;
use EricSeibt\PiratePHP\{Request, RequestInterface};


class Route implements RouteInterface
{
    private string $path;
    private string $httpMethodContext = 'ALL';
    private array $middleware = [];
    private array $handlers = [];


    function __construct($path)
    {
        $this->path = $path;
    }


    /**
     * adds the handler to the given method and then returns the updated instance
     * 
     * @param string    $method     the http method
     * @param callable  $handler    the method handler
     * @return static   the updated instance of itself
     */
    private function addMethodHandler(string $method, callable $handler):static
    {
        $this->httpMethodContext = $method;
        $this->handlers[$method] = $handler;
        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function connect(callable $handler):static { 
        return $this->addMethodHandler(Request::HTTP_CONNECT, $handler); 
    }


    /**
     * {@inheritdoc}
     */
    public function delete(callable $handler):static { 
        return $this->addMethodHandler(Request::HTTP_DELETE, $handler); 
    }


    /**
     * {@inheritdoc}
     */
    public function get(callable $handler):static { 
        return $this->addMethodHandler(Request::HTTP_GET, $handler); 
    }


    /**
     * {@inheritdoc}
     */
    public function head(callable $handler):static { 
        return $this->addMethodHandler(Request::HTTP_HEAD, $handler); 
    }

    
    /**
     * {@inheritdoc}
     */
    public function options(callable $handler):static { 
        return $this->addMethodHandler(Request::HTTP_OPTIONS, $handler); 
    }


    /**
     * {@inheritdoc}
     */
    public function patch(callable $handler):static { 
        return $this->addMethodHandler(Request::HTTP_PATCH, $handler); 
    }


    /**
     * {@inheritdoc}
     */
    public function post(callable $handler):static { 
        return $this->addMethodHandler(Request::HTTP_POST, $handler); 
    }


    /**
     * {@inheritdoc}
     */
    public function put(callable $handler):static { 
        return $this->addMethodHandler(Request::HTTP_PUT, $handler); 
    }


    /**
     * {@inheritdoc}
     */
    public function trace(callable $handler):static { 
        return $this->addMethodHandler(Request::HTTP_TRACE, $handler); 
    }
}