<?php
/**
 * php/email/EmailService.php
 * --------------------------
 * The ONLY thing the rest of the app talks to for email.
 * Chooses the provider (brevo | gmail | auto) and hides all SMTP/API
 * details so providers can be swapped without touching auth code.
 *
 *   sendVerificationOtp($email, $otp, $name)  -> true on success
 */
declare(strict_types=1);

require_once __DIR__ . '/EmailTemplate.php';
require_once __DIR__ . '/BrevoProvider.php';
require_once __DIR__ . '/GmailProvider.php';

final class EmailService
{
    private BrevoProvider $brevo;
    private GmailProvider $gmail;

    public function __construct()
    {
        $this->brevo = new BrevoProvider();
        $this->gmail = new GmailProvider();
    }

    /** Provider mode from .env: brevo | gmail | auto (defaults to brevo). */
    private function mode(): string
    {
        $mode = strtolower(trim((string) env('EMAIL_PROVIDER', 'brevo')));
        return in_array($mode, ['brevo', 'gmail', 'auto'], true) ? $mode : 'brevo';
    }

    /**
     * Startup/config validation. Returns null when the selected provider
     * is usable, or a developer-facing message when it is not. This lets
     * the app show a clear error WITHOUT crashing everything.
     */
    public function configError(): ?string
    {
        switch ($this->mode()) {
            case 'brevo':
                return $this->brevo->configured() ? null
                    : 'Brevo email provider is not configured (set BREVO_API_KEY and MAIL_FROM_ADDRESS in .env).';
            case 'gmail':
                return $this->gmail->configured() ? null
                    : 'Gmail email provider is not configured (set GMAIL_SMTP_USERNAME, GMAIL_SMTP_PASSWORD and MAIL_FROM_ADDRESS in .env).';
            case 'auto':
                return ($this->brevo->configured() || $this->gmail->configured()) ? null
                    : 'No email provider is configured for AUTO mode (configure Brevo and/or Gmail in .env).';
        }
        return 'Unknown EMAIL_PROVIDER.';
    }

    /**
     * Send the OTP. Returns true if any provider accepted the message.
     * Logs the delivery path safely; never logs the OTP or credentials.
     */
    public function sendVerificationOtp(string $toEmail, string $otp, ?string $name = null): bool
    {
        $tpl = EmailTemplate::verificationOtp($otp);
        $order = $this->providerOrder();

        foreach ($order as $provider) {
            if (!$provider->configured()) continue;
            $ok = $provider->send($toEmail, $tpl['subject'], $tpl['html'], $tpl['text']);
            if ($ok) {
                error_log('[BloodLink][email] OTP email sent successfully via ' . $provider->name());
                return true;
            }
            error_log('[BloodLink][email] ' . $provider->name() . ' delivery failed; trying next provider if any');
        }
        error_log('[BloodLink][email] All configured providers failed to send OTP');
        return false;
    }

    /** @return array<object> providers to try, in order. */
    private function providerOrder(): array
    {
        switch ($this->mode()) {
            case 'gmail': return [$this->gmail];
            case 'auto':  return [$this->brevo, $this->gmail]; // Brevo first, Gmail fallback
            case 'brevo':
            default:      return [$this->brevo];
        }
    }
}
