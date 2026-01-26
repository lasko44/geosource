<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email_template_id',
        'status',
        'audience',
        'audience_filters',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'sent_count',
        'failed_count',
        'opened_count',
        'clicked_count',
        'created_by',
    ];

    protected $casts = [
        'audience_filters' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sends(): HasMany
    {
        return $this->hasMany(EmailCampaignSend::class);
    }

    /**
     * Get the audience query builder based on audience type.
     */
    public function getAudienceQuery()
    {
        $query = User::query()
            ->whereNotNull('email_verified_at')
            ->whereDoesntHave('marketingUnsubscribe');

        switch ($this->audience) {
            case 'free':
                $query->whereDoesntHave('subscriptions');
                break;
            case 'pro':
                $query->whereHas('subscriptions', function ($q) {
                    $q->where('stripe_price', 'like', '%pro%');
                });
                break;
            case 'agency':
                $query->whereHas('subscriptions', function ($q) {
                    $q->where('stripe_price', 'like', '%agency%');
                });
                break;
            case 'custom':
                // Apply custom filters from audience_filters
                if (!empty($this->audience_filters)) {
                    foreach ($this->audience_filters as $filter) {
                        // Implement custom filter logic here
                    }
                }
                break;
            case 'all':
            default:
                // No additional filters
                break;
        }

        return $query;
    }

    /**
     * Get recipient count for this campaign.
     */
    public function getRecipientCount(): int
    {
        return $this->getAudienceQuery()->count();
    }

    /**
     * Check if campaign can be sent.
     */
    public function canBeSent(): bool
    {
        return in_array($this->status, ['draft', 'scheduled']);
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return round(($this->sent_count + $this->failed_count) / $this->total_recipients * 100, 1);
    }

    /**
     * Get open rate percentage.
     */
    public function getOpenRate(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }

        return round($this->opened_count / $this->sent_count * 100, 1);
    }

    /**
     * Get click rate percentage.
     */
    public function getClickRate(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }

        return round($this->clicked_count / $this->sent_count * 100, 1);
    }
}
