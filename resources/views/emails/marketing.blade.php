<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .tagline {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .content {
            padding: 30px;
        }
        .content h1, .content h2, .content h3 {
            color: #1f2937;
            margin-top: 0;
        }
        .content p {
            margin: 15px 0;
            color: #374151;
        }
        .content a {
            color: #6366f1;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white !important;
            text-decoration: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
        }
        .footer {
            background-color: #f9fafb;
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 5px 0;
            color: #6b7280;
            font-size: 13px;
        }
        .footer a {
            color: #6366f1;
            text-decoration: none;
        }
        .unsubscribe {
            margin-top: 15px;
            font-size: 12px;
            color: #9ca3af;
        }
        .unsubscribe a {
            color: #9ca3af;
            text-decoration: underline;
        }
        @if($previewText)
        .preview-text {
            display: none;
            max-height: 0;
            overflow: hidden;
        }
        @endif
    </style>
</head>
<body>
    @if($previewText)
    <div class="preview-text">{{ $previewText }}</div>
    @endif

    <div class="container">
        <div class="header">
            <div class="logo">{{ config('app.name') }}</div>
            <div class="tagline">Generative Engine Optimization</div>
        </div>

        <div class="content">
            {!! $content !!}
        </div>

        <div class="footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>Optimize your content for AI-powered search engines</p>
            <p style="margin-top: 15px;">
                <a href="{{ config('app.url') }}">Visit Dashboard</a>
            </p>
            <div class="unsubscribe">
                <p>You received this email because you're subscribed to marketing updates from {{ config('app.name') }}.</p>
                <p><a href="{{ $unsubscribeUrl }}">Unsubscribe</a> from marketing emails</p>
            </div>
        </div>
    </div>

    <!-- Open tracking pixel -->
    <img src="{{ $trackingPixelUrl }}" width="1" height="1" alt="" style="display:none;" />
</body>
</html>
