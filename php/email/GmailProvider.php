<?php
/**
 * php/email/GmailProvider.php
 * ---------------------------
 * OPTIONAL fallback provider. Sends via Gmail SMTP (smtp.gmail.com:587)
 * using STARTTLS + AUTH LOGIN.
 *
 * NOTE ON DEPENDENCIES: this project has no Composer, and PHPMailer could
 * not be vendored in this environment, so this is a small, purpose-built
 * SMTP client using PHP's built-in stream functions — zero dependencies.
 * It is a drop-in behind EmailService, so it can be swapped for PHPMailer
 * later without touching the authentication code.
 *
 * Gmail requires a Google *App Password* (not the normal account password).
 * Credentials come only from .env.
 */
declare(strict_types=1);

final class GmailProvider
{
    public function name(): string { return 'gmail'; }

    public function configured(): bool
    {
        return envFilled('GMAIL_SMTP_USERNAME')
            && envFilled('GMAIL_SMTP_PASSWORD')
            && envFilled('MAIL_FROM_ADDRESS');
    }

    public function send(string $toEmail, string $subject, string $html, string $text): bool
    {
        if (!$this->configured()) {
            error_log('[BloodLink][email] Gmail not configured (missing GMAIL_SMTP_* or MAIL_FROM_ADDRESS)');
            return false;
        }

        $host = env('GMAIL_SMTP_HOST', 'smtp.gmail.com');
        $port = (int) env('GMAIL_SMTP_PORT', '587');
        $user = (string) env('GMAIL_SMTP_USERNAME');
        $pass = (string) env('GMAIL_SMTP_PASSWORD');
        $from = (string) env('MAIL_FROM_ADDRESS');
        $fromName = (string) env('MAIL_FROM_NAME', 'BloodLink');

        $fp = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 15);
        if (!$fp) {
            error_log('[BloodLink][email] Gmail connect failed: ' . $errstr);
            return false;
        }
        stream_set_timeout($fp, 15);

        try {
            $this->expect($fp, 220);
            $this->cmd($fp, 'EHLO bloodlink.local', 250);
            $this->cmd($fp, 'STARTTLS', 220);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('TLS negotiation failed');
            }
            $this->cmd($fp, 'EHLO bloodlink.local', 250);
            $this->cmd($fp, 'AUTH LOGIN', 334);
            $this->cmd($fp, base64_encode($user), 334);
            $this->cmd($fp, base64_encode($pass), 235); // may throw on bad creds
            $this->cmd($fp, 'MAIL FROM:<' . $from . '>', 250);
            $this->cmd($fp, 'RCPT TO:<' . $toEmail . '>', 250);
            $this->cmd($fp, 'DATA', 354);

            $boundary = 'bl_' . bin2hex(random_bytes(8));
            $headers  = 'From: ' . $this->encodeName($fromName) . ' <' . $from . ">\r\n";
            $headers .= 'To: <' . $toEmail . ">\r\n";
            $headers .= 'Subject: ' . $this->encodeName($subject) . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . "\"\r\n";

            $body  = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n" . $text . "\r\n";
            $body .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n" . $html . "\r\n";
            $body .= "--{$boundary}--\r\n";

            // Dot-stuffing: lines starting with "." must be escaped.
            $message = preg_replace('/^\./m', '..', $headers . "\r\n" . $body);
            fwrite($fp, $message . "\r\n.\r\n");
            $this->expect($fp, 250);
            $this->cmd($fp, 'QUIT', 221);
            fclose($fp);
            return true;
        } catch (Throwable $e) {
            // Log the STAGE, never the password.
            error_log('[BloodLink][email] Gmail SMTP failed: ' . $e->getMessage());
            @fclose($fp);
            return false;
        }
    }

    private function cmd($fp, string $command, int $expected): void
    {
        fwrite($fp, $command . "\r\n");
        $this->expect($fp, $expected);
    }

    private function expect($fp, int $code): void
    {
        $line = '';
        // Read (possibly multi-line) reply; last line has "NNN " (space).
        while (($chunk = fgets($fp, 515)) !== false) {
            $line = $chunk;
            if (isset($chunk[3]) && $chunk[3] === ' ') break;
        }
        $got = (int) substr($line, 0, 3);
        if ($got !== $code) {
            throw new RuntimeException('SMTP expected ' . $code . ', got ' . $got);
        }
    }

    private function encodeName(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
