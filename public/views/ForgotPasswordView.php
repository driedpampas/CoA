<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
        <button id="themeToggle" class="theme-toggle" aria-label="Toggle Theme">
            <svg class="sun-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
            <svg class="moon-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
        </button>
    </nav>

    <?php if ($error): ?>
        <p class="error-message">
            <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        <p class="success-message">
            <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <form action="forgot-password" method="post">
        <h1>Forgot Password</h1>
        <p class="form-text-muted">
            Enter your email address and we'll send you a link to reset your password.
        </p>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required autocomplete="email">

        <input type="hidden" name="action" value="forgot-password">
        <button type="submit">Send Reset Link</button>
    </form>
    <p>Remember your password? <a href="login">Login here</a>.</p>
</body>

</html>
