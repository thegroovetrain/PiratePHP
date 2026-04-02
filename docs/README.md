# PiratePHP Documentation

The immutable PHP micro-framework. Functional composition all the way down.

## Quick Start

[Quick Start Guide](guide.md) -- Install PiratePHP and build your first app in 5 minutes.

## Core Concepts

| Topic | Document |
|-------|----------|
| Getting Started | [getting-started.md](getting-started.md) |
| Routing | [routing.md](routing.md) |
| Request | [request.md](request.md) |
| Response | [response.md](response.md) |
| Sessions & Flash | [sessions.md](sessions.md) |

## Middleware

See the [Middleware Overview](middleware/README.md) for how the pipeline works and how to write custom middleware.

| Middleware | Document |
|-----------|----------|
| Error Handling | [middleware/error.md](middleware/error.md) |
| Logging | [middleware/logging.md](middleware/logging.md) |
| Static Files | [middleware/static-files.md](middleware/static-files.md) |
| Rate Limiting | [middleware/rate-limiting.md](middleware/rate-limiting.md) |
| Content Negotiation | [middleware/content-negotiation.md](middleware/content-negotiation.md) |

## More

| Topic | Document |
|-------|----------|
| Templates | [templates.md](templates.md) |
| Cookies | [cookies.md](cookies.md) |
| Testing | [testing.md](testing.md) |

## Reference

| Topic | Document |
|-------|----------|
| API Reference | [api.md](api.md) |
| Architecture | [architecture.md](architecture.md) |

## Example App

A working demo app lives in the [examples/](../examples/) directory. It covers routing, form handling, flash messages, JSON APIs, sessions, static files, named routes, and template rendering.

```bash
cd examples
php -S localhost:8000 -t public/
```
