<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


interface AppInterface
{
    /**
     * creates and returns a new app
     * 
     * @return static
     */
    public static function create():static;

    /**
     * returns a clone of the app with the given router added to its list.
     * 
     * @param RouterInterface   $router
     * @return static
     */
    public function withRouter(RouterInterface $router):static;
}