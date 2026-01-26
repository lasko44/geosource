<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'subject',
        'preview_text',
        'content',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(EmailCampaign::class);
    }

    /**
     * Render the email content with variables replaced.
     */
    public function render(array $variables = []): string
    {
        $content = $this->content;

        // Default variables
        $defaults = [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'current_year' => date('Y'),
        ];

        $variables = array_merge($defaults, $variables);

        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
            $content = str_replace('{{ ' . $key . ' }}', $value, $content);
        }

        return $content;
    }

    /**
     * Get available template variables.
     */
    public static function getAvailableVariables(): array
    {
        return [
            'user_name' => 'Recipient\'s name',
            'user_email' => 'Recipient\'s email',
            'user_first_name' => 'Recipient\'s first name',
            'app_name' => 'Application name',
            'app_url' => 'Application URL',
            'unsubscribe_url' => 'Unsubscribe link (required)',
            'current_year' => 'Current year',
        ];
    }
}
