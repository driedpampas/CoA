<?php $error = $error ?? false; $errorMessage = $errorMessage ?? ''; $isLoggedIn = $isLoggedIn ?? false; $username = $username ?? ''; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <script src="../public/register.js" defer></script>
    <link rel="stylesheet" href="../public/register.css">
</head>

<body>
    <?php if ($error): ?>
        <p style="color: red;">
            <?php echo $errorMessage ?: "Unknown registration error. Please try again."; ?>
        </p>
    <?php endif; ?>

    <h1>Register</h1>
    <form action="../controllers/AccountsController.php" method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required autocomplete="username"><br>
        <div id="usernameFeedback"></div><br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required autocomplete="current-password"><br><br>
        <input type="hidden" name="action" value="register">
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="../controllers/AccountsController.php?page=login">Login here</a>.</p>
</body>

</html>