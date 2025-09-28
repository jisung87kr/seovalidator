<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('analysis.analysis_results') }} - {{ $analysis->url }}</title>
    <style>
        body {
            font-family: 'Noto Sans CJK KR', 'NanumGothic', 'Malgun Gothic', 'Arial Unicode MS', 'DejaVu Sans', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #4f46e5;
            margin: 0;
            font-size: 24px;
        }

        .header .url {
            font-size: 16px;
            color: #666;
            margin-top: 10px;
            word-break: break-all;
        }

        .header .date {
            font-size: 14px;
            color: #888;
            margin-top: 5px;
        }

        .score-section {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }

        .overall-score {
            font-size: 48px;
            font-weight: bold;
            color: #4f46e5;
            margin: 10px 0;
        }

        .score-label {
            font-size: 18px;
            color: #666;
            margin-bottom: 20px;
        }

        .score-breakdown {
            display: table;
            width: 100%;
            margin-top: 20px;
        }

        .score-item {
            display: table-cell;
            text-align: center;
            padding: 10px;
            border-right: 1px solid #e5e7eb;
        }

        .score-item:last-child {
            border-right: none;
        }

        .score-item-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .score-item-value {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }

        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            font-weight: bold;
            color: #666;
            padding: 8px 15px 8px 0;
            width: 30%;
            vertical-align: top;
        }

        .info-value {
            display: table-cell;
            padding: 8px 0;
            vertical-align: top;
            word-break: break-word;
        }

        .recommendations {
            margin-top: 20px;
        }

        .recommendation-category {
            margin-bottom: 20px;
        }

        .recommendation-header {
            font-weight: bold;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .critical {
            background: #fee2e2;
            color: #991b1b;
        }

        .warning {
            background: #fef3c7;
            color: #92400e;
        }

        .good {
            background: #d1fae5;
            color: #065f46;
        }

        .recommendation-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .recommendation-list li {
            padding: 5px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .recommendation-list li:last-child {
            border-bottom: none;
        }

        .technical-details {
            background: #f9fafb;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #888;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ __('analysis.analysis_results') }}</h1>
        <div class="url">{{ $analysis->url }}</div>
        <div class="date">{{ __('analysis.analyzed') }}: {{ $analysis->created_at->format('Y-m-d H:i') }}</div>
    </div>

    <!-- Overall Score Section -->
    <div class="score-section">
        <div class="score-label">{{ __('analysis.overall_seo_score') }}</div>
        <div class="overall-score">{{ $analysis->overall_score ? number_format($analysis->overall_score, 1) : '--' }}</div>

        <div class="score-breakdown">
            <div class="score-item">
                <div class="score-item-label">{{ __('analysis.technical_seo') }}</div>
                <div class="score-item-value">{{ $analysis->technical_score ? number_format($analysis->technical_score, 1) : '--' }}</div>
            </div>
            <div class="score-item">
                <div class="score-item-label">{{ __('analysis.content_quality') }}</div>
                <div class="score-item-value">{{ $analysis->content_score ? number_format($analysis->content_score, 1) : '--' }}</div>
            </div>
            <div class="score-item">
                <div class="score-item-label">{{ __('analysis.performance') }}</div>
                <div class="score-item-value">{{ $analysis->performance_score ? number_format($analysis->performance_score, 1) : '--' }}</div>
            </div>
            <div class="score-item">
                <div class="score-item-label">{{ __('analysis.accessibility') }}</div>
                <div class="score-item-value">{{ $analysis->accessibility_score ? number_format($analysis->accessibility_score, 1) : '--' }}</div>
            </div>
        </div>
    </div>

    <!-- Page Information -->
    @if(isset($analysisData['seo_elements']['meta']))
    <div class="section">
        <h2 class="section-title">{{ __('analysis.page_information') }}</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">{{ __('analysis.page_title') }}:</div>
                <div class="info-value">
                    {{ $analysisData['seo_elements']['meta']['title'] ?? __('analysis.no_title_found') }}
                    @if(isset($analysisData['seo_elements']['meta']['title_length']))
                        <br><small>({{ __('analysis.length') }}: {{ $analysisData['seo_elements']['meta']['title_length'] }} {{ __('analysis.characters') }})</small>
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">{{ __('analysis.meta_description') }}:</div>
                <div class="info-value">
                    {{ $analysisData['seo_elements']['meta']['description'] ?? __('analysis.no_description_found') }}
                    @if(isset($analysisData['seo_elements']['meta']['description_length']))
                        <br><small>({{ __('analysis.length') }}: {{ $analysisData['seo_elements']['meta']['description_length'] }} {{ __('analysis.characters') }})</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Technical Details -->
    @if(isset($analysisData['crawl_data']))
    <div class="section">
        <h2 class="section-title">{{ __('analysis.technical_details') }}</h2>
        <div class="info-grid">
            @if(isset($analysisData['crawl_data']['html_size']))
            <div class="info-row">
                <div class="info-label">{{ __('analysis.page_size') }}:</div>
                <div class="info-value">{{ number_format($analysisData['crawl_data']['html_size'] / 1024, 1) }} KB</div>
            </div>
            @endif
            @if(isset($analysisData['crawl_data']['load_time_ms']))
            <div class="info-row">
                <div class="info-label">{{ __('analysis.load_time') }}:</div>
                <div class="info-value">{{ number_format($analysisData['crawl_data']['load_time_ms']) }} ms</div>
            </div>
            @endif
            @if(isset($analysisData['crawl_data']['status_code']))
            <div class="info-row">
                <div class="info-label">{{ __('analysis.status_code') }}:</div>
                <div class="info-value">{{ $analysisData['crawl_data']['status_code'] }}</div>
            </div>
            @endif
            @if(isset($analysisData['seo_elements']['images']['total']))
            <div class="info-row">
                <div class="info-label">{{ __('analysis.total_images') }}:</div>
                <div class="info-value">{{ $analysisData['seo_elements']['images']['total'] }}</div>
            </div>
            @endif
            @if(isset($analysisData['seo_elements']['images']['missing_alt']))
            <div class="info-row">
                <div class="info-label">{{ __('analysis.images_missing_alt') }}:</div>
                <div class="info-value">{{ $analysisData['seo_elements']['images']['missing_alt'] }}</div>
            </div>
            @endif
            @if(isset($analysisData['seo_elements']['links']['total']))
            <div class="info-row">
                <div class="info-label">{{ __('analysis.total_links') }}:</div>
                <div class="info-value">{{ $analysisData['seo_elements']['links']['total'] }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Recommendations -->
    <div class="section page-break">
        <h2 class="section-title">{{ __('analysis.issues_and_recommendations') }}</h2>

        <div class="recommendation-category">
            <div class="recommendation-header critical">{{ __('analysis.critical_issues') }}</div>
            <ul class="recommendation-list">
                <li>{{ __('analysis.missing_meta_description') }}</li>
                <li>{{ __('analysis.large_page_size') }}</li>
                <li>{{ __('analysis.images_without_alt') }}</li>
            </ul>
        </div>

        <div class="recommendation-category">
            <div class="recommendation-header warning">{{ __('analysis.warnings') }}</div>
            <ul class="recommendation-list">
                <li>{{ __('analysis.h1_not_descriptive') }}</li>
                <li>{{ __('analysis.optimize_image_compression') }}</li>
                <li>{{ __('analysis.internal_links_lack_text') }}</li>
            </ul>
        </div>

        <div class="recommendation-category">
            <div class="recommendation-header good">{{ __('analysis.good_practices') }}</div>
            <ul class="recommendation-list">
                <li>{{ __('analysis.title_tag_optimized') }}</li>
                <li>{{ __('analysis.good_heading_structure') }}</li>
                <li>{{ __('analysis.mobile_responsive') }}</li>
            </ul>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        {{ __('ui.generated_at') }}: {{ now()->format('Y-m-d H:i') }} | SEO Validator
    </div>
</body>
</html>
