<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <meta name="color-scheme" content="light dark">
    <script>
        {
            const theme = localStorage.getItem("theme") || "system";
            if (theme === "dark" || (theme === "system" && window.matchMedia("(prefers-color-scheme: dark)").matches)) {
                document.documentElement.setAttribute("data-theme", "dark");
            } else {
                document.documentElement.setAttribute("data-theme", "light");
            }
        }
    </script>
    <link rel="stylesheet" href="login.css">
    <script src="theme.js" defer></script>
</head>

<body>
    <nav class="page-nav">
        <a href="dashboard">&#8592; Dashboard</a>
        <button id="themeToggle" type="button" class="theme-toggle" aria-label="Toggle Theme">
            <svg class="sun-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="5"></circle>
                <line x1="12" y1="1" x2="12" y2="3"></line>
                <line x1="12" y1="21" x2="12" y2="23"></line>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                <line x1="1" y1="12" x2="3" y2="12"></line>
                <line x1="21" y1="12" x2="23" y2="12"></line>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
            <svg class="moon-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
        </button>
    </nav>

    <?php if ($error): ?>
        <p class="error-message">
            <?php echo htmlspecialchars($errorMessage ?: 'Invalid username or password.', ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        <p class="success-message">
            <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <?php if ($isLoggedIn) { ?>
        <form action="login" method="post">
            <h1>Login successful</h1>
            <p class="form-text">You are now logged in as
                <strong><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></strong>.
            </p>
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="btn-danger">Logout</button>
        </form>
    <?php } else { ?>
        <form action="login" method="post">
            <h1>Login</h1>
            <label for="username">Username or Email:</label>
            <input type="text" id="username" name="username" required autocomplete="username">

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <p class="forgot-password-link"><a href="forgot-password">Forgot password?</a></p>

            <input type="hidden" name="action" value="login">
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register">Register here</a>.</p>
    <?php } ?>
</body>

</html>