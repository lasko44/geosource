<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'visitor_hash',
        'user_id',
        'url',
        'path',
        'page_type',
        'page_id',
        'page_title',
        'referrer',
        'referrer_host',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'country',
        'country_code',
        'region',
        'city',
        'device_type',
        'browser',
        'browser_version',
        'os',
        'os_version',
        'ip_address',
        'user_agent',
        'is_bot',
        'created_at',
    ];

    protected $casts = [
        'is_bot' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user who viewed the page.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by page type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('page_type', $type);
    }

    /**
     * Scope for filtering by date range.
     */
    public function scopeBetween($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope for filtering out bots.
     */
    public function scopeHumansOnly($query)
    {
        return $query->where('is_bot', false);
    }

    /**
     * Scope for filtering by referrer.
     */
    public function scopeFromReferrer($query, string $host)
    {
        return $query->where('referrer_host', $host);
    }

    /**
     * Scope for filtering by UTM source.
     */
    public function scopeFromUtmSource($query, string $source)
    {
        return $query->where('utm_source', $source);
    }

    /**
     * Scope for filtering by country.
     */
    public function scopeFromCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Get unique visitors count.
     */
    public static function uniqueVisitors($query = null)
    {
        $query = $query ?? static::query();
        return $query->distinct('visitor_hash')->count('visitor_hash');
    }
}
