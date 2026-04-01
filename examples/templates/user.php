<h1>User Profile</h1>

<div class="card">
    <h2>User #<?= htmlspecialchars($id) ?></h2>
    <p>This page demonstrates dynamic route parameters.</p>
    <p>The <code>:id</code> parameter was extracted from the URL and is available via
       <code>$request->getAttribute('id')</code>.</p>
    <p>Try changing the number in the URL: <a href="/user/1">/user/1</a>,
       <a href="/user/99">/user/99</a>, <a href="/user/1337">/user/1337</a></p>
</div>
