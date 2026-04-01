<h1>About</h1>

<div class="card">
    <h2>Named Routes &amp; urlFor()</h2>
    <p>This page demonstrates named routes and link generation with <code>urlFor()</code>.</p>

    <h2>Generated Links</h2>
    <ul>
        <li>Home: <a href="<?= htmlspecialchars($home_url) ?>"><?= htmlspecialchars($home_url) ?></a></li>
        <li>User 7: <a href="<?= htmlspecialchars($user_url) ?>"><?= htmlspecialchars($user_url) ?></a></li>
        <li>Form: <a href="<?= htmlspecialchars($form_url) ?>"><?= htmlspecialchars($form_url) ?></a></li>
    </ul>

    <p>These links were generated using <code>$router->urlFor('route.name', ['id' => '7'])</code>
       instead of hardcoding paths.</p>
</div>
