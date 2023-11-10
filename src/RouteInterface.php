<?php declare(strict_types=1);

namespace EricSeibt\PiratePHP;
use EricSeibt\PiratePHP\RequestInterface;


interface RouteInterface
{
    public function checkMatch(RequestInterface $request):bool;
}


interface DirectRouteInterface extends RouteInterface
{

}