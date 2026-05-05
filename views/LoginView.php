<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>

<body>
    <?php if ($error): ?>
        <p style="color: red;">Invalid username or password. The credentials are wrong.</p>
    <?php endif; ?>

    <?php if ($isLoggedIn) { ?>
        <h1>Login successful</h1>
        <p>You are now logged in as <?php echo $username; ?>.</p>
        <form action="../controllers/AccountsController.php" method="post">
            <input type="hidden" name="action" value="logout">
            <button type="submit">Logout</button>
        </form>
    <?php } else { ?>
        <h1>Login</h1>
        <form action="../controllers/AccountsController.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required autocomplete="username"><br><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required autocomplete="current-password"><br><br>
            <input type="hidden" name="action" value="login">
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="../controllers/AccountsController.php?page=register">Register here</a>.</p>
    <?php } ?>
</body>

</html>