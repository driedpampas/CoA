<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
    <nav class="page-nav">
        <a href="dashboard">&#8592; Dashboard</a>
    </nav>

    <?php if ($error): ?>
        <p class="error-message"><?php echo htmlspecialchars($errorMessage ?: 'Invalid username or password.', ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        <p style="background-color:#f0fff0;color:#1a7a1a;border:1px solid #87c687;padding:12px;border-radius:6px;width:100%;max-width:400px;margin-bottom:20px;text-align:center;font-size:14px;order:1;">
            <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <?php if ($isLoggedIn) { ?>
        <form action="login" method="post">
            <h1>Login successful</h1>
            <p style="margin-bottom: 20px;">You are now logged in as
                <strong><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></strong>.
            </p>
            <input type="hidden" name="action" value="logout">
            <button type="submit" style="background-color: #e74c3c;">Logout</button>
        </form>
    <?php } else { ?>
        <form action="login" method="post">
            <h1>Login</h1>
            <label for="username">Username or Email:</label>
            <input type="text" id="username" name="username" required autocomplete="username">

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <p style="margin-top:-12px;margin-bottom:16px;font-size:13px;"><a href="forgot-password">Forgot password?</a></p>

            <input type="hidden" name="action" value="login">
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register">Register here</a>.</p>
    <?php } ?>
</body>

</html>