<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <script src="register.js" defer></script>
    <link rel="stylesheet" href="login.css">
</head>

<body>
    <?php if ($error): ?>
        <p class="error-message">
            <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?: "Unknown registration error. Please try again."; ?>
        </p>
    <?php endif; ?>

    <form action="register" method="post">
        <h1>Register</h1> <label for="username">Username:</label>
        <input type="text" id="username" name="username" required autocomplete="username">
        <div id="usernameFeedback"></div>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required autocomplete="new-password">

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required autocomplete="email">

        <input type="hidden" name="action" value="register">
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login">Login here</a>.</p>
</body>

</html>