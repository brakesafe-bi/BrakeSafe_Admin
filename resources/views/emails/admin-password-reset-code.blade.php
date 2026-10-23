<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BrakeSafe Password Reset Code</title>
</head>
<body style="font-family: Arial, sans-serif; background: #0b1220; color: #e2e8f0; margin: 0; padding: 0;">
    <table align="center" width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 24px auto; background: #0f1830; border: 1px solid #1a2540; border-radius: 12px;">
        <tr>
            <td style="padding: 24px; text-align: center;">
                <h1 style="margin: 0 0 12px; font-size: 24px; color: #22d3ee;">BrakeSafe Password Reset</h1>
                <p style="margin: 0 0 24px; color: #cbd5e1;">Hi {{ $adminName }},</p>
                <p style="margin: 0 0 20px; color: #e2e8f0; font-size: 16px;">Use this verification code to reset your administrator password. It expires in 10 minutes.</p>
                <div style="display: inline-block; padding: 18px 24px; background: #111c34; border: 1px solid #1a2540; border-radius: 12px; font-size: 32px; letter-spacing: 0.2em; color: #22d3ee; font-weight: 700;">
                    {{ $code }}
                </div>
                <p style="margin: 24px 0 0; color: #94a3b8; font-size: 14px;">If you did not request this code, you can ignore this email.</p>
            </td>
        </tr>
    </table>
</body>
</html>
