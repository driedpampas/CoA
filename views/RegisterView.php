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
    <form action="index.php?page=register" method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required autocomplete="username"><br>
        <div id="usernameFeedback"></div><br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required autocomplete="new-password"><br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required autocomplete="email"><br><br>
        <input type="hidden" name="action" value="register">
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="index.php?page=login">Login here</a>.</p>
</body>

</html>