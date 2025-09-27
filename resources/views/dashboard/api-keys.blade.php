@extends('layouts.app')

@section('title', 'API Keys')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">API Keys</h1>
        <p class="mt-2 text-gray-600">Manage your API keys for programmatic access to SEO analysis</p>
    </div>

    <!-- API Documentation -->
    <div class="mb-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">API Documentation</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Use these API keys to integrate SEO analysis into your applications. View our <a href="#" class="underline hover:text-blue-800">API documentation</a> for detailed usage instructions.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Create New API Key -->
    <div class="mb-8 bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Create New API Key</h2>
        <form class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Key Name</label>
                    <input type="text" placeholder="e.g., Production App, Development" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Rate Limit</label>
                    <select class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="100">100 requests/hour</option>
                        <option value="500">500 requests/hour</option>
                        <option value="1000">1000 requests/hour</option>
                        <option value="unlimited">Unlimited</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Description (Optional)</label>
                <textarea rows="3" placeholder="Describe what this API key will be used for..." class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            </div>
            <div>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create API Key
                </button>
            </div>
        </form>
    </div>

    <!-- Existing API Keys -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Your API Keys</h3>

            <div class="space-y-4">
                <!-- API Key 1 -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-4">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">Production App</h4>
                                    <p class="text-sm text-gray-500">Created 3 days ago • Last used 2 hours ago</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        1000 req/hour
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="flex items-center space-x-2">
                                    <code class="px-2 py-1 bg-gray-100 rounded text-sm font-mono text-gray-900">
                                        seo_1234567890abcdef••••••••••••••••••••
                                    </code>
                                    <button class="text-sm text-indigo-600 hover:text-indigo-800">Copy</button>
                                    <button class="text-sm text-gray-600 hover:text-gray-800">Show</button>
                                </div>
                            </div>
                            <div class="mt-2">
                                <p class="text-sm text-gray-600">Used for production website SEO monitoring</p>
                            </div>
                        </div>
                        <div class="ml-4 flex items-center space-x-2">
                            <button class="text-sm text-gray-600 hover:text-gray-800">Edit</button>
                            <button class="text-sm text-red-600 hover:text-red-800">Delete</button>
                        </div>
                    </div>
                    <!-- Usage Stats -->
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">1,247</div>
                                <div class="text-sm text-gray-500">Total Requests</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">87</div>
                                <div class="text-sm text-gray-500">This Hour</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">99.2%</div>
                                <div class="text-sm text-gray-500">Success Rate</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Key 2 -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-4">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">Development Testing</h4>
                                    <p class="text-sm text-gray-500">Created 1 week ago • Last used 1 day ago</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        100 req/hour
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="flex items-center space-x-2">
                                    <code class="px-2 py-1 bg-gray-100 rounded text-sm font-mono text-gray-900">
                                        seo_abcdef1234567890••••••••••••••••••••
                                    </code>
                                    <button class="text-sm text-indigo-600 hover:text-indigo-800">Copy</button>
                                    <button class="text-sm text-gray-600 hover:text-gray-800">Show</button>
                                </div>
                            </div>
                            <div class="mt-2">
                                <p class="text-sm text-gray-600">For testing and development purposes</p>
                            </div>
                        </div>
                        <div class="ml-4 flex items-center space-x-2">
                            <button class="text-sm text-gray-600 hover:text-gray-800">Edit</button>
                            <button class="text-sm text-red-600 hover:text-red-800">Delete</button>
                        </div>
                    </div>
                    <!-- Usage Stats -->
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">342</div>
                                <div class="text-sm text-gray-500">Total Requests</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">0</div>
                                <div class="text-sm text-gray-500">This Hour</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">98.8%</div>
                                <div class="text-sm text-gray-500">Success Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State (when no keys exist) -->
            <!-- <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a2 2 0 012 2m-2-2h.01M9 5a2 2 0 00-2 2v6a2 2 0 002 2h6a2 2 0 002-2V7a2 2 0 00-2-2H9z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No API keys</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first API key.</p>
            </div> -->
        </div>
    </div>

    <!-- Usage Guidelines -->
    <div class="mt-8 bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Usage Guidelines</h3>
        <div class="space-y-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-700">Keep your API keys secure and never share them publicly</p>
                </div>
            </div>
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-700">Use environment variables to store API keys in your applications</p>
                </div>
            </div>
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-700">Rotate keys regularly and delete unused ones</p>
                </div>
            </div>
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-700">Monitor your usage to avoid hitting rate limits</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection