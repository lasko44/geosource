<?php

namespace App\Http\Requests\Citations;

use App\Services\Citation\CitationService;
use Illuminate\Foundation\Http\FormRequest;

class MarkCitationAlertsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(CitationService::class)->canAccessCitations($this->user());
    }

    public function rules(): array
    {
        return [
            'alert_ids' => 'required|array',
            'alert_ids.*' => 'integer',
        ];
    }
}
