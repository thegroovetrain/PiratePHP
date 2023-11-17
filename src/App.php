<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class App implements AppInterface
{
    use HasMiddleware;


    private array $routers;


    private function __construct()
    {
        $this->routers = [];
    }


    public static function create():static
    {
        return new static();
    }


    public function run():void
    {
        $request = Request::create();
        $response = $this->handle($request);
        $response->send();
    }


    private function handleRequest(RequestInterface $request):ResponseInterface
    {
        // sort all of the routers by the length of the basepath so that longer ones are checked first.
        $routers = $this->getSortedRouters();
        foreach ($routers as $router) {
            if ($router->getBasePath() === substr($request->getUri(), 0, strlen($router->getBasePath()))) {
                return $router->handle($request);
            }
        }
        return Response::create()->withStatus(404);
    }


    public function withRouter(RouterInterface ...$router):static
    {
        $new = clone $this;
        $new->routers = [...$this->routers, ...$router];
        return $new;
    }


    public function getRouters():array
    {
        return $this->routers;
    }

    
    public function getSortedRouters():array
    {
        $routers = $this->routers;
        usort($routers, function (RouterInterface $a, RouterInterface $b) {
            return strlen($b->getBasePath()) <=> strlen($a->getBasePath());
        });
        return $routers;
    }
}