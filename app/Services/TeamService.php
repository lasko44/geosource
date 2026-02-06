<?php

namespace App\Services;

/**
 * Provides team-related utility functions and operations.
 */
class TeamService
{
    /**
     * Mask an email address for privacy.
     */
    public function maskEmail(string $email): string
    {
        $parts = explode('@', $email);

        if (count($parts) !== 2) {
            return '***@***.***';
        }

        $local = $parts[0];
        $domain = $parts[1];

        $maskedLocal = strlen($local) > 2
            ? substr($local, 0, 2).str_repeat('*', min(strlen($local) - 2, 5))
            : $local;

        $domainParts = explode('.', $domain);
        $tld = array_pop($domainParts);
        $maskedDomain = '***.'.$tld;

        return $maskedLocal.'@'.$maskedDomain;
    }
}
