<?php

namespace App\Http\Requests\Scans;

use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RescanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $scan = $this->route('scan');

        return $scan instanceof Scan && $this->user()->can('update', $scan);
    }

    public function rules(): array
    {
        return [
            'tier' => 'nullable|in:basic,pro,full',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $scan = $this->route('scan');
                $tier = $this->input('tier', 'basic');
                $scanService = app(ScanService::class);

                $cooldown = $scanService->checkCooldown($scan->url, $this->user()->id, $tier);

                if ($cooldown) {
                    $minutes = $cooldown['minutes_remaining'];
                    $word = $minutes === 1 ? 'minute' : 'minutes';
                    $validator->errors()->add('cooldown', "You can rescan this URL in {$minutes} {$word}. Please wait before rescanning.");
                }
            },
        ];
    }

    public function getTier(): string
    {
        return $this->input('tier', 'basic');
    }
}
