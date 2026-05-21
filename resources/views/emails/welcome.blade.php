<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to GeoSource.ai</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f1f5f9;
        }
        .wrapper {
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #6366f1, #8B5CF6);
            color: white;
            padding: 32px 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            margin: 8px 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 32px 24px;
        }
        .content p {
            margin: 0 0 16px;
            color: #475569;
            font-size: 15px;
        }
        .token-badge {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 16px 20px;
            text-align: center;
            margin: 24px 0;
        }
        .token-badge .amount {
            font-size: 32px;
            font-weight: 700;
            color: #16a34a;
        }
        .token-badge .label {
            font-size: 14px;
            color: #4ade80;
            margin-top: 4px;
        }
        .steps {
            margin: 24px 0;
        }
        .step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .step-number {
            background: #6366f1;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            flex-shrink: 0;
            margin-right: 12px;
            margin-top: 2px;
        }
        .step-text {
            color: #475569;
            font-size: 15px;
        }
        .step-text strong {
            color: #1e293b;
        }
        .button-container {
            text-align: center;
            margin: 28px 0 8px;
        }
        .button {
            display: inline-block;
            background: #6366f1;
            color: white;
            padding: 14px 32px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
        }
        .footer {
            text-align: center;
            padding: 20px 24px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 13px;
        }
        .footer a {
            color: #6366f1;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Welcome to GeoSource.ai</h1>
            <p>Your AI search optimization journey starts now</p>
        </div>

        <div class="content">
            <p>Hi {{ $user->name }},</p>

            <p>Thanks for creating your account. GeoSource.ai helps you optimize your content to be cited by AI search engines like ChatGPT, Perplexity, Claude, and Gemini.</p>

            <div class="token-badge">
                <div class="amount">20 Free Tokens</div>
                <div class="label">Added to your account</div>
            </div>

            <p>Here's how to get started:</p>

            <div class="steps">
                <div class="step">
                    <span class="step-number">1</span>
                    <span class="step-text"><strong>Run your first GEO scan</strong> &mdash; paste any URL to get your GEO score across 12 optimization pillars.</span>
                </div>
                <div class="step">
                    <span class="step-number">2</span>
                    <span class="step-text"><strong>Review your recommendations</strong> &mdash; see exactly what to improve for AI citation readiness.</span>
                </div>
                <div class="step">
                    <span class="step-number">3</span>
                    <span class="step-text"><strong>Track your AI citations</strong> &mdash; monitor whether AI platforms cite your content.</span>
                </div>
            </div>

            <div class="button-container">
                <a href="{{ config('app.url') }}/dashboard" class="button">Go to Dashboard</a>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} <a href="{{ config('app.url') }}">GeoSource.ai</a>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
