<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
    <nav class="page-nav">
        <a href="dashboard">&#8592; Dashboard</a>
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
        <p style="margin-bottom: 20px; color: #616a7e; font-size: 14px;">
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
