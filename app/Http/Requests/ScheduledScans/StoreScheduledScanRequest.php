<?php

namespace App\Http\Requests\ScheduledScans;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates scheduled scan creation requests.
 */
class StoreScheduledScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => 'required|url',
            'name' => 'nullable|string|max:255',
            'frequency' => 'required|in:daily,weekly,monthly',
            'scheduled_time' => 'required|date_format:H:i',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:28',
        ];
    }

    public function getUrl(): string
    {
        return $this->input('url');
    }

    public function getName(): ?string
    {
        return $this->input('name');
    }

    public function getFrequency(): string
    {
        return $this->input('frequency');
    }

    public function getScheduledTime(): string
    {
        return $this->input('scheduled_time');
    }

    public function getDayOfWeek(): ?int
    {
        return $this->input('day_of_week');
    }

    public function getDayOfMonth(): ?int
    {
        return $this->input('day_of_month');
    }
}
