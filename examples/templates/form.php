<h1>Form Demo</h1>

<?php if (!empty($flash_message)): ?>
    <div class="flash">
        <?= htmlspecialchars($flash_message) ?>
    </div>
<?php endif; ?>

<?php if (!empty($flash_error)): ?>
    <div class="flash error">
        <?= htmlspecialchars($flash_error) ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2>Submit a Form</h2>
    <p>This demonstrates POST handling, validation, flash messages, and redirect.</p>

    <form method="POST" action="/form">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" placeholder="Your name">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="you@example.com">

        <button type="submit">Submit</button>
    </form>
</div>
