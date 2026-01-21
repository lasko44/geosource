<?php

namespace App\Services\GEO;

use App\Services\GEO\Contracts\ScorerInterface;

/**
 * Scores content based on E-E-A-T signals.
 * (Experience, Expertise, Authoritativeness, Trustworthiness)
 *
 * PRO TIER FEATURE
 *
 * Measures:
 * - Author attribution and credentials
 * - About/contact information
 * - Trust signals (reviews, testimonials)
 * - Professional presentation
 */
class EEATScorer implements ScorerInterface
{
    private const MAX_SCORE = 15;

    public function score(string $content, array $context = []): array
    {
        $details = [
            'author' => $this->analyzeAuthor($content),
            'trust_signals' => $this->analyzeTrustSignals($content),
            'contact' => $this->analyzeContactInfo($content),
            'credentials' => $this->analyzeCredentials($content),
        ];

        // Calculate scores (total: 15 points)
        $authorScore = $this->calculateAuthorScore($details['author']);           // Up to 5 pts
        $trustScore = $this->calculateTrustScore($details['trust_signals']);      // Up to 4 pts
        $contactScore = $this->calculateContactScore($details['contact']);        // Up to 3 pts
        $credentialScore = $this->calculateCredentialScore($details['credentials']); // Up to 3 pts

        $totalScore = $authorScore + $trustScore + $contactScore + $credentialScore;

        return [
            'score' => min(self::MAX_SCORE, $totalScore),
            'max_score' => self::MAX_SCORE,
            'details' => array_merge($details, [
                'breakdown' => [
                    'author' => $authorScore,
                    'trust_signals' => $trustScore,
                    'contact' => $contactScore,
                    'credentials' => $credentialScore,
                ],
            ]),
        ];
    }

    public function getMaxScore(): float
    {
        return self::MAX_SCORE;
    }

    public function getName(): string
    {
        return 'E-E-A-T Signals';
    }

    private function analyzeAuthor(string $content): array
    {
        $result = [
            'has_author' => false,
            'author_name' => null,
            'has_author_bio' => false,
            'has_author_image' => false,
            'has_author_link' => false,
        ];

        // Check for author schema
        if (preg_match('/"@type"\s*:\s*"Person"/', $content)) {
            $result['has_author'] = true;
        }

        // Check for author meta tag
        if (preg_match('/<meta[^>]+name=["\']author["\'][^>]+content=["\']([^"\']+)["\']/', $content, $match)) {
            $result['has_author'] = true;
            $result['author_name'] = $match[1];
        }

        // Check for common author patterns in HTML
        $authorPatterns = [
            '/<[^>]*class=["\'][^"\']*author[^"\']*["\'][^>]*>([^<]+)</i',
            '/(?:written|posted|published)\s+by\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)?)/i',
            '/<a[^>]+rel=["\']author["\'][^>]*>([^<]+)</i',
            '/<[^>]*itemprop=["\']author["\'][^>]*>([^<]*)</i',
        ];

        foreach ($authorPatterns as $pattern) {
            if (preg_match($pattern, $content, $match)) {
                $result['has_author'] = true;
                if (empty($result['author_name']) && ! empty(trim($match[1]))) {
                    $result['author_name'] = trim(strip_tags($match[1]));
                }
                break;
            }
        }

        // Check for author bio
        $bioPatterns = [
            '/class=["\'][^"\']*author[_-]?bio[^"\']*["\']/',
            '/class=["\'][^"\']*about[_-]?(?:the[_-]?)?author[^"\']*["\']/',
            '/id=["\']author[_-]?bio["\']/',
        ];

        foreach ($bioPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $result['has_author_bio'] = true;
                break;
            }
        }

        // Check for author image
        if (preg_match('/class=["\'][^"\']*author[^"\']*["\'][^>]*>.*?<img/is', $content)) {
            $result['has_author_image'] = true;
        }

        // Check for author link
        if (preg_match('/<a[^>]+(?:rel=["\']author["\']|class=["\'][^"\']*author[^"\']*["\'])[^>]+href=/i', $content)) {
            $result['has_author_link'] = true;
        }

