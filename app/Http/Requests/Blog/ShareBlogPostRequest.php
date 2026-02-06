<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates blog post share tracking requests.
 */
class ShareBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => 'required|string|in:twitter,linkedin,facebook,copy_link',
        ];
    }

    public function getPlatform(): string
    {
        return $this->input('platform');
    }
}
