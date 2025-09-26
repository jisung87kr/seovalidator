<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Combined SEO Analysis Report</title>
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
        .batch-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            margin-bottom: 25px;
        }
        .batch-info h2 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 18px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .info-item {
            background-color: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            text-align: center;
        }
        .info-number {
            display: block;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #3498db;
        }
        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #7f8c8d;
        }
        .performance-overview {
            background-color: #e8f5e8;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .performance-overview h3 {
            color: #27ae60;
            margin-top: 0;
        }
        .score-distribution {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        .score-range {
            text-align: center;
            padding: 10px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .excellent { background-color: #27ae60; }
        .good { background-color: #3498db; }
        .fair { background-color: #f39c12; }
        .poor { background-color: #e74c3c; }
        .critical { background-color: #8e44ad; }
        .reports-section {
            margin: 30px 0;
        }
        .reports-section h3 {
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }
        .report-grid {
            display: grid;
            gap: 15px;
        }
        .report-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            position: relative;
            transition: box-shadow 0.2s;
        }
        .report-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .report-card.excellent { border-left: 4px solid #27ae60; }
        .report-card.good { border-left: 4px solid #3498db; }
        .report-card.fair { border-left: 4px solid #f39c12; }
        .report-card.poor { border-left: 4px solid #e74c3c; }
        .report-url {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 16px;
            word-break: break-all;
        }
        .report-score {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            font-weight: bold;
        }
        .report-score.excellent { color: #27ae60; }
        .report-score.good { color: #3498db; }
        .report-score.fair { color: #f39c12; }
        .report-score.poor { color: #e74c3c; }
        .report-metrics {
            display: flex;
            gap: 15px;
            margin-top: 10px;
            font-size: 13px;
        }
        .metric {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .metric.issues { color: #e74c3c; }
        .metric.warnings { color: #f39c12; }
        .metric.successes { color: #27ae60; }
        .top-insights {
            background-color: #e8f4fd;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .top-insights h3 {
            color: #2980b9;
            margin-top: 0;
        }
        .insight-item {
            background-color: white;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 6px;
            border-left: 4px solid #3498db;
        }
        .failed-section {
            background-color: #fdf2f2;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #e74c3c;
            margin: 25px 0;
        }
        .failed-section h3 {
            color: #e53e3e;
            margin-top: 0;
        }
        .failed-item {
            background-color: white;
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 4px;
            border: 1px solid #fed7d7;
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
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            gap: 10px;
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
            .info-grid { grid-template-columns: 1fr; }
            .score-distribution { grid-template-columns: 1fr; }
            .report-score { position: static; margin-top: 10px; }
            .report-metrics { flex-direction: column; gap: 5px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Combined SEO Analysis</h1>
            <p>Multi-URL SEO Performance Report</p>
        </div>

        <div class="batch-info">
            <h2>📋 Batch Summary</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-number">{{ $summary['total_urls'] }}</span>
                    <span class="info-label">Total URLs</span>
                </div>
                <div class="info-item">
                    <span class="info-number">{{ $summary['successful_reports'] }}</span>
                    <span class="info-label">Successful</span>
                </div>
                <div class="info-item">
                    <span class="info-number">{{ $summary['average_seo_score'] }}/100</span>
                    <span class="info-label">Avg Score</span>
                </div>
                <div class="info-item">
                    <span class="info-number">{{ $summary['total_issues_found'] }}</span>
                    <span class="info-label">Total Issues</span>
                </div>
            </div>
        </div>

        <div class="performance-overview">
            <h3>🎯 Performance Distribution</h3>
            <p>SEO score distribution across all analyzed URLs:</p>
            <div class="score-distribution">
                @php
                    $excellent = count(array_filter($reports, fn($r) => ($r['seo_score']['overall'] ?? 0) >= 90));
                    $good = count(array_filter($reports, fn($r) => ($r['seo_score']['overall'] ?? 0) >= 80 && ($r['seo_score']['overall'] ?? 0) < 90));
                    $fair = count(array_filter($reports, fn($r) => ($r['seo_score']['overall'] ?? 0) >= 60 && ($r['seo_score']['overall'] ?? 0) < 80));
                    $poor = count(array_filter($reports, fn($r) => ($r['seo_score']['overall'] ?? 0) >= 40 && ($r['seo_score']['overall'] ?? 0) < 60));
                    $critical = count(array_filter($reports, fn($r) => ($r['seo_score']['overall'] ?? 0) < 40));
                @endphp
                <div class="score-range excellent">{{ $excellent }}<br>90-100</div>
                <div class="score-range good">{{ $good }}<br>80-89</div>
                <div class="score-range fair">{{ $fair }}<br>60-79</div>
                <div class="score-range poor">{{ $poor }}<br>40-59</div>
                <div class="score-range critical">{{ $critical }}<br>&lt;40</div>
            </div>
        </div>

        @if(count($reports) > 0)
        <div class="reports-section">
            <h3>📈 Individual URL Performance</h3>
            <div class="report-grid">
                @foreach(array_slice($reports, 0, 8) as $report)
                    @php
                        $score = $report['seo_score']['overall'] ?? 0;
                        $scoreClass = $score >= 80 ? 'excellent' : ($score >= 60 ? 'good' : ($score >= 40 ? 'fair' : 'poor'));
                        $summary = $report['summary'] ?? [];
                    @endphp
                    <div class="report-card {{ $scoreClass }}">
                        <div class="report-url">{{ $report['url'] }}</div>
                        <div class="report-score {{ $scoreClass }}">{{ $score }}/100</div>
                        <div class="report-metrics">
                            <div class="metric issues">
                                <span>⚠️</span>
                                <span>{{ $summary['total_issues'] ?? 0 }} issues</span>
                            </div>
                            <div class="metric warnings">
                                <span>⚡</span>
                                <span>{{ $summary['total_warnings'] ?? 0 }} warnings</span>
                            </div>
                            <div class="metric successes">
                                <span>✅</span>
                                <span>{{ $summary['total_successes'] ?? 0 }} optimized</span>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if(count($reports) > 8)
                <div class="report-card">
                    <div class="report-url">+ {{ count($reports) - 8 }} more URLs</div>
                    <div style="margin-top: 10px; color: #7f8c8d; font-size: 14px;">
                        View complete analysis in attached files
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        @if(!empty($failedReports))
        <div class="failed-section">
            <h3>⚠️ Failed Analysis ({{ count($failedReports) }})</h3>
            @foreach(array_slice($failedReports, 0, 5) as $failed)
            <div class="failed-item">
                <strong>{{ $failed['url'] }}</strong><br>
                <span style="color: #7f8c8d; font-size: 13px;">Error: {{ $failed['error'] ?? 'Unknown error occurred' }}</span>
            </div>
            @endforeach
            @if(count($failedReports) > 5)
            <div class="failed-item">
                <span style="color: #7f8c8d;">And {{ count($failedReports) - 5 }} more failed analyses...</span>
            </div>
            @endif
        </div>
        @endif

        <div class="top-insights">
            <h3>💡 Key Insights</h3>
            @php
                $avgScore = $summary['average_seo_score'];
                $totalIssues = $summary['total_issues_found'];
                $successRate = round(($summary['successful_reports'] / $summary['total_urls']) * 100);
            @endphp
            <div class="insight-item">
                <strong>Overall Performance:</strong> Your URLs have an average SEO score of {{ $avgScore }}/100,
                @if($avgScore >= 80)
                    which indicates excellent SEO optimization across your website.
                @elseif($avgScore >= 60)
                    which shows good SEO foundation with room for improvement.
                @else
                    which suggests significant optimization opportunities exist.
                @endif
            </div>
            <div class="insight-item">
                <strong>Success Rate:</strong> {{ $successRate }}% of URLs were successfully analyzed,
                with {{ $totalIssues }} total issues identified across all pages.
            </div>
            @if($totalIssues > 0)
            <div class="insight-item">
                <strong>Priority Action:</strong> Focus on addressing critical issues first,
                as they have the most significant impact on search engine rankings.
            </div>
            @endif
        </div>

        <div class="attachments">
            <h3>📎 Comprehensive Report Files</h3>
            <p>This combined analysis includes detailed reports in multiple formats:</p>
            <ul class="attachment-list">
                <li>
                    <span>📄</span>
                    <div>
                        <strong>Combined PDF Report</strong><br>
                        <span style="color: #7f8c8d; font-size: 12px;">Executive summary + individual URL analyses</span>
                    </div>
                </li>
                <li>
                    <span>📊</span>
                    <div>
                        <strong>Excel Analysis Workbook</strong><br>
                        <span style="color: #7f8c8d; font-size: 12px;">Multi-sheet analysis with charts and metrics</span>
                    </div>
                </li>
                <li>
                    <span>📈</span>
                    <div>
                        <strong>Comparison Data</strong><br>
                        <span style="color: #7f8c8d; font-size: 12px;">Side-by-side URL performance comparisons</span>
                    </div>
                </li>
            </ul>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('combined-reports.view', $batchId) }}" class="cta-button">
                View Interactive Dashboard
            </a>
        </div>

        <div class="footer">
            <p><strong>SEO Validator</strong> - Professional Multi-URL SEO Analysis</p>
            <p>Generated: {{ $generatedAt }} | Batch ID: {{ $batchId }}</p>
            <p>&copy; {{ date('Y') }} SEO Validator. All rights reserved.</p>
        </div>
    </div>
</body>
</html>