<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\ResponseInterface;


interface RouteInterface
{
    /**
     * returns true if the requested resource matches this route's path and methods
     * 
     * returns a response, either from the matched handler function, or
     * a 404 response if the path is not matched
     * a 405 response if the path is matched but the method is not
     * 
     * @param RequestInterface  $request
     * @return ResponseInterface
     */
    public function handleRequest(RequestInterface $request):ResponseInterface;
     
    /**
     * adds handler for connect method
     * 
     * @param callable  $handler    handler function for this method
     * @return static   returns the updated route with handler added
     */
    public function connect(callable $handler):static;

    /**
     * adds handler for delete method
     * 
     * @param callable  $handler    handler function for this method
     * @return static   returns the updated route with handler added
     */
    public function delete(callable $handler):static;

    /**
     * adds handler for get method
     * 
     * @param callable  $handler    handler function for this method
     * @return static   returns the updated route with handler added
     */
    public function get(callable $handler):static;

    /**
     * adds handler for head method
     * 
     * @param callable  $handler    handler function for this method
     * @return static   returns the updated route with handler added
     */
    public function head(callable $handler):static;

    /**
     * adds handler for options method
     * 
     * @param callable  $handler    handler function for this method
     * @return static   returns the updated route with handler added
     */
    public function options(callable $handler):static;

    /**
     * adds handler for patch method
     * 
     * @param callable  $handler    handler function for this method
     * @return static   returns the updated route with handler added
     */
    public function patch(callable $handler):static;

    /**
     * adds handler for post method
     * 
     * @param callable  $handler    handler function for this method
     * @return static   returns the updated route with handler added
     */
    public function post(callable $handler):static;

    /**
     * adds handler for put method
     * 
     * @param callable  $handler    handler function for this method
     * @return static   returns the updated route with handler added
     */
    public function put(callable $handler):static;

    /**
     * adds handler for trace method
     * 
     * @param callable  $handler    handler function for this method
     * @return static   returns the updated route with handler added
     */
    public function trace(callable $handler):static;
}