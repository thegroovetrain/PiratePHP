<?php declare(strict_types=1);

namespace EricSeibt\PiratePHP;
use EricSeibt\PiratePHP\RequestInterface;


interface RouteInterface
{
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