@extends('layouts.app')

@section('title', 'Compare Analyses')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Compare Analyses</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Compare multiple SEO analyses side by side</p>
    </div>

    <!-- Selection Panel -->
    <div class="mb-8 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Select Analyses to Compare</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">First Analysis</label>
                <select class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Select an analysis...</option>
                    <option value="1">https://example.com (Score: 78)</option>
                    <option value="2">https://test-site.com (Score: 85)</option>
                    <option value="3">https://demo.website.org (Score: 62)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Second Analysis</label>
                <select class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Select an analysis...</option>
                    <option value="1">https://example.com (Score: 78)</option>
                    <option value="2" selected>https://test-site.com (Score: 85)</option>
                    <option value="3">https://demo.website.org (Score: 62)</option>
                </select>
            </div>
        </div>
        <div class="mt-4">
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Compare
            </button>
        </div>
    </div>

    <!-- Comparison Results -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Site A -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="text-center mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">https://example.com</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Analyzed 2 hours ago</p>
                <div class="mt-4">
                    <div class="text-4xl font-bold text-yellow-600">78</div>
                    <div class="text-sm text-gray-500">Overall Score</div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Technical SEO</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-20 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 85%"></div>
                        </div>
                        <span class="text-sm font-medium text-green-600">85</span>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Content</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-20 bg-gray-200 rounded-full h-2">
                            <div class="bg-yellow-600 h-2 rounded-full" style="width: 72%"></div>
                        </div>
                        <span class="text-sm font-medium text-yellow-600">72</span>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Performance</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-20 bg-gray-200 rounded-full h-2">
                            <div class="bg-red-600 h-2 rounded-full" style="width: 68%"></div>
                        </div>
                        <span class="text-sm font-medium text-red-600">68</span>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Accessibility</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-20 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 83%"></div>
                        </div>
                        <span class="text-sm font-medium text-green-600">83</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Site B -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="text-center mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">https://test-site.com</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Analyzed 1 day ago</p>
                <div class="mt-4">
                    <div class="text-4xl font-bold text-green-600">85</div>
                    <div class="text-sm text-gray-500">Overall Score</div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Technical SEO</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-20 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 88%"></div>
                        </div>
                        <span class="text-sm font-medium text-green-600">88</span>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Content</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-20 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 82%"></div>
                        </div>
                        <span class="text-sm font-medium text-green-600">82</span>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Performance</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-20 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 85%"></div>
                        </div>
                        <span class="text-sm font-medium text-green-600">85</span>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Accessibility</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-20 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 85%"></div>
                        </div>
                        <span class="text-sm font-medium text-green-600">85</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparison Chart -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Score Comparison</h3>
        <div class="h-64">
            <canvas id="comparisonChart"></canvas>
        </div>
    </div>

    <!-- Detailed Differences -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Key Differences</h3>
        <div class="space-y-4">
            <div class="border-l-4 border-green-500 bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-green-800">test-site.com performs better in:</h4>
                        <div class="mt-2 text-sm text-green-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Content quality (+10 points)</li>
                                <li>Page load performance (+17 points)</li>
                                <li>Technical SEO implementation (+3 points)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-l-4 border-yellow-500 bg-yellow-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-yellow-800">Areas where example.com can improve:</h4>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Optimize images to reduce page load time</li>
                                <li>Improve content readability and structure</li>
                                <li>Add missing meta descriptions</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('comparisonChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Technical SEO', 'Content', 'Performance', 'Accessibility'],
                datasets: [{
                    label: 'example.com',
                    data: [85, 72, 68, 83],
                    backgroundColor: 'rgba(245, 158, 11, 0.2)',
                    borderColor: 'rgba(245, 158, 11, 1)',
                    borderWidth: 2
                }, {
                    label: 'test-site.com',
                    data: [88, 82, 85, 85],
                    backgroundColor: 'rgba(34, 197, 94, 0.2)',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }
});
</script>
@endsection