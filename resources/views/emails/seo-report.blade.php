<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Analysis Report</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            max-width: 600px;
            margin: 0 auto;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #3498db;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #3498db;
            margin: 0;
            font-size: 28px;
        }
        .header p {
            color: #7f8c8d;
            margin: 5px 0 0 0;
            font-style: italic;
        }
        .score-section {
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .score-circle {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .score-grade {
            font-size: 24px;
            opacity: 0.9;
        }
        .url-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #3498db;
            margin-bottom: 25px;
            border-radius: 0 4px 4px 0;
        }
        .url-info h2 {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-size: 18px;
        }
        .url-info p {
            margin: 0;
            color: #7f8c8d;
            font-size: 14px;
        }
        .summary-stats {
            display: flex;
            justify-content: space-around;
            margin: 25px 0;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            display: block;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #7f8c8d;
        }
        .critical .stat-number { color: #e74c3c; }
        .warning .stat-number { color: #f39c12; }
        .success .stat-number { color: #27ae60; }
        .section {
            margin-bottom: 25px;
        }
        .section h3 {
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .issues-list, .insights-list {
            list-style: none;
            padding: 0;
        }
        .issues-list li, .insights-list li {
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 4px;
            position: relative;
            padding-left: 45px;
        }
        .issues-list li.critical {
            background-color: #fdf2f2;
            border-left: 4px solid #e74c3c;
        }
        .issues-list li.warning {
            background-color: #fef9e7;
            border-left: 4px solid #f39c12;
        }
        .issues-list li.success {
            background-color: #f0f9f4;
            border-left: 4px solid #27ae60;
        }
        .insights-list li {
            background-color: #e8f4fd;
            border-left: 4px solid #3498db;
        }
        .issues-list li::before {
            position: absolute;
            left: 15px;
            font-size: 16px;
        }
        .issues-list li.critical::before { content: "⚠️"; }
        .issues-list li.warning::before { content: "⚡"; }
        .issues-list li.success::before { content: "✅"; }
        .insights-list li::before {
            content: "💡";
            position: absolute;
            left: 15px;
            font-size: 16px;
        }
        .attachments {
            background-color: #e8f4fd;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .attachments h3 {
            margin-top: 0;
            color: #2980b9;
        }
        .attachment-list {
            list-style: none;
            padding: 0;
        }
        .attachment-list li {
            background-color: white;
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #7f8c8d;
            font-size: 12px;
        }
        .cta-button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: bold;
            margin: 15px 0;
        }
        .no-items {
            color: #7f8c8d;
            font-style: italic;
            text-align: center;
            padding: 20px;
        }
        @media only screen and (max-width: 600px) {
            .container { margin: 10px; padding: 20px; }
            .summary-stats { flex-direction: column; gap: 15px; }
            .score-circle { font-size: 36px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SEO Validator</h1>
            <p>Professional SEO Analysis Report</p>
        </div>

        <div class="score-section">
            <div class="score-circle">{{ $score }}/100</div>
            <div class="score-grade">Grade: {{ $grade }}</div>
        </div>

        <div class="url-info">
            <h2>{{ $url }}</h2>
            <p><strong>Report ID:</strong> {{ $reportId }} | <strong>Generated:</strong> {{ $generatedAt }}</p>
        </div>

        <div class="summary-stats">
            <div class="stat-item critical">
                <span class="stat-number">{{ count($issues) }}</span>
                <span class="stat-label">Critical Issues</span>
            </div>
            <div class="stat-item warning">
                <span class="stat-number">{{ count($warnings) }}</span>
                <span class="stat-label">Warnings</span>
            </div>
            <div class="stat-item success">
                <span class="stat-number">{{ count($successes) }}</span>
                <span class="stat-label">Optimizations</span>
            </div>
        </div>

        @if(count($insights) > 0)
        <div class="section">
            <h3>Key Insights</h3>
            <ul class="insights-list">
                @foreach(array_slice($insights, 0, 3) as $insight)
                <li>{{ $insight }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(count($issues) > 0)
        <div class="section">
            <h3>Critical Issues ({{ count($issues) }})</h3>
            <ul class="issues-list">
                @foreach(array_slice($issues, 0, 5) as $issue)
                <li class="critical">{{ $issue }}</li>
                @endforeach
                @if(count($issues) > 5)
                <li class="critical">And {{ count($issues) - 5 }} more issues in the detailed report...</li>
                @endif
            </ul>
        </div>
        @endif

        @if(count($warnings) > 0)
        <div class="section">
            <h3>Warnings ({{ count($warnings) }})</h3>
            <ul class="issues-list">
                @foreach(array_slice($warnings, 0, 3) as $warning)
                <li class="warning">{{ $warning }}</li>
                @endforeach
                @if(count($warnings) > 3)
                <li class="warning">And {{ count($warnings) - 3 }} more warnings in the detailed report...</li>
                @endif
            </ul>
        </div>
        @endif

        @if(count($successes) > 0)
        <div class="section">
            <h3>What's Working Well ({{ count($successes) }})</h3>
            <ul class="issues-list">
                @foreach(array_slice($successes, 0, 3) as $success)
                <li class="success">{{ $success }}</li>
                @endforeach
                @if(count($successes) > 3)
                <li class="success">Plus {{ count($successes) - 3 }} more optimizations!</li>
                @endif
            </ul>
        </div>
        @endif

        <div class="attachments">
            <h3>📎 Report Files Attached</h3>
            <p>This email includes detailed reports in multiple formats:</p>
            <ul class="attachment-list">
                <li>📄 <strong>PDF Report</strong> - Professional formatted report with charts and insights</li>
                <li>📊 <strong>Excel Spreadsheet</strong> - Detailed data analysis with multiple worksheets</li>
                <li>💾 <strong>JSON Data</strong> - Raw analysis data for technical integration</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('reports.view', $reportId) }}" class="cta-button">
                View Full Report Online
            </a>
        </div>

        <div class="footer">
            <p><strong>SEO Validator</strong> - Professional SEO Analysis Tool</p>
            <p>This report was automatically generated. For support, contact our team.</p>
            <p>&copy; {{ date('Y') }} SEO Validator. All rights reserved.</p>
        </div>
    </div>
</body>
</html>