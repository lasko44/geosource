<?php

namespace App\Http\Requests\Scans;

use App\Models\Scan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates requests to email a scan report.
 */
class EmailScanReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $scan = $this->route('scan');

        return $scan instanceof Scan && $this->user()->can('view', $scan);
    }

    public function rules(): array
    {
        return [
            'email' => 'nullable|email|max:255',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $user = $this->user();

                if ($user->is_admin) {
                    return;
                }

                $cache = app('cache');
                $scan = $this->route('scan');

                // Global rate limit: 10 emails per hour per user
                $globalKey = "email_report_hourly:{$user->id}";
                if ((int) $cache->get($globalKey, 0) >= 10) {
                    $validator->errors()->add('email', 'You have reached the hourly email limit (10 emails/hour). Please try again later.');

                    return;
                }

                // Per-scan rate limit: 3 emails per scan per day
                $scanKey = "email_report_scan:{$user->id}:{$scan->id}";
                if ((int) $cache->get($scanKey, 0) >= 3) {
                    $validator->errors()->add('email', 'You have already sent this report 3 times today. Please try again tomorrow.');
                }
            },
        ];
    }

    public function getRecipientEmail(): string
    {
        return $this->input('email', $this->user()->email);
    }
}
