<!DOCTYPE html>
<html lang="en">
<body style="margin:0;background:#f1f5f9;font-family:Arial,sans-serif;color:#0f172a;padding:24px;">
    <main style="max-width:560px;margin:0 auto;background:#fff;border-radius:16px;padding:32px;">
        <p style="margin:0 0 20px;font-size:14px;color:#475569;">Dear {{ $recipientEmail }},</p>
        <h1 style="margin:0 0 12px;font-size:22px;">Password reset verification</h1>
        <p style="font-size:15px;line-height:1.6;">Use this code to reset your BrakeSafe account password:</p>
        <p style="margin:24px 0;padding:16px;text-align:center;background:#f1f5f9;border-radius:10px;font-size:30px;font-weight:700;letter-spacing:8px;">{{ $verificationCode }}</p>
        <p style="font-size:14px;line-height:1.6;color:#475569;">This code expires in 10 minutes and can be used once. If you did not request a password reset, you can ignore this email.</p>
        <p style="margin:28px 0 0;font-size:14px;color:#475569;">BrakeSafe</p>
    </main>
</body>
</html>
