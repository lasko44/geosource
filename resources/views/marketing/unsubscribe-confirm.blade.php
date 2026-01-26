<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 480px;
            width: 100%;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }
        h2 {
            color: #1f2937;
            font-size: 20px;
            margin: 0 0 15px 0;
        }
        p {
            color: #6b7280;
            line-height: 1.6;
            margin: 0 0 20px 0;
        }
        .email-address {
            font-weight: 600;
            color: #1f2937;
        }
        form {
            margin-top: 20px;
        }
        label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            resize: vertical;
            min-height: 80px;
            box-sizing: border-box;
        }
        textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        .btn {
            flex: 1;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
        }
        .btn-primary:hover {
            opacity: 0.9;
        }
        .btn-secondary {
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .btn-secondary:hover {
            background: #f9fafb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>{{ config('app.name') }}</h1>
        </div>

        <h2>Unsubscribe from Marketing Emails</h2>
        <p>You're about to unsubscribe <span class="email-address">{{ $email }}</span> from our marketing emails.</p>
        <p>You'll still receive important account-related emails like password resets and billing notifications.</p>

        <form action="{{ route('marketing.unsubscribe.process') }}" method="POST">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">

            <label for="reason">Mind telling us why? (Optional)</label>
            <textarea name="reason" id="reason" placeholder="Your feedback helps us improve..."></textarea>

            <div class="buttons">
                <a href="{{ config('app.url') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Unsubscribe</button>
            </div>
        </form>
    </div>
</body>
</html>
