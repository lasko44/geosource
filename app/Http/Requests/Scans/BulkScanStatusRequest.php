<?php

namespace App\Http\Requests\Scans;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates bulk scan status requests.
 */
class BulkScanStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuids' => 'required|array',
            'uuids.*' => 'required|string|uuid',
        ];
    }

    public function getUuids(): array
    {
        return $this->input('uuids');
    }
}
