<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #1e293b; background: #f8fafc; padding: 24px; }
        .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 24px; max-width: 600px; margin: 0 auto; }
        .header { color: #1e3a5f; font-size: 18px; font-weight: bold; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px; }
        .footer { margin-top: 20px; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 12px; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">{{ $firmName }}</div>
    <p>Dear {{ $client->name }},</p>
    <p>{!! nl2br(e($messageBody)) !!}</p>
    <div class="footer">
        This message was sent via {{ $firmName }} Practice Management System. Please log in to your Client Portal to upload documents or reply securely.
    </div>
</div>
</body>
</html>

