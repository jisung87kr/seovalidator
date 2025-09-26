<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled SEO Report</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            max-width: 700px;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .schedule-info {
            background-color: #e8f4fd;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            margin-bottom: 25px;
        }
        .schedule-info h2 {
            margin: 0 0 10px 0;
            color: #2980b9;
            font-size: 18px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
        }
        .info-item {
            font-size: 14px;
        }
        .info-label {
            font-weight: bold;
            color: #2c3e50;
        }
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 25px 0;
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background-color: white;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        .stat-number {
            display: block;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #7f8c8d;
            line-height: 1.2;
        }
        .total .stat-number { color: #3498db; }
        .successful .stat-number { color: #27ae60; }
        .average .stat-number { color: #9b59b6; }
        .frequency .stat-number { color: #f39c12; }
        .reports-overview {
            margin: 30px 0;
        }
        .reports-overview h3 {
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }
        .report-item {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
            position: relative;
        }
        .report-item.excellent { border-left: 4px solid #27ae60; }
        .report-item.good { border-left: 4px solid #3498db; }
        .report-item.fair { border-left: 4px solid #f39c12; }
        .report-item.poor { border-left: 4px solid #e74c3c; }
        .report-url {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .report-score {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 18px;
            font-weight: bold;
        }
        .report-score.excellent { color: #27ae60; }
        .report-score.good { color: #3498db; }
        .report-score.fair { color: #f39c12; }
        .report-score.poor { color: #e74c3c; }
        .report-summary {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        .failed-reports {
            background-color: #fdf2f2;
            border: 1px solid #fed7d7;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .failed-reports h4 {
            color: #e53e3e;
            margin: 0 0 10px 0;
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
        @media only screen and (max-width: 600px) {
            .container { margin: 10px; padding: 20px; }
            .summary-stats { grid-template-columns: 1fr 1fr; }
            .info-grid { grid-template-columns: 1fr; }
            .report-score { position: static; margin-top: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Scheduled SEO Report</h1>
            <p>Your automated SEO monitoring results are ready</p>
        </div>

        <div class="schedule-info">
            <h2>📅 Schedule Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Batch ID:</span> {{ $batchId }}
                </div>
                <div class="info-item">
                    <span class="info-label">Generated:</span> {{ $generatedAt }}
                </div>
                <div class="info-item">
                    <span class="info-label">Frequency:</span> {{ ucfirst($scheduleFrequency) }}
                </div>
                <div class="info-item">
                    <span class="info-label">URLs Monitored:</span> {{ $totalUrls }}
                </div>
            </div>
        </div>

        <div class="summary-stats">
            <div class="stat-item total">
                <span class="stat-number">{{ $totalUrls }}</span>
                <span class="stat-label">Total URLs</span>
            </div>
            <div class="stat-item successful">
                <span class="stat-number">{{ $successfulReports }}</span>
                <span class="stat-label">Successful Reports</span>
            </div>
            <div class="stat-item average">
                <span class="stat-number">{{ $averageScore }}/100</span>
                <span class="stat-label">Average Score</span>
            </div>
            <div class="stat-item frequency">
                <span class="stat-number">{{ ucfirst($scheduleFrequency) }}</span>
                <span class="stat-label">Schedule</span>
            </div>
        </div>

        @if(count($bulkResult['reports']) > 0)
        <div class="reports-overview">
            <h3>📈 Individual Report Summary</h3>
            @foreach(array_slice($bulkResult['reports'], 0, 10) as $report)
                @if($report['success'] ?? true)
                    @php
                        $score = $report['seo_score']['overall'] ?? 0;
                        $scoreClass = $score >= 80 ? 'excellent' : ($score >= 60 ? 'good' : ($score >= 40 ? 'fair' : 'poor'));
                        $summary = $report['summary'] ?? [];
                    @endphp
                    <div class="report-item {{ $scoreClass }}">
                        <div class="report-url">{{ $report['url'] }}</div>
                        <div class="report-score {{ $scoreClass }}">{{ $score }}/100</div>
                        <div class="report-summary">
                            {{ $summary['total_issues'] ?? 0 }} issues •
                            {{ $summary['total_warnings'] ?? 0 }} warnings •
                            {{ $summary['total_successes'] ?? 0 }} optimizations
                        </div>
                    </div>
                @endif
            @endforeach

            @if(count($bulkResult['reports']) > 10)
            <div class="report-item">
                <div class="report-url">And {{ count($bulkResult['reports']) - 10 }} more reports...</div>
                <div class="report-summary">View the complete analysis in the attached files</div>
            </div>
            @endif
        </div>
        @endif

        @php
            $failedReports = array_filter($bulkResult['reports'], fn($r) => !($r['success'] ?? true));
        @endphp
        @if(count($failedReports) > 0)
        <div class="failed-reports">
            <h4>⚠️ Failed Reports ({{ count($failedReports) }})</h4>
            @foreach(array_slice($failedReports, 0, 5) as $failed)
            <div>• {{ $failed['url'] }} - {{ $failed['error'] ?? 'Unknown error' }}</div>
            @endforeach
            @if(count($failedReports) > 5)
            <div>• And {{ count($failedReports) - 5 }} more failed reports...</div>
            @endif
        </div>
        @endif

        <div class="attachments">
            <h3>📎 Report Files Attached</h3>
            <p>This scheduled report includes comprehensive analysis files:</p>
            <ul class="attachment-list">
                <li>📄 <strong>Combined PDF Report</strong> - Executive summary and individual reports</li>
                <li>📊 <strong>Excel Analysis</strong> - Detailed metrics with charts and comparisons</li>
                <li>📈 <strong>Trend Data</strong> - Historical performance tracking</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('scheduled-reports.view', $batchId) }}" class="cta-button">
                View Complete Report Dashboard
            </a>
        </div>

        <div class="footer">
            <p><strong>SEO Validator</strong> - Automated SEO Monitoring</p>
            <p>This is an automated scheduled report. To modify your schedule or unsubscribe, visit your dashboard.</p>
            <p>&copy; {{ date('Y') }} SEO Validator. All rights reserved.</p>
        </div>
    </div>
</body>
</html>