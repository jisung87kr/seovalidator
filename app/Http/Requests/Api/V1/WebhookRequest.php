<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class WebhookRequest extends FormRequest
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
        $rules = [
            'url' => ['required', 'url', 'max:2048'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', 'in:analysis.completed,analysis.failed,batch.completed,batch.failed'],
            'secret' => ['sometimes', 'string', 'min:8', 'max:255'],
            'active' => ['sometimes', 'boolean'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:1000'],
        ];

        // For updates, make fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['url'][0] = 'sometimes';
            $rules['events'][0] = 'sometimes';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'url.required' => 'The webhook URL is required.',
            'url.url' => 'Please provide a valid webhook URL.',
            'url.max' => 'The webhook URL must not exceed 2048 characters.',
            'events.required' => 'At least one event type must be specified.',
            'events.array' => 'Events must be provided as an array.',
            'events.min' => 'At least one event type is required.',
            'events.*.required' => 'Each event type is required.',
            'events.*.in' => 'Invalid event type. Allowed types: analysis.completed, analysis.failed, batch.completed, batch.failed',
            'secret.min' => 'Webhook secret must be at least 8 characters.',
            'secret.max' => 'Webhook secret must not exceed 255 characters.',
            'name.max' => 'Webhook name must not exceed 255 characters.',
            'description.max' => 'Webhook description must not exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'url' => 'webhook URL',
            'events' => 'event types',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $url = $this->input('url');

            // Check if URL is reachable (basic validation)
            if ($url && !$this->isValidWebhookUrl($url)) {
                $validator->errors()->add('url', 'The webhook URL appears to be invalid or unreachable.');
            }

            // Check for duplicate events
            $events = $this->input('events', []);
            if (count($events) !== count(array_unique($events))) {
                $validator->errors()->add('events', 'Duplicate event types are not allowed.');
            }
        });
    }

    /**
     * Basic webhook URL validation
     */
    private function isValidWebhookUrl(string $url): bool
    {
        // Must be HTTPS in production
        if (app()->environment('production') && !str_starts_with($url, 'https://')) {
            return false;
        }

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