<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
    <nav class="page-nav">
        <a href="dashboard">&#8592; Dashboard</a>
    </nav>

    <?php if (!empty($resetError)): ?>
        <p class="error-message"><?php echo htmlspecialchars($resetError, ENT_QUOTES, 'UTF-8'); ?></p>
        <p>Request a new <a href="forgot-password">password reset link</a>.</p>
    <?php else: ?>

        <?php if ($error): ?>
            <p class="error-message">
                <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <form action="reset-password" method="post">
            <h1>Reset Password</h1>
            <p style="margin-bottom: 20px; color: #616a7e; font-size: 14px;">
                Enter your new password below.
            </p>

            <label for="password">New Password:</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">

            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="reset-password">
            <button type="submit">Reset Password</button>
        </form>
    <?php endif; ?>

    <p><a href="login">Back to login</a></p>
</body>

</html>
