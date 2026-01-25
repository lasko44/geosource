@php
// Get white label settings with defaults
$wl = $pdfData['whiteLabel'] ?? [];
$primaryColor = $wl['primary_color'] ?? '#6366f1';
$secondaryColor = $wl['secondary_color'] ?? '#8b5cf6';
$companyName = $wl['company_name'] ?? config('app.name', 'GeoSource.ai');
$logoUrl = $wl['logo_url'] ?? null;
$reportFooter = $wl['report_footer'] ?? null;
$contactEmail = $wl['contact_email'] ?? null;
$websiteUrl = $wl['website_url'] ?? config('app.url');
$isWhiteLabeled = $wl['enabled'] ?? false;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Scan Complete</title>
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
            background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header-logo {
            max-height: 50px;
            max-width: 200px;
            margin-bottom: 8px;
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
        .scheduled-badge {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 10px;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .scan-info-box {
            background-color: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .scan-info-box h4 {
            margin: 0 0 10px 0;
            color: #0369a1;
            font-size: 14px;
        }
        .scan-info-box p {
            margin: 5px 0;
            color: #0c4a6e;
            font-size: 14px;
        }
        .score-card {
            background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            margin: 20px 0;
        }
        .score-value {
            font-size: 48px;
            font-weight: bold;
        }
        .score-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .grade {
            display: inline-block;
            font-size: 24px;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 50px;
            margin-top: 10px;
            background-color: rgba(255, 255, 255, 0.2);
        }
        .scan-details {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .scan-details h3 {
            margin: 0 0 15px 0;
            color: #374151;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #6b7280;
        }
        .detail-value {
            font-weight: 600;
            color: #1f2937;
        }
        .pillar-summary {
            margin: 20px 0;
        }
        .pillar-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .pillar-item:last-child {
            border-bottom: none;
        }
        .pillar-name {
            flex: 1;
            font-weight: 500;
        }
        .pillar-score {
            color: #6b7280;
            margin-right: 15px;
        }
        .pillar-bar {
            width: 100px;
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        .pillar-fill {
            height: 100%;
            border-radius: 4px;
        }
        .fill-excellent { background-color: #10b981; }
        .fill-good { background-color: #3b82f6; }
        .fill-needs-work { background-color: #f59e0b; }
        .fill-critical { background-color: #ef4444; }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);
            color: white;
            text-decoration: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
        }
        .note {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .note p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
        }
        .schedule-info {
            background-color: #f3f4f6;
            border-radius: 6px;
            padding: 12px 15px;
            margin: 20px 0;
            font-size: 13px;
            color: #6b7280;
        }
        .schedule-info strong {
            color: #374151;
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
            color: {{ $primaryColor }};
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="header-logo">
            @else
                <div class="logo">{{ $companyName }}</div>
            @endif
            <div class="tagline">Generative Engine Optimization</div>
            <div class="scheduled-badge">Scheduled Scan</div>
        </div>

        <div class="content">
            <p class="greeting">Hi {{ $recipient->name }},</p>

            <p>Your scheduled scan has completed successfully. Here's a summary of the results:</p>

            <div class="scan-info-box">
                <h4>Scheduled Scan: {{ $scheduledScan->name ?? 'Untitled' }}</h4>
                <p><strong>URL:</strong> {{ Str::limit($scan->url, 50) }}</p>
                <p><strong>Schedule:</strong> {{ $scheduledScan->schedule_description }}</p>
                @if($scheduledScan->next_run_at)
                <p><strong>Next Run:</strong> {{ $scheduledScan->next_run_at->format('M j, Y g:i A') }}</p>
                @endif
            </div>

            <div class="score-card">
                <div class="score-value">{{ number_format($scan->score, 1) }}</div>
                <div class="score-label">out of {{ $scan->results['max_score'] ?? 100 }}</div>
                <div class="grade">{{ $scan->grade }}</div>
            </div>

            <div class="scan-details">
                <h3>Scan Details</h3>
                <div class="detail-row">
                    <span class="detail-label">URL</span>
                    <span class="detail-value">{{ Str::limit($scan->url, 40) }}</span>
                </div>
                @if($scan->title)
                <div class="detail-row">
                    <span class="detail-label">Page Title</span>
                    <span class="detail-value">{{ Str::limit($scan->title, 40) }}</span>
                </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">Scanned</span>
                    <span class="detail-value">{{ $scan->created_at->format('M j, Y g:i A') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Scans</span>
                    <span class="detail-value">{{ $scheduledScan->total_runs }} runs</span>
                </div>
            </div>

            @if(!empty($pdfData['pillars']))
            <div class="pillar-summary">
                <h3 style="margin: 0 0 15px 0; color: #374151;">Score Breakdown</h3>
                @foreach($pdfData['pillars'] as $key => $pillar)
                @php
                    $percentage = $pillar['percentage'] ?? 0;
                    $fillClass = $percentage >= 80 ? 'fill-excellent' : ($percentage >= 60 ? 'fill-good' : ($percentage >= 40 ? 'fill-needs-work' : 'fill-critical'));
                @endphp
                <div class="pillar-item">
                    <span class="pillar-name">{{ $pillar['name'] ?? ucfirst(str_replace('_', ' ', $key)) }}</span>
                    <span class="pillar-score">{{ number_format($pillar['score'] ?? 0, 1) }}/{{ $pillar['max_score'] ?? 0 }}</span>
                    <div class="pillar-bar">
                        <div class="pillar-fill {{ $fillClass }}" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($pdfData['recommendationsLimited']) && $pdfData['recommendationsLimited'])
            <div class="note">
                <p><strong>Note:</strong> The attached report includes {{ count($pdfData['recommendations']) }} of {{ $pdfData['recommendationsTotal'] }} recommendations based on your current plan. Upgrade to see all recommendations.</p>
            </div>
            @endif

            <p style="margin-top: 25px;">The full detailed report is attached as a PDF. You can also view your scan results and manage your scheduled scans in your dashboard.</p>

            <center>
                <a href="{{ route('scans.show', $scan) }}" class="cta-button">View Full Report</a>
            </center>

            <div class="schedule-info">
                <strong>Managing Your Scheduled Scans:</strong> You can modify the schedule, pause, or delete this scheduled scan from your <a href="{{ route('scheduled-scans.index') }}">Scheduled Scans</a> page.
            </div>
        </div>

        <div class="footer">
            @if($isWhiteLabeled)
                <p><strong>{{ $companyName }}</strong></p>
                @if($contactEmail || $websiteUrl)
                <p>
                    @if($contactEmail){{ $contactEmail }}@endif
                    @if($contactEmail && $websiteUrl) | @endif
                    @if($websiteUrl)<a href="{{ $websiteUrl }}">{{ parse_url($websiteUrl, PHP_URL_HOST) }}</a>@endif
                </p>
                @endif
                @if($reportFooter)
                <p style="margin-top: 10px; font-style: italic;">{{ $reportFooter }}</p>
                @endif
            @else
                <p><strong>GeoSource.ai</strong></p>
                <p>Optimize your content for AI-powered search engines</p>
                <p style="margin-top: 15px;">
                    <a href="{{ config('app.url') }}">Visit Dashboard</a> |
                    <a href="{{ route('scheduled-scans.index') }}">Manage Scheduled Scans</a>
                </p>
            @endif
        </div>
    </div>
</body>
</html>
