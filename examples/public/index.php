<?php declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use thegroovetrain\PiratePHP\App;
use thegroovetrain\PiratePHP\Router;
use thegroovetrain\PiratePHP\Route;
use thegroovetrain\PiratePHP\RouteGroup;
use thegroovetrain\PiratePHP\Request;
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\Response;
use thegroovetrain\PiratePHP\ResponseInterface;
use thegroovetrain\PiratePHP\PhpRenderer;
use thegroovetrain\PiratePHP\ErrorMiddleware;
use thegroovetrain\PiratePHP\LoggingMiddleware;
use thegroovetrain\PiratePHP\FileLogger;
use thegroovetrain\PiratePHP\SessionMiddleware;
use thegroovetrain\PiratePHP\StaticFileMiddleware;

// --- Setup ---

$renderer = PhpRenderer::create(__DIR__ . '/../templates');
$logger = FileLogger::create(__DIR__ . '/../logs/app.log');

/**
 * Helper: render a template inside the layout.
 */
function renderPage(PhpRenderer $renderer, string $template, array $data = [], string $title = 'PiratePHP Demo'): string
{
    $content = $renderer->render($template, $data);
    return $renderer->render('layout.php', ['content' => $content, 'title' => $title]);
}

// --- Routes ---

$homeRoute = Route::create()
    ->withPath('/')
    ->withMethods('GET')
    ->withName('home')
    ->withHandler(function (RequestInterface $request) use ($renderer): ResponseInterface {
        $html = renderPage($renderer, 'home.php', [], 'PiratePHP Demo - Home');
        return Response::create()->withBody($html)->withHeader('Content-Type', 'text/html');
    });

$formGetRoute = Route::create()
    ->withPath('/form')
    ->withMethods('GET')
    ->withName('form')
    ->withHandler(function (RequestInterface $request) use ($renderer): ResponseInterface {
        $flash = $request->getAttribute('_pirate_flash');
        $data = [];
        if ($flash !== null) {
            $message = $flash->get('message');
            $error = $flash->get('error');
            if ($message !== null) {
                $data['flash_message'] = $message;
            }
            if ($error !== null) {
                $data['flash_error'] = $error;
            }
        }
        $html = renderPage($renderer, 'form.php', $data, 'PiratePHP Demo - Form');
        return Response::create()->withBody($html)->withHeader('Content-Type', 'text/html');
    });

$formPostRoute = Route::create()
    ->withPath('/form')
    ->withMethods('POST')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $name = $request->getPostDatum('name', '');
        $email = $request->getPostDatum('email', '');

        // Simple validation
        if (trim($name) === '' || trim($email) === '') {
            return Response::redirect('/form')
                ->withAttribute('_pirate_flash_writes', [
                    'error' => 'Name and email are required.',
                ]);
        }

        return Response::redirect('/form')
            ->withAttribute('_pirate_flash_writes', [
                'message' => "Thanks, {$name}! We received your submission.",
            ]);
    });

$userRoute = Route::create()
    ->withPath('/user/:id')
    ->withMethods('GET')
    ->withName('user')
    ->withHandler(function (RequestInterface $request) use ($renderer): ResponseInterface {
        $id = $request->getAttribute('id');
        $html = renderPage($renderer, 'user.php', ['id' => $id], "PiratePHP Demo - User #{$id}");
        return Response::create()->withBody($html)->withHeader('Content-Type', 'text/html');
    });

// --- Router (with named routes for urlFor) ---

$router = Router::create()
    ->withRoute($homeRoute, $formGetRoute, $formPostRoute, $userRoute);

// About route uses $router for urlFor(), so we define it after the router has the named routes
$aboutRoute = Route::create()
    ->withPath('/about')
    ->withMethods('GET')
    ->withName('about')
    ->withHandler(function (RequestInterface $request) use ($renderer, &$router): ResponseInterface {
        $homeUrl = $router->urlFor('home');
        $userUrl = $router->urlFor('user', ['id' => '7']);
        $formUrl = $router->urlFor('form');
        $html = renderPage($renderer, 'about.php', [
            'home_url' => $homeUrl,
            'user_url' => $userUrl,
            'form_url' => $formUrl,
        ], 'PiratePHP Demo - About');
        return Response::create()->withBody($html)->withHeader('Content-Type', 'text/html');
    });

$router = $router->withRoute($aboutRoute);

// --- API Route Group ---

$apiUsersRoute = Route::create()
    ->withPath('/users')
    ->withMethods('GET')
    ->withName('api.users')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $users = [
            ['id' => 1, 'name' => 'Blackbeard', 'email' => 'blackbeard@pirate.ship'],
            ['id' => 2, 'name' => 'Anne Bonny', 'email' => 'anne@pirate.ship'],
            ['id' => 3, 'name' => 'Calico Jack', 'email' => 'calico@pirate.ship'],
        ];
        return Response::json($users);
    });

$apiGroup = RouteGroup::create()
    ->withPrefix('/api')
    ->withRoute($apiUsersRoute);

$router = $router->withGroup($apiGroup);

// --- App with Middleware ---

$app = App::create()
    ->withRouter($router)
    ->withMiddleware(
        ErrorMiddleware::create(),
        LoggingMiddleware::create($logger),
        SessionMiddleware::create(),
        StaticFileMiddleware::create(__DIR__)
    );

$app->run();
