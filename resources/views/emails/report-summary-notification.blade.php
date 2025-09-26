<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Report Ready</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            max-width: 500px;
            margin: 0 auto;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #3498db;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .header p {
            color: #7f8c8d;
            margin: 0;
            font-size: 14px;
        }
        .notification-badge {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 25px;
        }
        .notification-badge .icon {
            font-size: 48px;
            margin-bottom: 10px;
            display: block;
        }
        .notification-badge .message {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .notification-badge .submessage {
            font-size: 14px;
            opacity: 0.9;
        }
        .url-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #3498db;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
        }
        .url-info .url {
            font-weight: bold;
            color: #2c3e50;
            word-break: break-all;
            margin-bottom: 5px;
        }
        .url-info .meta {
            font-size: 12px;
            color: #7f8c8d;
        }
        .score-summary {
            display: flex;
            justify-content: space-between;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .score-item {
            text-align: center;
            flex: 1;
        }
        .score-number {
            display: block;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .score-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #7f8c8d;
        }
        .score-main .score-number {
            color: #3498db;
            font-size: 36px;
        }
        .score-issues .score-number { color: #e74c3c; }
        .score-warnings .score-number { color: #f39c12; }
        .quick-insights {
            margin-bottom: 25px;
        }
        .quick-insights h3 {
            color: #2c3e50;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        .insight-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .insight-list li {
            background-color: #e8f4fd;
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 4px;
            border-left: 4px solid #3498db;
            font-size: 14px;
            position: relative;
            padding-left: 35px;
        }
        .insight-list li::before {
            content: "💡";
            position: absolute;
            left: 10px;
            top: 10px;
        }
        .top-issues {
            margin-bottom: 25px;
        }
        .top-issues h3 {
            color: #e74c3c;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        .issue-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .issue-list li {
            background-color: #fdf2f2;
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 4px;
            border-left: 4px solid #e74c3c;
            font-size: 14px;
            position: relative;
            padding-left: 35px;
        }
        .issue-list li::before {
            content: "⚠️";
            position: absolute;
            left: 10px;
            top: 10px;
        }
        .cta-section {
            text-align: center;
            background-color: #e8f4fd;
            padding: 25px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .cta-button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 0;
            font-size: 16px;
        }
        .cta-button:hover {
            background-color: #2980b9;
        }
        .cta-description {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #7f8c8d;
            font-size: 12px;
        }
        .no-items {
            color: #27ae60;
            font-style: italic;
            text-align: center;
            padding: 15px;
            background-color: #f0f9f4;
            border-radius: 4px;
            border-left: 4px solid #27ae60;
        }
        @media only screen and (max-width: 480px) {
            .container { margin: 10px; padding: 20px; }
            .score-summary { flex-direction: column; gap: 15px; }
            .notification-badge .icon { font-size: 36px; }
            .score-main .score-number { font-size: 28px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SEO Validator</h1>
            <p>Your SEO analysis is complete</p>
        </div>

        <div class="notification-badge">
            <span class="icon">📊</span>
            <div class="message">Report Ready!</div>
            <div class="submessage">Your SEO analysis has been completed</div>
        </div>

        <div class="url-info">
            <div class="url">{{ $url }}</div>
            <div class="meta">
                <strong>Report ID:</strong> {{ $reportId }} •
                <strong>Generated:</strong> {{ $generatedAt }}
            </div>
        </div>

        <div class="score-summary">
            <div class="score-item score-main">
                <span class="score-number">{{ $score }}</span>
                <span class="score-label">SEO Score</span>
            </div>
            <div class="score-item score-issues">
                <span class="score-number">{{ $totalIssues }}</span>
                <span class="score-label">Issues</span>
            </div>
            <div class="score-item score-warnings">
                <span class="score-number">{{ $totalWarnings }}</span>
                <span class="score-label">Warnings</span>
            </div>
        </div>

        @if(count($keyInsights) > 0)
        <div class="quick-insights">
            <h3>🎯 Quick Insights</h3>
            <ul class="insight-list">
                @foreach($keyInsights as $insight)
                <li>{{ $insight }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(count($topIssues) > 0)
        <div class="top-issues">
            <h3>⚠️ Top Issues to Address</h3>
            <ul class="issue-list">
                @foreach($topIssues as $issue)
                <li>{{ $issue }}</li>
                @endforeach
                @if($totalIssues > count($topIssues))
                <li style="background-color: #f8f9fa; border-left-color: #7f8c8d;">
                    Plus {{ $totalIssues - count($topIssues) }} more issues in the full report
                </li>
                @endif
            </ul>
        </div>
        @else
        <div class="no-items">
            🎉 Excellent! No critical issues found in your SEO analysis.
        </div>
        @endif

        <div class="cta-section">
            <h3 style="margin-top: 0; color: #2c3e50;">View Your Complete Report</h3>
            <a href="{{ $downloadUrl }}" class="cta-button">
                📄 Download Full Report
            </a>
            <div class="cta-description">
                Get the complete analysis with detailed recommendations,<br>
                downloadable in PDF, Excel, and JSON formats.
            </div>
        </div>

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0;">
            <h4 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 14px;">📈 What's Next?</h4>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #7f8c8d;">
                <li>Review the detailed recommendations in your report</li>
                <li>Prioritize fixing critical issues first</li>
                <li>Monitor progress with regular re-analysis</li>
                <li>Share results with your development team</li>
            </ul>
        </div>

        <div class="footer">
            <p><strong>SEO Validator</strong> - Professional SEO Analysis</p>
            <p>This notification was sent because you requested an SEO analysis.</p>
            <p>&copy; {{ date('Y') }} SEO Validator. All rights reserved.</p>
        </div>
    </div>
</body>
</html>