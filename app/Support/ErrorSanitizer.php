<?php

namespace App\Support;

use Illuminate\Database\QueryException;

/**
 * Sanitizes exception messages to prevent internal details from being exposed to users.
 */
class ErrorSanitizer
{
    /**
     * Convert an exception into a safe, user-facing message.
     */
    public static function sanitize(\Throwable $e): string
    {
        if ($e instanceof QueryException) {
            return 'An internal error occurred. Please try again later.';
        }

        $message = $e->getMessage();

        // Database/SQL errors
        if (str_contains($message, 'SQLSTATE') || str_contains($message, 'Connection refused')) {
            return 'An internal error occurred. Please try again later.';
        }

        // UTF-8/JSON encoding errors
        if (str_contains($message, 'UTF-8') || str_contains($message, 'JSON') || str_contains($message, 'Malformed')) {
            return 'An unexpected data format error occurred. Please try again.';
        }

        // Timeout errors
        if (str_contains($message, 'timed out') || str_contains($message, 'timeout') || str_contains($message, 'cURL error 28')) {
            return 'The request timed out. Please try again.';
        }

        // Rate limiting
        if (str_contains($message, 'rate limit') || str_contains($message, '429') || str_contains($message, 'Too Many')) {
            return 'Too many requests. Please wait a moment and try again.';
        }

        // API errors
        if (str_contains($message, 'API error') || str_contains($message, 'API request failed')) {
            return 'An external service is temporarily unavailable. Please try again in a few minutes.';
        }

        // Authentication/authorization errors
        if (str_contains($message, 'Unauthorized') || str_contains($message, 'Invalid API key') || str_contains($message, '401') || str_contains($message, '403')) {
            return 'A service configuration error occurred. Our team has been notified.';
        }

        // File system / path errors
        if (str_contains($message, '/usr/') || str_contains($message, '/home/') || str_contains($message, '/var/') || str_contains($message, 'No such file')) {
            return 'An internal error occurred. Please try again later.';
        }

        // Browsershot / Puppeteer errors
        if (str_contains($message, 'Browsershot') || str_contains($message, 'puppeteer') || str_contains($message, 'Chrome') || str_contains($message, 'node')) {
            return 'We were unable to process this page. Please try again.';
        }

        // Mail errors
        if (str_contains($message, 'SMTP') || str_contains($message, 'mail') || str_contains($message, 'Mailgun')) {
            return 'Failed to send email. Please try again later.';
        }

        // Stripe errors - these are generally safe to show
        if (str_contains($message, 'Stripe') || str_contains($message, 'card was declined') || str_contains($message, 'payment')) {
            return $message; // Stripe messages are designed to be user-facing
        }

        // Generic fallback
        return 'Something went wrong. Please try again later.';
    }

    /**
     * Sanitize for scan-specific errors.
     */
    public static function sanitizeScan(\Throwable $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Browsershot') || str_contains($message, 'puppeteer') || str_contains($message, 'stealth')) {
            return 'We were unable to fetch this page. The site may be blocking automated access.';
        }

        if (str_contains($message, 'cURL') || str_contains($message, 'Could not resolve host')) {
            return 'We could not reach this URL. Please check the address and try again.';
        }

        if (str_contains($message, '404') || str_contains($message, 'Not Found')) {
            return 'The page was not found. Please check the URL and try again.';
        }

        if (str_contains($message, '403') || str_contains($message, 'Forbidden')) {
            return 'Access to this page was denied. The site may be blocking our scanner.';
        }

        if (str_contains($message, '500') || str_contains($message, 'Internal Server Error')) {
            return 'The target website returned an error. Please try again later.';
        }

        return self::sanitize($e);
    }
}
