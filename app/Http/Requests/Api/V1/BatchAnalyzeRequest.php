<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class BatchAnalyzeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'urls' => ['required', 'array', 'min:1', 'max:10'],
            'urls.*' => ['required', 'url', 'max:2048'],
            'options' => ['sometimes', 'array'],
            'options.force_refresh' => ['sometimes', 'boolean'],
            'options.include_quality_analysis' => ['sometimes', 'boolean'],
            'options.include_technical_analysis' => ['sometimes', 'boolean'],
            'options.include_content_analysis' => ['sometimes', 'boolean'],
            'options.include_performance_analysis' => ['sometimes', 'boolean'],
            'options.javascript_enabled' => ['sometimes', 'boolean'],
            'options.mobile_analysis' => ['sometimes', 'boolean'],
            'options.timeout' => ['sometimes', 'integer', 'min:5', 'max:60'],
            'webhook_url' => ['sometimes', 'url', 'max:2048'],
            'async' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'urls.required' => 'At least one URL is required for batch analysis.',
            'urls.array' => 'URLs must be provided as an array.',
            'urls.min' => 'At least one URL is required.',
            'urls.max' => 'Maximum of 10 URLs allowed per batch.',
            'urls.*.required' => 'Each URL in the batch is required.',
            'urls.*.url' => 'Each item must be a valid URL.',
            'urls.*.max' => 'Each URL must not exceed 2048 characters.',
            'webhook_url.url' => 'Please provide a valid webhook URL.',
            'options.timeout.min' => 'Timeout must be at least 5 seconds.',
            'options.timeout.max' => 'Timeout must not exceed 60 seconds.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'urls' => 'URLs',
            'webhook_url' => 'webhook URL',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $urls = $this->input('urls', []);

            // Check for duplicate URLs
            if (count($urls) !== count(array_unique($urls))) {
                $validator->errors()->add('urls', 'Duplicate URLs are not allowed in batch analysis.');
            }

            // Validate each URL
            foreach ($urls as $index => $url) {
                if (!$this->isValidUrl($url)) {
                    $validator->errors()->add("urls.{$index}", 'This URL appears to be invalid or unreachable.');
                }
            }
        });
    }

    /**
     * Basic URL validation
     */
    private function isValidUrl(string $url): bool
    {
        // Check for localhost/private IPs in production
        if (app()->environment('production')) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host && (
                $host === 'localhost' ||
                filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
            )) {
                return false;
            }
        }

        return true;
    }
}