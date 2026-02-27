<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; }
        .header { background: #1a1a2e; color: #fff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; letter-spacing: 1px; }
        .body { padding: 32px 24px; color: #333; }
        .body h2 { color: #1a1a2e; margin-top: 0; font-size: 20px; }
        .info-box { background: #f9f9f9; padding: 16px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #e67e22; }
        .info-box p { margin: 4px 0; font-size: 13px; color: #555; }
        .btn-wrap { text-align: center; margin: 28px 0; }
        .btn {
            display: inline-block;
            background: #e67e22;
            color: #fff;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .fallback { background: #f4f4f4; padding: 12px 16px; border-radius: 6px; margin: 16px 0; word-break: break-all; font-size: 12px; color: #555; }
        .divider { border: none; border-top: 1px solid #eee; margin: 20px 0; }
        .warning { font-size: 12px; color: #888; margin-top: 8px; }
        .footer { background: #f4f4f4; padding: 16px; text-align: center; color: #888; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>XTREMEFIT</h1>
        </div>
        <div class="body">
            <h2>Reset Your Password</h2>
            <p>Hi {{ $name }},</p>
            <p>We received a request to reset the password for your XTREMEFIT account. Click the button below to choose a new password.</p>

            <div class="btn-wrap">
                <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
            </div>

            <div class="info-box">
                <p><strong>This link will expire in {{ $expireMinutes }} minutes.</strong></p>
                <p>If you did not request a password reset, no action is needed — your password will remain unchanged.</p>
            </div>

            <hr class="divider">

            <p class="warning">If the button above doesn't work, copy and paste the link below into your browser:</p>
            <div class="fallback">{{ $resetUrl }}</div>

            <p>— The XTREMEFIT Team</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} XTREMEFIT. All rights reserved.
        </div>
    </div>
</body>
</html>
