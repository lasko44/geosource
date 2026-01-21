<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class ScanAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'scan_id',
        'team_id',
        'event',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Event constants
    public const EVENT_SCAN_CREATED = 'scan_created';
    public const EVENT_SCAN_RESCAN = 'scan_rescan';
    public const EVENT_QUOTA_EXCEEDED = 'quota_exceeded';
    public const EVENT_MEMBER_LIMIT_EXCEEDED = 'member_limit_exceeded';
    public const EVENT_TEAM_QUOTA_EXCEEDED = 'team_quota_exceeded';
    public const EVENT_SCAN_DELETED = 'scan_deleted';
    public const EVENT_UNAUTHORIZED_ACCESS = 'unauthorized_access';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Log a scan event.
     */
    public static function log(
        string $event,
        ?User $user = null,
        ?Scan $scan = null,
        ?Team $team = null,
        ?Request $request = null,
        array $metadata = []
    ): self {
        return self::create([
            'user_id' => $user?->id,
            'scan_id' => $scan?->id,
            'team_id' => $team?->id ?? $scan?->team_id,
            'event' => $event,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? substr($request->userAgent(), 0, 255) : null,
            'metadata' => $metadata ?: null,
        ]);
    }

    /**
     * Log a scan creation event.
     */
    public static function logScanCreated(Scan $scan, User $user, Request $request, array $metadata = []): self
    {
        return self::log(
            self::EVENT_SCAN_CREATED,
            $user,
            $scan,
            $scan->team,
            $request,
            array_merge([
                'url' => $scan->url,
                'context' => $scan->team_id ? 'team' : 'personal',
            ], $metadata)
        );
    }

    /**
     * Log a rescan event.
     */
    public static function logRescan(Scan $newScan, Scan $originalScan, User $user, Request $request): self
    {
        return self::log(
            self::EVENT_SCAN_RESCAN,
            $user,
            $newScan,
            $newScan->team,
            $request,
            [
                'original_scan_id' => $originalScan->id,
                'original_scan_uuid' => $originalScan->uuid,
                'url' => $newScan->url,
            ]
        );
    }

    /**
     * Log a quota exceeded event.
     */
    public static function logQuotaExceeded(User $user, Request $request, string $quotaType, array $metadata = []): self
    {
        $event = match ($quotaType) {
            'personal' => self::EVENT_QUOTA_EXCEEDED,
            'team' => self::EVENT_TEAM_QUOTA_EXCEEDED,
            'member' => self::EVENT_MEMBER_LIMIT_EXCEEDED,
            default => self::EVENT_QUOTA_EXCEEDED,
        };

        return self::log(
            $event,
            $user,
            null,
            $metadata['team'] ?? null,
            $request,
            $metadata
        );
    }
}
