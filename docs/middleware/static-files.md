# StaticFileMiddleware

StaticFileMiddleware serves static files (CSS, JavaScript, images, fonts, etc.) directly from a directory on disk. If the request URI matches a real file inside the configured base directory, it returns the file contents with the appropriate `Content-Type` header. If the file does not exist or the path is suspicious (dotfiles, directory traversal), it passes the request through to `$next`.

## Configuration

**Constructor:**

```php
StaticFileMiddleware::create(string $baseDir, array $mimeTypes = []): static
```

- `$baseDir` -- absolute path to the directory containing static files
- `$mimeTypes` -- optional associative array of extension-to-MIME-type mappings that are merged with the built-in defaults

**Configuration methods:**

```php
public function withBaseDir(string $baseDir): static
public function withMimeTypes(array $mimeTypes): static
```

`withMimeTypes()` merges new mappings into the existing set (including defaults).

**Default MIME types:**

| Extension | Content-Type |
|---|---|
| `html`, `htm` | `text/html` |
| `css` | `text/css` |
| `js` | `application/javascript` |
| `json` | `application/json` |
| `xml` | `application/xml` |
| `txt` | `text/plain` |
| `csv` | `text/csv` |
| `png` | `image/png` |
| `jpg`, `jpeg` | `image/jpeg` |
| `gif` | `image/gif` |
| `svg` | `image/svg+xml` |
| `ico` | `image/x-icon` |
| `webp` | `image/webp` |
| `pdf` | `application/pdf` |
| `woff` | `font/woff` |
| `woff2` | `font/woff2` |
| `ttf` | `font/ttf` |
| `eot` | `application/vnd.ms-fontobject` |
| `mp3` | `audio/mpeg` |
| `mp4` | `video/mp4` |
| `webm` | `video/webm` |
| `zip` | `application/zip` |

Unrecognized extensions fall back to `application/octet-stream`.

## Usage

```php
use thegroovetrain\PiratePHP\StaticFileMiddleware;

$static = StaticFileMiddleware::create(__DIR__ . '/public')
    ->withMimeTypes([
        'wasm' => 'application/wasm',
        'avif' => 'image/avif',
    ]);

$app = App::create()
    ->withMiddleware($static)
    ->withRouter($router);
```

With this setup, a request to `/css/style.css` will serve the file at `./public/css/style.css` if it exists.

## What It Reads / What It Writes

**Reads from the request:**

- `$request->getUri()` -- the URI path, used to locate the file on disk

**Writes to the response (when a file is found):**

- `Content-Type` header set to the MIME type for the file extension
- `Content-Length` header set to the file size in bytes
- Body is a closure that calls `readfile()` for streaming output

**When no file is found:** passes through to `$next` without modification.

## Edge Cases and Gotchas

- **Path traversal protection:** The middleware resolves the real path using `realpath()` and verifies it starts with the base directory's real path. Requests like `/../../etc/passwd` will not escape the base directory.
- **Dotfile blocking:** Any URI segment starting with `.` causes the middleware to skip and pass through to `$next`. This protects `.htaccess`, `.env`, `.git/` and other sensitive hidden files. A request to `/assets/.secret/data.json` will not be served.
- **Streaming body:** The response body is a closure, not a string. The file is read via `readfile()` when `Response::send()` is called. This means the file contents are not held in memory.
- **Content-Length header:** The file size is measured with `filesize()` and set as the `Content-Length` header, allowing clients to display download progress.
- **Directories are not served.** If the URI points to a directory (even with an `index.html` inside it), the middleware passes through. It only serves regular files.
- **No caching headers.** StaticFileMiddleware does not set `Cache-Control`, `ETag`, or `Last-Modified` headers. Add those via a custom middleware wrapper if needed.
- **Base directory must exist.** If `realpath($baseDir)` returns `false`, every request will fall through to `$next`.

## Related

- [Middleware Overview](README.md) -- how the onion pipeline works, middleware ordering, and custom middleware examples
