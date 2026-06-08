<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Your Email</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
    <nav class="page-nav">
        <a href="dashboard">&#8592; Dashboard</a>
    </nav>

    <form>
        <h1>Check Your Email</h1>
        <p style="margin-bottom: 20px; color: #333; line-height: 1.5;">
            <?php echo htmlspecialchars($successMessage ?: 'We sent a verification link to your email address. Please check your inbox and click the link to activate your account.', ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <p style="margin-bottom: 20px; color: #616a7e; font-size: 13px;">
            Didn't receive the email? Check your spam folder, or <a href="register">try registering again</a>.
        </p>
        <a href="login" class="btn-submit">Go to Login</a>
    </form>
</body>

</html>
