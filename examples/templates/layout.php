<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'PiratePHP Demo') ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/form">Form</a>
        <a href="/user/42">User 42</a>
        <a href="/about">About</a>
        <a href="/api/users">API: Users</a>
    </nav>

    <?= $content ?? '' ?>

    <footer>
        Powered by PiratePHP v2
    </footer>
</body>
</html>
