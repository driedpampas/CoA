<?php $error = $error ?? false; $errorMessage = $errorMessage ?? ''; $isLoggedIn = $isLoggedIn ?? false; $username = $username ?? ''; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
<?php if ($error): ?>
    <p class="error-message">Invalid username or password. The credentials are wrong.</p>
<?php endif; ?>

<?php if ($isLoggedIn) { ?>
    <form action="login" method="post">
        <h1>Login successful</h1>
        <p style="margin-bottom: 20px;">You are now logged in as <strong><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
        <input type="hidden" name="action" value="logout">
        <button type="submit" style="background-color: #e74c3c;">Logout</button> </form>
<?php } else { ?>
    <form action="login" method="post">
        <h1>Login</h1>
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required autocomplete="username">

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <input type="hidden" name="action" value="login">
        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="register">Register here</a>.</p>
<?php } ?>
</body>

</html>