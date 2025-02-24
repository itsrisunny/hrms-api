<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <p>Dear {{ $employee->FirstName }},</p>
    <p>You requested a password reset. Click the link below to reset your password:</p>
    <p><a href="{{ $resetLink }}">Reset Password</a></p>
    <p>If you did not request a password reset, please ignore this email.</p>
    <p>Best regards,</p>
    <p>APISOD</p>
</body>
</html>
