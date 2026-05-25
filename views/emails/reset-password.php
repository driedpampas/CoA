<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f4f7f6;padding:40px 20px;">
<div style="max-width:480px;margin:0 auto;background:#fff;border-radius:8px;padding:30px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
    <h2 style="color:#1a1a2e;margin-bottom:16px;">Reset your password</h2>
    <p style="color:#333;line-height:1.5;">Hi <strong><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></strong>,</p>
    <p style="color:#333;line-height:1.5;">Click the button below to reset your password. This link expires in 1 hour.</p>
    <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" style="display:inline-block;background:#1a1a2e;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:600;margin:20px 0;">Reset Password</a>
    <p style="color:#999;font-size:12px;">If you did not request a password reset, you can ignore this email.</p>
</div>
</body>
</html>
