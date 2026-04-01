<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class SessionMiddleware
{
    public const ATTR_SESSION = '_pirate_session';
    public const ATTR_FLASH = '_pirate_flash';
    public const ATTR_SESSION_WRITES = '_pirate_session_writes';
    public const ATTR_FLASH_WRITES = '_pirate_flash_writes';
    private const SESSION_FLASH_KEY = '_flash';

    private array $cookieParams;


    private function __construct()
    {
        $this->cookieParams = [
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => false,
        ];
    }


    public static function create(): static
    {
        return new static();
    }


    public function withCookieParams(array $params): static
    {
        $new = clone $this;
        $new->cookieParams = array_merge($new->cookieParams, $params);
        return $new;
    }


    public function __invoke(RequestInterface $request, callable $next): ResponseInterface
    {
        // Try to start session with secure defaults
        try {
            $serverData = $request->getServerData();
            $params = $this->cookieParams;
            $params['secure'] = isset($serverData['HTTPS']);
            session_set_cookie_params($params);
            session_start();
        } catch (\Throwable $e) {
            // If session fails, continue without session
            return $next($request);
        }

        // Read session data into PhpSession object
        $sessionData = $_SESSION ?? [];
        $session = new PhpSession($sessionData);

        // Read flash data from prior session, then clear it
        $flashData = $_SESSION[self::SESSION_FLASH_KEY] ?? [];
        unset($_SESSION[self::SESSION_FLASH_KEY]);
        $flash = new PhpSession($flashData);

        // Attach session and flash to request
        $request = $request->withAttribute(self::ATTR_SESSION, $session);
        $request = $request->withAttribute(self::ATTR_FLASH, $flash);

        // Call next middleware
        $response = $next($request);

        // Read write-back attributes from response
        $sessionWrites = $response->getAttribute(self::ATTR_SESSION_WRITES);
        $flashWrites = $response->getAttribute(self::ATTR_FLASH_WRITES);

        // Write session data
        if (is_array($sessionWrites)) {
            foreach ($sessionWrites as $key => $value) {
                $_SESSION[$key] = $value;
            }
        }

        // Write flash data for next request
        if (is_array($flashWrites)) {
            $_SESSION[self::SESSION_FLASH_KEY] = $flashWrites;
        }

        // Close session
        session_write_close();

        return $response;
    }
}
