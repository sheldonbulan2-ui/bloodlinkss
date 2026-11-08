<?php
/**
 * php/email/BrevoProvider.php
 * ---------------------------
 * PRIMARY provider. Sends through Brevo's Transactional Email HTTP API
 * using cURL (built into PHP) — no SMTP library, no Composer package.
 *
 * Credentials come only from .env (BREVO_API_KEY, MAIL_FROM_ADDRESS).
 */
declare(strict_types=1);

final class BrevoProvider
{
    public function name(): string { return 'brevo'; }

    /** True only when the API key and a from-address are present. */
    public function configured(): bool
    {
        return envFilled('BREVO_API_KEY') && envFilled('MAIL_FROM_ADDRESS');
    }

    /**
     * Send an email. Returns true on success, false on failure.
     * Never throws credentials or provider internals to the caller.
     */
    public function send(string $toEmail, string $subject, string $html, string $text): bool
    {
        if (!$this->configured()) {
            error_log('[BloodLink][email] Brevo not configured (missing BREVO_API_KEY or MAIL_FROM_ADDRESS)');
            return false;
        }
        if (!function_exists('curl_init')) {
            error_log('[BloodLink][email] Brevo: cURL extension unavailable');
            return false;
        }

        $payload = [
            'sender' => [
                'email' => env('MAIL_FROM_ADDRESS'),
                'name'  => env('MAIL_FROM_NAME', 'BloodLink'),
            ],
            'to'          => [['email' => $toEmail]],
            'subject'     => $subject,
            'htmlContent' => $html,
            'textContent' => $text,
        ];

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . env('BREVO_API_KEY'),
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        $response = curl_exec($ch);
        $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false || $status < 200 || $status >= 300) {
            // Log status only — do NOT log the API key or full response body verbatim.
            error_log('[BloodLink][email] Brevo delivery failed (HTTP ' . $status . ') ' . $curlErr);
            return false;
        }
        return true;
    }
}
