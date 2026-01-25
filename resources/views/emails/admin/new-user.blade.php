<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New User Registration</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #6366f1, #8B5CF6);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .content {
            background: #f8fafc;
            padding: 20px;
            border: 1px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        .info-row {
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: 600;
            color: #64748b;
        }
        .value {
            color: #1e293b;
        }
        .button {
            display: inline-block;
            background: #6366f1;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 15px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #64748b;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 24px;">New User Registration</h1>
    </div>
    <div class="content">
        <p>A new user has registered on GeoSource.ai:</p>

        <div class="info-row">
            <span class="label">Name:</span>
            <span class="value">{{ $user->name }}</span>
        </div>

        <div class="info-row">
            <span class="label">Email:</span>
            <span class="value">{{ $user->email }}</span>
        </div>

        <div class="info-row">
            <span class="label">Registered:</span>
            <span class="value">{{ $user->created_at->format('F j, Y \a\t g:i A') }}</span>
        </div>

        <div class="info-row">
            <span class="label">User ID:</span>
            <span class="value">{{ $user->id }}</span>
        </div>

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/nova/resources/users/{{ $user->id }}" class="button">
                View in Nova
            </a>
        </div>
    </div>
    <div class="footer">
        <p>GeoSource.ai Admin Notification</p>
    </div>
</body>
</html>
