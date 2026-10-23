<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your BrakeSafe access request</title>
</head>
<body style="margin: 0; padding: 24px 12px; background: #f1f5f9; color: #0f172a; font-family: Arial, sans-serif;">
    <table role="presentation" align="center" width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff;">
        <tr>
            <td style="padding: 32px 24px; text-align: center;">
                <img src="{{ $message->embed(public_path('administrator/logo.png')) }}" alt="BrakeSafe" width="76" height="76" style="display: block; width: 76px; height: 76px; margin: 0 auto 16px; border-radius: 16px;">
                <h1 style="margin: 0 0 24px; color: #b91c1c; font-size: 24px;">BrakeSafe</h1>
                <p style="margin: 0 0 12px; color: #334155;">Dear {{ $recipientEmail }},</p>
                <p style="margin: 0 0 20px; color: #334155;">Use this code to verify your BrakeSafe access request:</p>
                <p style="display: inline-block; margin: 0; padding: 14px 22px; border: 1px solid #fecaca; border-radius: 12px; background: #fef2f2; color: #b91c1c; font-size: 28px; font-weight: 700; letter-spacing: 6px;">{{ $verificationCode }}</p>
                <p style="margin: 20px 0 12px; color: #64748b; font-size: 14px;">This code expires in 10 minutes.</p>
                <p style="margin: 0 0 24px;"><a href="{{ $verificationUrl }}" style="color: #b91c1c;">Verify email address</a></p>
                <p style="margin: 0; color: #64748b; font-size: 13px;">If you did not request BrakeSafe access, you can ignore this message.</p>
            </td>
        </tr>
    </table>
</body>
</html>
