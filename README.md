# PiratePHP

## Patterns

### Your First PiratePHP App
$app = App::create()
    ->withRouter(Router::create()
        ->withRoute(Route::create()
            ->withPath('/')
            ->withMethods('GET')
            ->withHandler(function () {
                return Response::create()
                    ->withBody('Hello, World!')
                    ->withHeader('Foo-Bar', 'xyzzy');
            }
        )
    )
);
$app->run();


### subrouters
$adminRouter = Router::create()
    ->withBasePath('/admin')
    ->withMiddleware('authRequired')
    ->withRoute(...);

$app = $app->withRouter($adminRouter);


### composable components
$privateRoute = Route::create()->withMiddleware('authRequired');
$adminDashboardRoute = $privateRoute
    ->withPath('/admin/dashboard')
    ->withMethod('GET')
    ->withHandler(...);

$405response = Response::create()->withStatus(405)->withBody('405');


### Getting the request and/or data from it
$homeRoute = Route::create()
    ->withPath('/')
    ->withMethods('GET')
    ->withHandler(function () {
        $request = Request::receive();
        $query = $request->getQueryParams;
    });

$customerInfoRoute = Route::create()
    ->withPath('/customer/:id')
    ->withMethods('GET', 'POST')
    ->withHandler(function ($id) {
        if(Request::receive()->getMethod() === 'POST') {
            $postData = Request::receive()->getPostData();
            if($id === '100') {
                return Response::create()->withBody('Congratulations! You are number 100!');
            }
            return Response::create()->withBody('You are not number 100.');
        }
    });