        return $result;
    }

    private function analyzeTrustSignals(string $content): array
    {
        $result = [
            'has_reviews' => false,
            'has_testimonials' => false,
            'has_ratings' => false,
            'has_certifications' => false,
            'has_awards' => false,
            'trust_indicators_count' => 0,
        ];

        // Check for review/rating schema
        if (preg_match('/"@type"\s*:\s*"Review"/', $content) ||
            preg_match('/"aggregateRating"/', $content)) {
            $result['has_reviews'] = true;
            $result['has_ratings'] = true;
        }

        // Check for testimonial sections
        $testimonialPatterns = [
            '/class=["\'][^"\']*testimonial/i',
            '/class=["\'][^"\']*review/i',
            '/class=["\'][^"\']*customer[_-]?(?:feedback|quote)/i',
            '/<blockquote[^>]*class=["\'][^"\']*quote/i',
        ];

        foreach ($testimonialPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $result['has_testimonials'] = true;
                break;
            }
        }

        // Check for rating elements
        if (preg_match('/class=["\'][^"\']*(?:star|rating)[^"\']*["\']/', $content) ||
            preg_match('/itemprop=["\']ratingValue["\']/', $content)) {
            $result['has_ratings'] = true;
        }

        // Check for certifications
        $certPatterns = [
            '/certified|certification|accredited|accreditation/i',
            '/ISO\s*\d{4,5}/i',
            '/class=["\'][^"\']*(?:badge|certification|trust[_-]?seal)/i',
        ];

        foreach ($certPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $result['has_certifications'] = true;
                break;
            }
        }

        // Check for awards
        if (preg_match('/(?:award|winner|recognized|featured in|as seen (?:on|in))/i', $content)) {
            $result['has_awards'] = true;
        }

        // Count total indicators
        $result['trust_indicators_count'] = array_sum([
            $result['has_reviews'] ? 1 : 0,
            $result['has_testimonials'] ? 1 : 0,
            $result['has_ratings'] ? 1 : 0,
            $result['has_certifications'] ? 1 : 0,
            $result['has_awards'] ? 1 : 0,
        ]);

        return $result;
    }

    private function analyzeContactInfo(string $content): array
    {
        $result = [
            'has_contact_page_link' => false,
            'has_email' => false,
            'has_phone' => false,
            'has_address' => false,
            'has_social_links' => false,
        ];

        // Check for contact page link
        if (preg_match('/<a[^>]+href=["\'][^"\']*contact[^"\']*["\'][^>]*>/i', $content)) {
            $result['has_contact_page_link'] = true;
        }

        // Check for email
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $content) ||
            preg_match('/mailto:/i', $content)) {
            $result['has_email'] = true;
        }

        // Check for phone
        if (preg_match('/(?:tel:|phone|call)[:\s]*[\d\s\-\+\(\)]{10,}/', $content) ||
            preg_match('/\+?1?[-.\s]?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $content)) {
            $result['has_phone'] = true;
        }

        // Check for address (schema or common patterns)
        if (preg_match('/"@type"\s*:\s*"PostalAddress"/', $content) ||
            preg_match('/itemprop=["\']address["\']/', $content) ||
            preg_match('/class=["\'][^"\']*address[^"\']*["\']/', $content)) {
            $result['has_address'] = true;
        }

        // Check for social links
        $socialPlatforms = ['facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'tiktok'];
        foreach ($socialPlatforms as $platform) {
            if (preg_match('/href=["\'][^"\']*'.$platform.'\.com/i', $content)) {
                $result['has_social_links'] = true;
                break;
            }
        }

        return $result;
    }

    private function analyzeCredentials(string $content): array
    {
        $result = [
            'has_expertise_claims' => false,
            'has_experience_mentions' => false,
            'has_qualifications' => false,
            'credential_phrases' => [],
        ];

        // Check for expertise claims
        $expertisePatterns = [
            '/(?:expert|specialist|professional|leader)\s+in/i',
            '/years?\s+of\s+experience/i',
            '/industry[_\s-]?leading/i',
            '/(?:certified|licensed|qualified)\s+\w+/i',
        ];

        foreach ($expertisePatterns as $pattern) {
            if (preg_match($pattern, $content, $match)) {
                $result['has_expertise_claims'] = true;
                $result['credential_phrases'][] = trim($match[0]);
            }
        }

        // Check for experience mentions
        if (preg_match('/(\d+)\+?\s*years?\s+(?:of\s+)?experience/i', $content, $match)) {
            $result['has_experience_mentions'] = true;
            $result['years_experience'] = (int) $match[1];
        }

        // Check for qualifications
        $qualificationPatterns = [
            '/(?:PhD|Ph\.D|M\.D\.|MBA|CPA|JD|MD|BSc|MSc|BA|MA|MS)\b/',
            '/(?:board[_\s-]?certified|licensed|registered)/i',
            '/(?:university|college|institute)\s+of/i',
        ];

        foreach ($qualificationPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $result['has_qualifications'] = true;
                break;
            }
        }

        $result['credential_phrases'] = array_unique(array_slice($result['credential_phrases'], 0, 5));

        return $result;
    }

    private function calculateAuthorScore(array $author): float
    {
        $score = 0;

        if ($author['has_author']) {
            $score += 2;
        }

        if ($author['has_author_bio']) {
            $score += 1.5;
        }

        if ($author['has_author_image']) {
            $score += 0.75;
        }

        if ($author['has_author_link']) {
            $score += 0.75;
        }

        return min(5, $score);
    }

    private function calculateTrustScore(array $trust): float
    {
        $score = 0;

        if ($trust['has_reviews'] || $trust['has_testimonials']) {
            $score += 2;
        }

        if ($trust['has_ratings']) {
            $score += 1;
        }

        if ($trust['has_certifications'] || $trust['has_awards']) {
            $score += 1;
        }

        return min(4, $score);
    }

    private function calculateContactScore(array $contact): float
    {
        $score = 0;

        if ($contact['has_contact_page_link']) {
            $score += 1;
        }

        if ($contact['has_email'] || $contact['has_phone']) {
            $score += 1;
        }

        if ($contact['has_social_links']) {
            $score += 1;
        }

        return min(3, $score);
    }

    private function calculateCredentialScore(array $credentials): float
    {
        $score = 0;

        if ($credentials['has_expertise_claims']) {
            $score += 1;
        }

        if ($credentials['has_experience_mentions']) {
            $score += 1;
        }

        if ($credentials['has_qualifications']) {
            $score += 1;
        }

        return min(3, $score);
    }
}
