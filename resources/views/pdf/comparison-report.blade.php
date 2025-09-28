<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('analysis.compare_title') }}</title>
    <style>
        @font-face {
            font-family: 'NanumGothic';
            src: url('{{ asset("fonts/NanumGothic.ttf") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'NanumGothic';
            src: url('{{ asset("fonts/NanumGothicBold.ttf") }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        body {
            font-family: 'NanumGothic', 'DejaVu Sans', 'Arial Unicode MS', sans-serif;
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

        .header .date {
            font-size: 14px;
            color: #888;
            margin-top: 10px;
        }

        .comparison-grid {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .comparison-row {
            display: table-row;
        }

        .comparison-cell {
            display: table-cell;
            padding: 15px;
            vertical-align: top;
            width: 50%;
        }

        .analysis-box {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            height: 200px;
        }

        .analysis-url {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            word-break: break-all;
        }

        .analysis-score {
            font-size: 36px;
            font-weight: bold;
            color: #4f46e5;
            margin: 15px 0;
        }

        .score-breakdown {
            font-size: 12px;
            color: #666;
            margin-top: 15px;
        }

        .score-item {
            margin: 5px 0;
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

        .score-comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .score-comparison-table th,
        .score-comparison-table td {
            border: 1px solid #e5e7eb;
            padding: 12px;
            text-align: center;
        }

        .score-comparison-table th {
            background: #f9fafb;
            font-weight: bold;
            color: #374151;
        }

        .positive-diff {
            color: #059669;
            font-weight: bold;
        }

        .negative-diff {
            color: #dc2626;
            font-weight: bold;
        }

        .neutral-diff {
            color: #6b7280;
        }

        .info-comparison {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-comparison-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            font-weight: bold;
            color: #666;
            padding: 8px 15px 8px 0;
            width: 20%;
            vertical-align: top;
        }

        .info-value-1 {
            display: table-cell;
            padding: 8px 15px 8px 0;
            width: 40%;
            vertical-align: top;
            border-right: 1px solid #e5e7eb;
        }

        .info-value-2 {
            display: table-cell;
            padding: 8px 0 8px 15px;
            width: 40%;
            vertical-align: top;
        }

        .technical-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .technical-table th,
        .technical-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 12px;
            text-align: left;
        }

        .technical-table th {
            background: #f9fafb;
            font-weight: bold;
            color: #374151;
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
        <h1>{{ __('analysis.compare_title') }}</h1>
        <div class="date">{{ __('ui.generated_at') }}: {{ now()->format('Y-m-d H:i') }}</div>
    </div>

    <!-- Analysis Overview -->
    <div class="section">
        <h2 class="section-title">{{ __('analysis.score_comparison') }}</h2>

        <div class="comparison-grid">
            <div class="comparison-row">
                <div class="comparison-cell">
                    <div class="analysis-box">
                        <div class="analysis-url">{{ $comparison['analysis1']->url }}</div>
                        <div class="analysis-score">{{ number_format($comparison['analysis1']->overall_score, 1) }}</div>
                        <div class="score-breakdown">
                            <div class="score-item">{{ __('analysis.technical_seo') }}: {{ $comparison['analysis1']->technical_score ? number_format($comparison['analysis1']->technical_score, 1) : '--' }}</div>
                            <div class="score-item">{{ __('analysis.content') }}: {{ $comparison['analysis1']->content_score ? number_format($comparison['analysis1']->content_score, 1) : '--' }}</div>
                            <div class="score-item">{{ __('analysis.performance') }}: {{ $comparison['analysis1']->performance_score ? number_format($comparison['analysis1']->performance_score, 1) : '--' }}</div>
                            <div class="score-item">{{ __('analysis.accessibility') }}: {{ $comparison['analysis1']->accessibility_score ? number_format($comparison['analysis1']->accessibility_score, 1) : '--' }}</div>
                        </div>
                    </div>
                </div>
                <div class="comparison-cell">
                    <div class="analysis-box">
                        <div class="analysis-url">{{ $comparison['analysis2']->url }}</div>
                        <div class="analysis-score">{{ number_format($comparison['analysis2']->overall_score, 1) }}</div>
                        <div class="score-breakdown">
                            <div class="score-item">{{ __('analysis.technical_seo') }}: {{ $comparison['analysis2']->technical_score ? number_format($comparison['analysis2']->technical_score, 1) : '--' }}</div>
                            <div class="score-item">{{ __('analysis.content') }}: {{ $comparison['analysis2']->content_score ? number_format($comparison['analysis2']->content_score, 1) : '--' }}</div>
                            <div class="score-item">{{ __('analysis.performance') }}: {{ $comparison['analysis2']->performance_score ? number_format($comparison['analysis2']->performance_score, 1) : '--' }}</div>
                            <div class="score-item">{{ __('analysis.accessibility') }}: {{ $comparison['analysis2']->accessibility_score ? number_format($comparison['analysis2']->accessibility_score, 1) : '--' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Score Differences -->
    <div class="section">
        <h2 class="section-title">{{ __('analysis.score_differences') }}</h2>

        <table class="score-comparison-table">
            <thead>
                <tr>
                    <th>{{ __('analysis.metric') }}</th>
                    <th>{{ __('analysis.analysis_1') }}</th>
                    <th>{{ __('analysis.analysis_2') }}</th>
                    <th>{{ __('analysis.difference') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ __('analysis.overall') }}</td>
                    <td>{{ number_format($comparison['analysis1']->overall_score, 1) }}</td>
                    <td>{{ number_format($comparison['analysis2']->overall_score, 1) }}</td>
                    <td class="{{ $comparison['analysis2']->overall_score > $comparison['analysis1']->overall_score ? 'positive-diff' : ($comparison['analysis2']->overall_score < $comparison['analysis1']->overall_score ? 'negative-diff' : 'neutral-diff') }}">
                        @php
                            $diff = $comparison['analysis2']->overall_score - $comparison['analysis1']->overall_score;
                        @endphp
                        {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 1) }}
                    </td>
                </tr>
                <tr>
                    <td>{{ __('analysis.technical_seo') }}</td>
                    <td>{{ $comparison['analysis1']->technical_score ? number_format($comparison['analysis1']->technical_score, 1) : '--' }}</td>
                    <td>{{ $comparison['analysis2']->technical_score ? number_format($comparison['analysis2']->technical_score, 1) : '--' }}</td>
                    <td class="{{ ($comparison['analysis2']->technical_score ?? 0) > ($comparison['analysis1']->technical_score ?? 0) ? 'positive-diff' : (($comparison['analysis2']->technical_score ?? 0) < ($comparison['analysis1']->technical_score ?? 0) ? 'negative-diff' : 'neutral-diff') }}">
                        @php
                            $techDiff = ($comparison['analysis2']->technical_score ?? 0) - ($comparison['analysis1']->technical_score ?? 0);
                        @endphp
                        {{ $techDiff > 0 ? '+' : '' }}{{ number_format($techDiff, 1) }}
                    </td>
                </tr>
                <tr>
                    <td>{{ __('analysis.content') }}</td>
                    <td>{{ $comparison['analysis1']->content_score ? number_format($comparison['analysis1']->content_score, 1) : '--' }}</td>
                    <td>{{ $comparison['analysis2']->content_score ? number_format($comparison['analysis2']->content_score, 1) : '--' }}</td>
                    <td class="{{ ($comparison['analysis2']->content_score ?? 0) > ($comparison['analysis1']->content_score ?? 0) ? 'positive-diff' : (($comparison['analysis2']->content_score ?? 0) < ($comparison['analysis1']->content_score ?? 0) ? 'negative-diff' : 'neutral-diff') }}">
                        @php
                            $contentDiff = ($comparison['analysis2']->content_score ?? 0) - ($comparison['analysis1']->content_score ?? 0);
                        @endphp
                        {{ $contentDiff > 0 ? '+' : '' }}{{ number_format($contentDiff, 1) }}
                    </td>
                </tr>
                <tr>
                    <td>{{ __('analysis.performance') }}</td>
                    <td>{{ $comparison['analysis1']->performance_score ? number_format($comparison['analysis1']->performance_score, 1) : '--' }}</td>
                    <td>{{ $comparison['analysis2']->performance_score ? number_format($comparison['analysis2']->performance_score, 1) : '--' }}</td>
                    <td class="{{ ($comparison['analysis2']->performance_score ?? 0) > ($comparison['analysis1']->performance_score ?? 0) ? 'positive-diff' : (($comparison['analysis2']->performance_score ?? 0) < ($comparison['analysis1']->performance_score ?? 0) ? 'negative-diff' : 'neutral-diff') }}">
                        @php
                            $perfDiff = ($comparison['analysis2']->performance_score ?? 0) - ($comparison['analysis1']->performance_score ?? 0);
                        @endphp
                        {{ $perfDiff > 0 ? '+' : '' }}{{ number_format($perfDiff, 1) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Page Information Comparison -->
    @if(isset($comparison['data1']['seo_elements']['meta']) && isset($comparison['data2']['seo_elements']['meta']))
    <div class="section page-break">
        <h2 class="section-title">{{ __('analysis.page_info_comparison') }}</h2>

        <div class="info-comparison">
            <div class="info-comparison-row">
                <div class="info-label">{{ __('analysis.title') }}:</div>
                <div class="info-value-1">
                    {{ $comparison['data1']['seo_elements']['meta']['title'] ?? __('analysis.no_title_found') }}
                    @if(isset($comparison['data1']['seo_elements']['meta']['title_length']))
                        <br><small>({{ $comparison['data1']['seo_elements']['meta']['title_length'] }} {{ __('analysis.characters') }})</small>
                    @endif
                </div>
                <div class="info-value-2">
                    {{ $comparison['data2']['seo_elements']['meta']['title'] ?? __('analysis.no_title_found') }}
                    @if(isset($comparison['data2']['seo_elements']['meta']['title_length']))
                        <br><small>({{ $comparison['data2']['seo_elements']['meta']['title_length'] }} {{ __('analysis.characters') }})</small>
                    @endif
                </div>
            </div>
            <div class="info-comparison-row">
                <div class="info-label">{{ __('analysis.description') }}:</div>
                <div class="info-value-1">
                    {{ $comparison['data1']['seo_elements']['meta']['description'] ?? __('analysis.no_description_found') }}
                    @if(isset($comparison['data1']['seo_elements']['meta']['description_length']))
                        <br><small>({{ $comparison['data1']['seo_elements']['meta']['description_length'] }} {{ __('analysis.characters') }})</small>
                    @endif
                </div>
                <div class="info-value-2">
                    {{ $comparison['data2']['seo_elements']['meta']['description'] ?? __('analysis.no_description_found') }}
                    @if(isset($comparison['data2']['seo_elements']['meta']['description_length']))
                        <br><small>({{ $comparison['data2']['seo_elements']['meta']['description_length'] }} {{ __('analysis.characters') }})</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Technical Comparison -->
    @if(isset($comparison['data1']['crawl_data']) && isset($comparison['data2']['crawl_data']))
    <div class="section">
        <h2 class="section-title">{{ __('analysis.technical_comparison') }}</h2>

        <table class="technical-table">
            <thead>
                <tr>
                    <th>{{ __('analysis.metric') }}</th>
                    <th>{{ __('analysis.analysis_1') }}</th>
                    <th>{{ __('analysis.analysis_2') }}</th>
                    <th>{{ __('analysis.difference') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ __('analysis.page_size') }}</td>
                    <td>{{ isset($comparison['data1']['crawl_data']['html_size']) ? number_format($comparison['data1']['crawl_data']['html_size'] / 1024, 1) . ' KB' : '--' }}</td>
                    <td>{{ isset($comparison['data2']['crawl_data']['html_size']) ? number_format($comparison['data2']['crawl_data']['html_size'] / 1024, 1) . ' KB' : '--' }}</td>
                    <td>
                        @if(isset($comparison['data1']['crawl_data']['html_size']) && isset($comparison['data2']['crawl_data']['html_size']))
                            @php
                                $sizeDiff = ($comparison['data2']['crawl_data']['html_size'] - $comparison['data1']['crawl_data']['html_size']) / 1024;
                            @endphp
                            <span class="{{ $sizeDiff > 0 ? 'negative-diff' : ($sizeDiff < 0 ? 'positive-diff' : 'neutral-diff') }}">
                                {{ $sizeDiff > 0 ? '+' : '' }}{{ number_format($sizeDiff, 1) }} KB
                            </span>
                        @else
                            --
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>{{ __('analysis.load_time') }}</td>
                    <td>{{ isset($comparison['data1']['crawl_data']['load_time_ms']) ? number_format($comparison['data1']['crawl_data']['load_time_ms']) . ' ms' : '--' }}</td>
                    <td>{{ isset($comparison['data2']['crawl_data']['load_time_ms']) ? number_format($comparison['data2']['crawl_data']['load_time_ms']) . ' ms' : '--' }}</td>
                    <td>
                        @if(isset($comparison['data1']['crawl_data']['load_time_ms']) && isset($comparison['data2']['crawl_data']['load_time_ms']))
                            @php
                                $timeDiff = $comparison['data2']['crawl_data']['load_time_ms'] - $comparison['data1']['crawl_data']['load_time_ms'];
                            @endphp
                            <span class="{{ $timeDiff > 0 ? 'negative-diff' : ($timeDiff < 0 ? 'positive-diff' : 'neutral-diff') }}">
                                {{ $timeDiff > 0 ? '+' : '' }}{{ number_format($timeDiff) }} ms
                            </span>
                        @else
                            --
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        {{ __('ui.generated_at') }}: {{ now()->format('Y-m-d H:i') }} | SEO Validator
    </div>
</body>
</html>