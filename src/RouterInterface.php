<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


interface RouterInterface
{
    /**
     * creates a new router instance and returns it
     * 
     * @return static
     */
    public static function create():static;

    /**
     * returns a clone of this router with given basepath
     * 
     * @param string|null    $basepath  defaults to '/'
     * @return static
     */
    public function withBasePath(string $basepath):static;

    /**
     * returns a clone of this router with given routes added to its list
     * 
     * @param RouteInterface    ...$route   you can add as many as you want
     * @return static
     */
    public function withRoute(RouteInterface ...$route):static;

    /**
     * gets the basepath
     * 
     * @return string
     */
    public function getBasePath():string;

    /**
     * gets the array of routes
     * 
     * @return array
     */
    public function getRoutes():array;
}