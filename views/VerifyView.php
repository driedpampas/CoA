<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verified</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
    <nav class="page-nav">
        <a href="dashboard">&#8592; Dashboard</a>
    </nav>

    <?php if (!empty($verifyError)): ?>
        <p class="error-message"><?php echo htmlspecialchars($verifyError, ENT_QUOTES, 'UTF-8'); ?></p>
        <p>Your verification link may have expired. Please register again or request a new link.</p>
    <?php else: ?>
        <form>
            <h1>Email Verified</h1>
            <p style="margin-bottom: 20px; color: #333; line-height: 1.5;">
                Your email has been verified successfully, <strong><?php echo htmlspecialchars($verifiedUsername ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>.
                You can now log in to your account.
            </p>
            <a href="login" style="display:block;width:100%;background-color:#1a1a2e;color:white;border:none;padding:12px;font-size:16px;font-weight:600;border-radius:6px;cursor:pointer;text-align:center;text-decoration:none;margin-top:10px;box-sizing:border-box;">Go to Login</a>
        </form>
    <?php endif; ?>
</body>

</html>
