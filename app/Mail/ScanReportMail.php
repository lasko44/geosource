<?php

namespace App\Mail;

use App\Models\Scan;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ScanReportMail extends Mailable
{

    public array $pdfData;

    public function __construct(
        public Scan $scan,
        public User $user,
        public ?string $recipientEmail = null,
    ) {
        $this->preparePdfData();
    }

    public function envelope(): Envelope
    {
        // Use white label company name if enabled
        $companyName = 'GeoSource.ai';
        if ($this->scan->team_id && $this->scan->team) {
            $whiteLabel = $this->scan->team->getWhiteLabelSettings();
            if ($whiteLabel['enabled'] && !empty($whiteLabel['company_name'])) {
                $companyName = $whiteLabel['company_name'];
            }
        }

        return new Envelope(
            subject: $companyName.' - GEO Scan Report: '.($this->scan->title ?? $this->scan->url),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.scan-report',
            with: [
                'scan' => $this->scan,
                'user' => $this->user,
                'pdfData' => $this->pdfData,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        // Generate PDF with tier-appropriate content
        $pdf = Pdf::loadView('exports.scan-pdf', $this->pdfData);

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                $this->pdfData['filename']
            )->withMime('application/pdf'),
        ];
    }

    private function preparePdfData(): void
    {
        $recommendations = $this->scan->results['recommendations'] ?? [];
        $recommendationsLimited = false;
        $recommendationsTotal = count($recommendations);

        // Apply recommendation limits based on tier
        if ($this->user->isFreeTier()) {
            $recommendationsLimit = $this->user->getLimit('recommendations_shown') ?? 3;
            $recommendations = array_slice($recommendations, 0, $recommendationsLimit);
            $recommendationsLimited = true;
        }

        $filename = 'geo-scan-'.($this->scan->title ? str()->slug($this->scan->title) : $this->scan->uuid).'.pdf';

        // Get white label settings from the scan's team
        $whiteLabel = [
            'enabled' => false,
            'company_name' => config('app.name'),
            'logo_url' => null,
            'logo_path' => null,
            'primary_color' => '#6366f1',
            'secondary_color' => '#8b5cf6',
            'report_footer' => null,
            'contact_email' => null,
            'website_url' => config('app.url'),
        ];

        if ($this->scan->team_id && $this->scan->team) {
            $whiteLabel = $this->scan->team->getWhiteLabelSettings();
            // Get the actual file path for embedding in PDF
            if ($this->scan->team->logo_path) {
                $whiteLabel['logo_path'] = storage_path('app/public/'.$this->scan->team->logo_path);
            }
        }

        // Filter pillars based on user's current tier
        $pillars = $this->filterPillarsForTier($this->scan->results['pillars'] ?? []);

        // Also filter recommendations to only include those for visible pillars
        $visiblePillarNames = array_keys($pillars);
        $recommendations = array_filter($recommendations, function ($rec) use ($visiblePillarNames) {
            $pillarKey = $rec['pillar_key'] ?? $this->pillarNameToKey($rec['pillar'] ?? '');

            return in_array($pillarKey, $visiblePillarNames);
        });
        $recommendations = array_values($recommendations);

        $this->pdfData = [
            'scan' => $this->scan,
            'pillars' => $pillars,
            'recommendations' => $recommendations,
            'summary' => $this->scan->results['summary'] ?? [],
            'filename' => $filename,
            'recommendationsLimited' => $recommendationsLimited,
            'recommendationsTotal' => $recommendationsTotal,
            'userPlan' => $this->user->getPlanKey(),
            'generatedAt' => now(),
            'whiteLabel' => $whiteLabel,
        ];
    }

    /**
     * Filter pillars based on user's current subscription tier.
     */
    private function filterPillarsForTier(array $pillars): array
    {
        $userTier = $this->getUserTierForPillars();

        $allowedTiers = ['free'];
        if (in_array($userTier, ['pro', 'agency', 'agency_member', 'admin'])) {
            $allowedTiers[] = 'pro';
        }
        if (in_array($userTier, ['agency', 'agency_member', 'admin'])) {
            $allowedTiers[] = 'agency';
        }

        return array_filter($pillars, function ($pillar) use ($allowedTiers) {
            $pillarTier = $pillar['tier'] ?? 'free';

            return in_array($pillarTier, $allowedTiers);
        });
    }

    /**
     * Get the user's tier for pillar filtering.
     */
    private function getUserTierForPillars(): string
    {
        if ($this->user->is_admin) {
            return 'admin';
        }

        return $this->user->getPlanKey();
    }

    /**
     * Convert pillar display name to key.
     */
    private function pillarNameToKey(string $name): string
    {
        return strtolower(str_replace([' ', '-'], '_', $name));
    }
}
