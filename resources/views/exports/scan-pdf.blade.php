@php
// Helper function to safely output values that might be arrays
function safeOutput($value, $default = 'N/A') {
    if (is_null($value)) return $default;
    if (is_array($value)) {
        // If it's an array of arrays (like top_terms), just return count
        if (!empty($value) && is_array(reset($value))) {
            return count($value) . ' items';
        }
        return implode(', ', array_map(fn($v) => is_scalar($v) ? $v : json_encode($v), $value));
    }
    if (is_bool($value)) return $value ? 'Yes' : 'No';
    if (is_scalar($value)) return $value;
    return $default;
}

function safeNumber($value, $default = 0) {
    if (is_array($value)) return count($value);
    if (is_numeric($value)) return $value;
    return $default;
}
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GEO Scan Report - {{ $scan->title ?? $scan->url }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #1a1a1a;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #6366f1;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #6366f1;
            margin-bottom: 3px;
        }
        .tagline {
            font-size: 10px;
            color: #6b7280;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        h1 {
            font-size: 18px;
            margin-top: 12px;
            color: #1f2937;
        }
        h2 {
            font-size: 14px;
            color: #1f2937;
            margin-top: 20px;
            margin-bottom: 10px;
            padding: 8px 12px;
            background-color: #f3f4f6;
            border-left: 4px solid #6366f1;
        }
        h3 {
            font-size: 12px;
            color: #374151;
            margin-top: 12px;
            margin-bottom: 6px;
            font-weight: 600;
        }
        h4 {
            font-size: 11px;
            color: #4b5563;
            margin-top: 8px;
            margin-bottom: 4px;
            font-weight: 600;
        }
        .scan-info {
            background-color: #f9fafb;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid #e5e7eb;
        }
        .scan-info table {
            width: 100%;
        }
        .scan-info td {
            padding: 3px 0;
            vertical-align: top;
        }
        .scan-info td:first-child {
            font-weight: bold;
            width: 100px;
            color: #6b7280;
        }
        .score-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .score-card {
            display: table-cell;
            width: 35%;
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 10px;
            color: white;
            vertical-align: middle;
        }
        .score-value {
            font-size: 42px;
            font-weight: bold;
        }
        .score-label {
            font-size: 10px;
            opacity: 0.9;
        }
        .grade {
            display: inline-block;
            font-size: 28px;
            font-weight: bold;
            padding: 8px 16px;
            border-radius: 50%;
            margin-top: 8px;
            background-color: rgba(255, 255, 255, 0.2);
        }
        .summary-card {
            display: table-cell;
            width: 65%;
            padding-left: 20px;
            vertical-align: top;
        }
        .summary-text {
            color: #4b5563;
            margin-bottom: 10px;
            font-size: 11px;
        }
        .focus-area {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .focus-area strong {
            color: #92400e;
        }
        .strengths-weaknesses {
            display: table;
            width: 100%;
        }
        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        .column:last-child {
            padding-right: 0;
            padding-left: 10px;
        }
        .strength-item, .weakness-item {
            padding: 5px 10px;
            margin-bottom: 4px;
            border-radius: 3px;
            font-size: 10px;
        }
        .strength-item {
            background-color: #ecfdf5;
            color: #065f46;
            border-left: 3px solid #10b981;
        }
        .weakness-item {
            background-color: #fef3c7;
            color: #92400e;
            border-left: 3px solid #f59e0b;
        }
        .pillar-overview {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 15px;
        }
        .pillar-overview th, .pillar-overview td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }
        .pillar-overview th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        .pillar-overview tr:last-child td {
            border-bottom: none;
        }
        .progress-bar {
            width: 80px;
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
        }
        .progress-fill {
            height: 100%;
            border-radius: 4px;
        }
        .progress-green { background-color: #10b981; }
        .progress-blue { background-color: #3b82f6; }
        .progress-yellow { background-color: #f59e0b; }
        .progress-red { background-color: #ef4444; }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
        }
        .status-excellent { background-color: #d1fae5; color: #065f46; }
        .status-good { background-color: #dbeafe; color: #1e40af; }
        .status-needs-work { background-color: #fef3c7; color: #92400e; }
        .status-critical { background-color: #fee2e2; color: #991b1b; }
        .pillar-detail {
            margin-bottom: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }
        .pillar-header {
            background-color: #f9fafb;
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .pillar-header-title {
            font-weight: bold;
            font-size: 12px;
            color: #1f2937;
        }
        .pillar-header-score {
            float: right;
            font-weight: bold;
            color: #6366f1;
        }
        .pillar-body {
            padding: 12px;
        }
        .metrics-grid {
            display: table;
            width: 100%;
        }
        .metrics-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        .metrics-column:last-child {
            padding-right: 0;
            padding-left: 10px;
        }
        .metric-item {
            padding: 4px 0;
            border-bottom: 1px dotted #e5e7eb;
        }
        .metric-item:last-child {
            border-bottom: none;
        }
        .metric-label {
            color: #6b7280;
            font-size: 10px;
        }
        .metric-value {
            font-weight: 600;
            color: #1f2937;
            float: right;
        }
        .metric-value.good { color: #059669; }
        .metric-value.warning { color: #d97706; }
        .metric-value.bad { color: #dc2626; }
        .check-yes { color: #059669; }
        .check-no { color: #dc2626; }
        .score-breakdown {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
        }
        .score-breakdown-title {
            font-size: 10px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .breakdown-item {
            display: inline-block;
            background-color: #f3f4f6;
            padding: 3px 8px;
            border-radius: 3px;
            margin-right: 5px;
            margin-bottom: 3px;
            font-size: 9px;
        }
        .breakdown-item strong {
            color: #6366f1;
        }
        .recommendation {
            padding: 10px 12px;
            margin-bottom: 8px;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .recommendation.high {
            background-color: #fef2f2;
            border-color: #ef4444;
        }
        .recommendation.medium {
            background-color: #fffbeb;
            border-color: #f59e0b;
        }
        .recommendation.low {
            background-color: #ecfdf5;
            border-color: #10b981;
        }
        .recommendation-header {
            margin-bottom: 5px;
        }
        .recommendation-pillar {
            font-weight: bold;
            color: #374151;
            font-size: 11px;
        }
        .priority-badge {
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 8px;
            text-transform: uppercase;
            font-weight: bold;
            margin-left: 8px;
        }
        .priority-high { background-color: #fecaca; color: #991b1b; }
        .priority-medium { background-color: #fde68a; color: #92400e; }
        .priority-low { background-color: #a7f3d0; color: #065f46; }
        .current-score {
            float: right;
            font-size: 10px;
            color: #6b7280;
        }
        .recommendation-actions {
            margin-top: 6px;
            margin-left: 12px;
        }
        .recommendation-actions li {
            color: #4b5563;
            font-size: 10px;
            margin-bottom: 2px;
        }
        .definitions-found {
            background-color: #f9fafb;
            padding: 8px;
            border-radius: 4px;
            margin-top: 8px;
        }
        .definition-item {
            padding: 5px 0;
            border-bottom: 1px dashed #e5e7eb;
            font-size: 10px;
        }
        .definition-item:last-child {
            border-bottom: none;
        }
        .definition-pattern {
            font-size: 9px;
            color: #6b7280;
            font-style: italic;
        }
        .top-terms {
            margin-top: 5px;
        }
        .term-badge {
            display: inline-block;
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            margin-right: 3px;
            margin-bottom: 3px;
        }
        .snippets-section {
            margin-top: 8px;
        }
        .snippet-item {
            background-color: #f0fdf4;
            border: 1px solid #86efac;
            padding: 6px 10px;
            border-radius: 4px;
            margin-bottom: 5px;
            font-size: 10px;
            font-style: italic;
            color: #166534;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
        }
        .page-break {
            page-break-before: always;
        }
        .toc {
            margin-bottom: 20px;
        }
        .toc-item {
            padding: 3px 0;
            font-size: 10px;
        }
        .toc-item a {
            color: #4b5563;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">GeoSource.ai</div>
        <div class="tagline">Generative Engine Optimization Analysis</div>
        <h1>Comprehensive GEO Scan Report</h1>
    </div>

    <!-- Scan Info -->
    <div class="scan-info">
        <table>
            <tr>
                <td>URL:</td>
                <td>{{ $scan->url }}</td>
            </tr>
            @if($scan->title)
            <tr>
                <td>Title:</td>
                <td>{{ $scan->title }}</td>
            </tr>
            @endif
            <tr>
                <td>Scanned:</td>
                <td>{{ $scan->created_at->format('F j, Y \a\t g:i A') }}</td>
            </tr>
            <tr>
                <td>Report ID:</td>
                <td>{{ $scan->uuid }}</td>
            </tr>
        </table>
    </div>

    <!-- Score Overview -->
    <div class="score-section">
        <div class="score-card">
            <div class="score-value">{{ number_format($scan->score, 1) }}</div>
            <div class="score-label">out of {{ $scan->results['max_score'] ?? 100 }}</div>
            <div class="grade">{{ $scan->grade }}</div>
        </div>
        <div class="summary-card">
            @if(!empty($summary['overall']))
            <p class="summary-text">{{ $summary['overall'] }}</p>
            @endif

            @if(!empty($summary['focus_area']))
            <div class="focus-area">
                <strong>Primary Focus Area:</strong> {{ $summary['focus_area'] }}
            </div>
            @endif

            @if(!empty($summary['strengths']) || !empty($summary['weaknesses']))
            <div class="strengths-weaknesses">
                <div class="column">
                    <h4>Strengths</h4>
                    @foreach($summary['strengths'] ?? [] as $strength)
                    <div class="strength-item">{{ $strength }}</div>
                    @endforeach
                </div>
                <div class="column">
                    <h4>Needs Improvement</h4>
                    @foreach($summary['weaknesses'] ?? [] as $weakness)
                    <div class="weakness-item">{{ $weakness }}</div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Pillar Overview Table -->
    <h2>Score Breakdown</h2>
    <table class="pillar-overview">
        <thead>
            <tr>
                <th>Pillar</th>
                <th>Score</th>
                <th>Progress</th>
                <th>Percentage</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pillars as $key => $pillar)
            @php
                $percentage = $pillar['percentage'] ?? 0;
                $progressClass = $percentage >= 80 ? 'progress-green' : ($percentage >= 60 ? 'progress-blue' : ($percentage >= 40 ? 'progress-yellow' : 'progress-red'));
                $statusClass = $percentage >= 80 ? 'status-excellent' : ($percentage >= 60 ? 'status-good' : ($percentage >= 40 ? 'status-needs-work' : 'status-critical'));
                $statusText = $percentage >= 80 ? 'Excellent' : ($percentage >= 60 ? 'Good' : ($percentage >= 40 ? 'Needs Work' : 'Critical'));
            @endphp
            <tr>
                <td><strong>{{ $pillar['name'] ?? ucfirst(str_replace('_', ' ', $key)) }}</strong></td>
                <td>{{ number_format($pillar['score'] ?? 0, 1) }} / {{ $pillar['max_score'] ?? 0 }}</td>
                <td>
                    <div class="progress-bar">
                        <div class="progress-fill {{ $progressClass }}" style="width: {{ $percentage }}%"></div>
                    </div>
                </td>
                <td>{{ number_format($percentage, 0) }}%</td>
                <td><span class="status-badge {{ $statusClass }}">{{ $statusText }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Detailed Pillar Analysis -->
    <div class="page-break"></div>
    <h2>Detailed Pillar Analysis</h2>

    @foreach($pillars as $key => $pillar)
    @php
        $details = $pillar['details'] ?? [];
        $percentage = $pillar['percentage'] ?? 0;
    @endphp

    <div class="pillar-detail">
        <div class="pillar-header">
            <span class="pillar-header-title">{{ $pillar['name'] ?? ucfirst(str_replace('_', ' ', $key)) }}</span>
            <span class="pillar-header-score">{{ number_format($pillar['score'] ?? 0, 1) }} / {{ $pillar['max_score'] ?? 0 }} ({{ number_format($percentage, 0) }}%)</span>
        </div>
        <div class="pillar-body">
            @if($key === 'definitions')
                {{-- Clear Definitions Pillar --}}
                <div class="metrics-grid">
                    <div class="metrics-column">
                        <div class="metric-item">
                            <span class="metric-label">Entity/Topic Detected</span>
                            <span class="metric-value">{{ safeOutput($details['entity'] ?? null, 'Not detected') }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Has Early Definition</span>
                            <span class="metric-value {{ ($details['early_definition'] ?? false) ? 'check-yes' : 'check-no' }}">
                                {{ ($details['early_definition'] ?? false) ? '✓ Yes' : '✗ No' }}
                            </span>
                        </div>
                    </div>
                    <div class="metrics-column">
                        <div class="metric-item">
                            <span class="metric-label">Entity in Definition</span>
                            <span class="metric-value {{ ($details['entity_in_definition'] ?? false) ? 'check-yes' : 'check-no' }}">
                                {{ ($details['entity_in_definition'] ?? false) ? '✓ Yes' : '✗ No' }}
                            </span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Definitions Found</span>
                            <span class="metric-value">{{ count($details['definitions_found'] ?? []) }}</span>
                        </div>
                    </div>
                </div>
                @if(!empty($details['definitions_found']))
                <div class="definitions-found">
                    <h4>Detected Definitions</h4>
                    @foreach(array_slice($details['definitions_found'], 0, 3) as $def)
                    <div class="definition-item">
                        "{{ Str::limit($def['sentence'] ?? '', 150) }}"
                        <div class="definition-pattern">Pattern: {{ $def['pattern'] ?? 'N/A' }} | Position: {{ $def['position'] ?? 0 }}%</div>
                    </div>
                    @endforeach
                </div>
                @endif

            @elseif($key === 'structure')
                {{-- Structured Knowledge Pillar --}}
                @php
                    $headings = $details['headings'] ?? [];
                    $lists = $details['lists'] ?? [];
                    $sections = $details['sections'] ?? [];
                    $hierarchy = $details['hierarchy'] ?? [];
                    // Handle headings - could be counts or arrays of texts
                    $h1Count = is_array($headings['h1'] ?? 0) ? count($headings['h1']) : ($headings['h1'] ?? 0);
                    $h2Count = is_array($headings['h2'] ?? 0) ? count($headings['h2']) : ($headings['h2'] ?? 0);
                    $h3Count = is_array($headings['h3'] ?? 0) ? count($headings['h3']) : ($headings['h3'] ?? 0);
                    $h4Count = is_array($headings['h4'] ?? 0) ? count($headings['h4']) : ($headings['h4'] ?? 0);
                    $h5Count = is_array($headings['h5'] ?? 0) ? count($headings['h5']) : ($headings['h5'] ?? 0);
                    $h6Count = is_array($headings['h6'] ?? 0) ? count($headings['h6']) : ($headings['h6'] ?? 0);
                @endphp
                <div class="metrics-grid">
                    <div class="metrics-column">
                        <h4>Heading Structure</h4>
                        <div class="metric-item">
                            <span class="metric-label">H1 Tags</span>
                            <span class="metric-value {{ $h1Count === 1 ? 'good' : 'warning' }}">{{ $h1Count }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">H2 Tags</span>
                            <span class="metric-value">{{ $h2Count }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">H3-H6 Tags</span>
                            <span class="metric-value">{{ $h3Count + $h4Count + $h5Count + $h6Count }}</span>
                        </div>
                        <h4>Lists</h4>
                        <div class="metric-item">
                            <span class="metric-label">Unordered Lists</span>
                            <span class="metric-value">{{ safeNumber($lists['unordered'] ?? 0) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Ordered Lists</span>
                            <span class="metric-value">{{ safeNumber($lists['ordered'] ?? 0) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Total List Items</span>
                            <span class="metric-value">{{ safeNumber($lists['total_items'] ?? 0) }}</span>
                        </div>
                    </div>
                    <div class="metrics-column">
                        <h4>Content Structure</h4>
                        <div class="metric-item">
                            <span class="metric-label">Semantic Sections</span>
                            <span class="metric-value">{{ safeNumber($sections['semantic_sections'] ?? 0) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Paragraphs</span>
                            <span class="metric-value">{{ safeNumber($sections['paragraphs'] ?? 0) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Content Density</span>
                            <span class="metric-value">{{ number_format(safeNumber($sections['content_density'] ?? 0), 1) }}/sec</span>
                        </div>
                        <h4>Hierarchy Quality</h4>
                        <div class="metric-item">
                            <span class="metric-label">Properly Nested</span>
                            <span class="metric-value {{ ($hierarchy['properly_nested'] ?? false) ? 'check-yes' : 'check-no' }}">
                                {{ ($hierarchy['properly_nested'] ?? false) ? '✓ Yes' : '✗ No' }}
                            </span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Single H1</span>
                            <span class="metric-value {{ ($hierarchy['single_h1'] ?? false) ? 'check-yes' : 'check-no' }}">
                                {{ ($hierarchy['single_h1'] ?? false) ? '✓ Yes' : '✗ No' }}
                            </span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Heading Levels</span>
                            <span class="metric-value">{{ safeNumber($hierarchy['levels_used'] ?? 0) }}</span>
                        </div>
                    </div>
                </div>

            @elseif($key === 'authority')
                {{-- Topic Authority Pillar --}}
                @php
                    $coherence = $details['topic_coherence'] ?? [];
                    $keyword = $details['keyword_density'] ?? [];
                    $depth = $details['topic_depth'] ?? [];
                    $links = $details['internal_links'] ?? [];
                @endphp
                <div class="metrics-grid">
                    <div class="metrics-column">
                        <h4>Topic Coherence</h4>
                        <div class="metric-item">
                            <span class="metric-label">Word Count</span>
                            <span class="metric-value {{ safeNumber($coherence['word_count'] ?? 0) >= 800 ? 'good' : 'warning' }}">{{ number_format(safeNumber($coherence['word_count'] ?? 0)) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Unique Word Ratio</span>
                            <span class="metric-value">{{ number_format(safeNumber($coherence['unique_ratio'] ?? 0) * 100, 1) }}%</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Coherence Score</span>
                            <span class="metric-value">{{ number_format(safeNumber($coherence['coherence_ratio'] ?? 0), 3) }}</span>
                        </div>
                        <h4>Keyword Analysis</h4>
                        <div class="metric-item">
                            <span class="metric-label">Primary Keyword</span>
                            <span class="metric-value">{{ safeOutput($keyword['primary_keyword'] ?? null, 'N/A') }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Density</span>
                            <span class="metric-value {{ safeNumber($keyword['density'] ?? 0) >= 1 && safeNumber($keyword['density'] ?? 0) <= 3 ? 'good' : 'warning' }}">{{ number_format(safeNumber($keyword['density'] ?? 0), 2) }}%</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Distribution</span>
                            <span class="metric-value">{{ safeOutput($keyword['distribution_label'] ?? null, 'N/A') }}</span>
                        </div>
                    </div>
                    <div class="metrics-column">
                        <h4>Content Depth</h4>
                        <div class="metric-item">
                            <span class="metric-label">Sentences</span>
                            <span class="metric-value">{{ safeNumber($depth['sentence_count'] ?? 0) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Avg Sentence Length</span>
                            <span class="metric-value">{{ number_format(safeNumber($depth['avg_sentence_length'] ?? 0), 1) }} words</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Depth Level</span>
                            <span class="metric-value">{{ ucfirst(safeOutput($depth['depth_level'] ?? null, 'N/A')) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Depth Indicators</span>
                            <span class="metric-value">{{ safeNumber($depth['total_indicators'] ?? 0) }}</span>
                        </div>
                        <h4>Link Profile</h4>
                        <div class="metric-item">
                            <span class="metric-label">Internal Links</span>
                            <span class="metric-value">{{ safeNumber($links['internal_count'] ?? 0) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">External Links</span>
                            <span class="metric-value">{{ safeNumber($links['external_count'] ?? 0) }}</span>
                        </div>
                    </div>
                </div>
                @if(!empty($coherence['top_terms']) && is_array($coherence['top_terms']))
                <div class="top-terms">
                    <h4>Top Terms</h4>
                    @foreach(array_slice($coherence['top_terms'], 0, 8) as $term)
                        @if(is_array($term) && isset($term['term']))
                        <span class="term-badge">{{ $term['term'] }} ({{ $term['count'] ?? 0 }})</span>
                        @endif
                    @endforeach
                </div>
                @endif

            @elseif($key === 'machine_readable')
                {{-- Machine-Readable Pillar --}}
                @php
                    $schema = $details['schema'] ?? [];
                    $semantic = $details['semantic_html'] ?? [];
                    $faq = $details['faq'] ?? [];
                    $meta = $details['meta'] ?? [];
                @endphp
                <div class="metrics-grid">
                    <div class="metrics-column">
                        <h4>Schema.org Data</h4>
                        <div class="metric-item">
                            <span class="metric-label">Has JSON-LD</span>
                            <span class="metric-value {{ ($schema['has_json_ld'] ?? false) ? 'check-yes' : 'check-no' }}">
                                {{ ($schema['has_json_ld'] ?? false) ? '✓ Yes' : '✗ No' }}
                            </span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Schema Types</span>
                            <span class="metric-value">{{ safeNumber($schema['found_count'] ?? 0) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Valuable Schema</span>
                            <span class="metric-value {{ ($schema['has_valuable_schema'] ?? false) ? 'check-yes' : 'check-no' }}">
                                {{ ($schema['has_valuable_schema'] ?? false) ? '✓ Yes' : '✗ No' }}
                            </span>
                        </div>
                        <h4>FAQ Analysis</h4>
                        <div class="metric-item">
                            <span class="metric-label">FAQ Schema</span>
                            <span class="metric-value {{ ($faq['has_faq_schema'] ?? false) ? 'check-yes' : 'check-no' }}">
                                {{ ($faq['has_faq_schema'] ?? false) ? '✓ Yes' : '✗ No' }}
                            </span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">FAQ Section</span>
                            <span class="metric-value {{ ($faq['has_faq_section'] ?? false) ? 'check-yes' : 'check-no' }}">
                                {{ ($faq['has_faq_section'] ?? false) ? '✓ Yes' : '✗ No' }}
                            </span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Questions Found</span>
                            <span class="metric-value">{{ safeNumber($faq['question_count'] ?? 0) }}</span>
                        </div>
                    </div>
                    <div class="metrics-column">
                        <h4>Semantic HTML</h4>
                        <div class="metric-item">
                            <span class="metric-label">Semantic Elements</span>
                            <span class="metric-value">{{ safeNumber($semantic['total_elements'] ?? 0) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Unique Types</span>
                            <span class="metric-value">{{ safeNumber($semantic['unique_elements'] ?? 0) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Images w/ Alt Text</span>
                            <span class="metric-value">{{ safeNumber($semantic['image_alt_coverage'] ?? 0) }}%</span>
                        </div>
                        <h4>Meta Tags</h4>
                        <div class="metric-item">
                            <span class="metric-label">Title Tag</span>
                            <span class="metric-value {{ ($meta['has_title'] ?? false) ? 'check-yes' : 'check-no' }}">
                                {{ ($meta['has_title'] ?? false) ? '✓' : '✗' }}
                            </span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Description</span>
                            <span class="metric-value {{ ($meta['has_description'] ?? false) ? 'check-yes' : 'check-no' }}">
                                {{ ($meta['has_description'] ?? false) ? '✓' : '✗' }}
                            </span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Open Graph</span>
                            <span class="metric-value {{ ($meta['has_og'] ?? false) ? 'check-yes' : 'check-no' }}">
                                {{ ($meta['has_og'] ?? false) ? '✓' : '✗' }}
                            </span>
                        </div>
                    </div>
                </div>

            @elseif($key === 'answerability')
                {{-- Answerability Pillar --}}
                @php
                    $declarative = $details['declarative'] ?? [];
                    $uncertainty = $details['uncertainty'] ?? [];
                    $confidence = $details['confidence'] ?? [];
                    $snippets = $details['snippets'] ?? [];
                    $directness = $details['directness'] ?? [];
                @endphp
                <div class="metrics-grid">
                    <div class="metrics-column">
                        <h4>Declarative Language</h4>
                        <div class="metric-item">
                            <span class="metric-label">Total Sentences</span>
                            <span class="metric-value">{{ safeNumber($declarative['total_sentences'] ?? 0) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Declarative Ratio</span>
                            <span class="metric-value {{ (safeNumber($declarative['ratio'] ?? 0) * 100) >= 70 ? 'good' : 'warning' }}">{{ number_format(safeNumber($declarative['ratio'] ?? 0) * 100, 1) }}%</span>
                        </div>
                        <h4>Uncertainty Analysis</h4>
                        <div class="metric-item">
                            <span class="metric-label">Hedging Words</span>
                            <span class="metric-value {{ safeNumber($uncertainty['hedging_count'] ?? 0) <= 5 ? 'good' : 'warning' }}">{{ safeNumber($uncertainty['hedging_count'] ?? 0) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Uncertainty Level</span>
                            <span class="metric-value">{{ ucfirst(safeOutput($uncertainty['uncertainty_level'] ?? null, 'N/A')) }}</span>
                        </div>
                        <h4>Confidence</h4>
                        <div class="metric-item">
                            <span class="metric-label">Confidence Phrases</span>
                            <span class="metric-value">{{ safeNumber($confidence['confidence_count'] ?? 0) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Confidence Level</span>
                            <span class="metric-value">{{ ucfirst(safeOutput($confidence['confidence_level'] ?? null, 'N/A')) }}</span>
                        </div>
                    </div>
                    <div class="metrics-column">
                        <h4>Quotable Snippets</h4>
                        <div class="metric-item">
                            <span class="metric-label">Snippet Candidates</span>
                            <span class="metric-value">{{ safeNumber($snippets['count'] ?? 0) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Featured Snippet Ready</span>
                            <span class="metric-value {{ ($snippets['has_featured_snippet_candidates'] ?? false) ? 'check-yes' : 'check-no' }}">
                                {{ ($snippets['has_featured_snippet_candidates'] ?? false) ? '✓ Yes' : '✗ No' }}
                            </span>
                        </div>
                        <h4>Content Directness</h4>
                        <div class="metric-item">
                            <span class="metric-label">Starts with Answer</span>
                            <span class="metric-value {{ ($directness['starts_with_answer'] ?? false) ? 'check-yes' : 'check-no' }}">
                                {{ ($directness['starts_with_answer'] ?? false) ? '✓ Yes' : '✗ No' }}
                            </span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Direct Elements</span>
                            <span class="metric-value">{{ safeNumber($directness['total_direct_elements'] ?? 0) }}</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Directness Level</span>
                            <span class="metric-value">{{ ucfirst(safeOutput($directness['directness_level'] ?? null, 'N/A')) }}</span>
                        </div>
                    </div>
                </div>
                @if(!empty($snippets['snippets']) && is_array($snippets['snippets']))
                <div class="snippets-section">
                    <h4>Top Quotable Snippets</h4>
                    @foreach(array_slice($snippets['snippets'], 0, 2) as $snippet)
                        @if(is_array($snippet) && isset($snippet['text']))
                        <div class="snippet-item">"{{ Str::limit($snippet['text'], 200) }}"</div>
                        @endif
                    @endforeach
                </div>
                @endif
            @endif

            {{-- Score Breakdown --}}
            @if(!empty($details['breakdown']))
            <div class="score-breakdown">
                <div class="score-breakdown-title">Score Components</div>
                @foreach($details['breakdown'] as $component => $score)
                <span class="breakdown-item">{{ ucfirst(str_replace('_', ' ', $component)) }}: <strong>{{ number_format($score, 1) }} pts</strong></span>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endforeach

    <!-- Recommendations -->
    @if(!empty($recommendations))
    <div class="page-break"></div>
    <h2>Recommendations</h2>
    <p style="color: #6b7280; font-size: 10px; margin-bottom: 15px;">
        The following recommendations are prioritized based on their potential impact on your GEO score.
    </p>

    @foreach($recommendations as $rec)
    <div class="recommendation {{ $rec['priority'] ?? 'medium' }}">
        <div class="recommendation-header">
            <span class="recommendation-pillar">{{ $rec['pillar'] ?? 'General' }}</span>
            <span class="priority-badge priority-{{ $rec['priority'] ?? 'medium' }}">
                {{ $rec['priority'] ?? 'medium' }} priority
            </span>
            <span class="current-score">Current: {{ $rec['current_score'] ?? 'N/A' }}</span>
        </div>
        @if(!empty($rec['actions']))
        <ul class="recommendation-actions">
            @foreach($rec['actions'] as $action)
            <li>{{ $action }}</li>
            @endforeach
        </ul>
        @endif
    </div>
    @endforeach
    @endif

    <!-- Footer -->
    <div class="footer">
        <p><strong>GeoSource.ai</strong> - Generative Engine Optimization Platform</p>
        <p>Report generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        <p style="margin-top: 8px;">This report provides actionable insights for improving your content's visibility in AI-powered search engines including ChatGPT, Perplexity, Claude, and Google AI Overviews.</p>
    </div>
</body>
</html>
