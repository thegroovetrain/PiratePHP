<h1>PiratePHP v2 Demo</h1>

<div class="card">
    <h2>Demo Routes</h2>
    <ul>
        <li><code>GET /</code> — This page (template rendered)</li>
        <li><code>GET /form</code> — HTML form with flash messages</li>
        <li><code>POST /form</code> — Form submission with validation</li>
        <li><code>GET /user/:id</code> — Dynamic route params (try <a href="/user/42">/user/42</a>)</li>
        <li><code>GET /about</code> — Named routes with urlFor() link generation</li>
        <li><code>GET /api/users</code> — JSON API endpoint (route group)</li>
    </ul>
</div>

<div class="card">
    <h2>Active Middleware</h2>
    <ul>
        <li>ErrorMiddleware — catches exceptions, returns 500</li>
        <li>LoggingMiddleware — logs requests to <code>examples/logs/app.log</code></li>
        <li>Sessions &amp; flash messages — built into Request/Response</li>
    </ul>
</div>
