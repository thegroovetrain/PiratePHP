<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\ResponseInterface;


interface RouteInterface
{
    /**
     * creates a new route.
     * 
     * @return static
     */
    public static function create():static;

    /**
     * returns a clone with given path
     * 
     * @param string    $path
     * @return static
     */
    public function withPath(string $path):static;

    /**
     * returns a clone with given handler
     * 
     * @param callable  $handler
     * @return static
     */
    public function withHandler(callable $handler):static;

    /**
     * returns a clone with given method(s) appended.
     * 
     * @param string    $methods    you may add any number of these.
     * @return static
     */
    public function withMethods(string ...$methods):static;

    /**
     * returns a clone with given middleware added.
     * 
     * @param callable  $middleware     you may add any number of these.
     * @return static
     */
    public function withMiddleware(callable ...$middleware):static;

    /**
     * gets the route's path
     * 
     * @return string
     */
    public function getPath():string;

    /**
     * gets the handler
     * 
     * @return callable|null
     */
    public function getHandler():mixed;

    /**
     * gets the list of methods
     * 
     * @return array
     */
    public function getMethods():array;

    /**
     * gets the list of middleware
     * 
     * @return array
     */
    public function getMiddleware():array;
}