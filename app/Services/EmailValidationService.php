<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class EmailValidationService
{
    /**
     * List of strictly role-based prefixes that are highly likely 
     * to cause spam trap flags or hard bounces.
     */
    protected array $rolePrefixes = [
        'admin',
        'administrator',
        'info',
        'support',
        'contact',
        'billing',
        'sales',
        'webmaster',
        'postmaster',
        'noreply',
        'no-reply',
        'help',
        'abuse',
        'office',
        'hi',
        'hello'
    ];

    /**
     * Common disposable email domains to reject upfront.
     * In a robust production environment, this list would be pulled from a DB or Cache.
     */
    protected array $disposableDomains = [
        'mailinator.com',
        '10minutemail.com',
        'guerrillamail.com',
        'tempmail.com',
        'yopmail.com',
        'temp-mail.org',
        'throwawaymail.com',
        'maildrop.cc'
    ];

    /**
     * Validates an email address and returns its precise status and score.
     * 
     * @param string $email
     * @return array [ 'status' => string, 'score' => int ]
     */
    public function validate(string $email): array
    {
        $email = strtolower(trim($email));

        // 1. Basic Syntax Check (RFC Compliance)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'invalid_format', 'score' => 0];
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return ['status' => 'invalid_format', 'score' => 0];
        }

        $localPart = $parts[0];
        $domain = $parts[1];

        // 2. Disposable Domain Check
        if (in_array($domain, $this->disposableDomains)) {
            return ['status' => 'disposable', 'score' => 10];
        }

        // 3. Role-Based Check
        if (in_array($localPart, $this->rolePrefixes)) {
            // Role based emails aren't strictly bouncing, but are highly risky 
            // to send bulk email to. We mark them risky.
            return ['status' => 'risky', 'score' => 40];
        }

        // 4. MX Record Validation (DNS Check)
        // Ensure the domain actually has a mail server capable of receiving email.
        if (!$this->hasMxRecords($domain)) {
            return ['status' => 'no_mx', 'score' => 20];
        }

        // 5. Advanced (Optional) SMTP Handshake could go here.
        // For 200k shared hosting processing, full SMTP handshakes in bulk
        // are prone to false positives due to greylisting and connection limits.
        // We stop at strong MX validation.

        // If it passes all static checks and has an MX record, we consider it valid.
        return ['status' => 'valid', 'score' => 100];
    }

    /**
     * Check if a domain has valid MX records.
     * Uses built-in PHP checkdnsrr.
     */
    protected function hasMxRecords(string $domain): bool
    {
        try {
            // Only check MX. If no MX, check A record as fallback (some domains receive mail via A).
            // But for high volume strict mode, requiring MX is safer.
            if (checkdnsrr($domain, 'MX')) {
                return true;
            }

            // Fallback: If no MX, does it even resolve via A record?
            // (Often, mail bound to domains with no MX goes to the A record per RFC 5321).
            if (checkdnsrr($domain, 'A')) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::warning("DNS check failed for domain: {$domain}", ['error' => $e->getMessage()]);
            return false;
        }
    }
}
