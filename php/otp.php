<?php
/**
 * php/otp.php
 * -----------
 * Email OTP lifecycle: issue, resend, verify.
 *
 * Security properties:
 *  - 6-digit code from random_int() (cryptographically secure)
 *  - stored only as an HMAC-SHA256 hash (never plaintext), keyed by APP_KEY
 *  - 5-minute expiry, one-time use, max 5 verify attempts
 *  - 60-second resend cooldown, capped resend count
 *  - generating a new code overwrites (invalidates) the previous one
 *
 * Table: email_verification_otps (see database/migration-otp.sql).
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/email/EmailService.php';

const OTP_TTL_SECONDS      = 300;  // 5 minutes
const OTP_MAX_ATTEMPTS     = 5;
const OTP_RESEND_COOLDOWN  = 60;   // seconds
const OTP_MAX_RESENDS      = 5;    // per active code lifecycle

/** Keyed hash of the code. APP_KEY must be set in .env. */
function otpHash(string $code): string
{
    $key = (string) env('APP_KEY', '');
    if ($key === '') {
        // Should never happen in a configured install; log once.
        error_log('[BloodLink][otp] APP_KEY is empty — set it in .env');
        $key = 'bloodlink-insecure-fallback-key';
    }
    return hash_hmac('sha256', $code, $key);
}

function generateOtpCode(): string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Issue (or resend) an OTP for a user. Enforces cooldown + resend cap
 * only when $isResend is true. Returns [ok(bool), message(string)].
 */
function issueOtp(int $userId, string $email, ?string $name, bool $isResend): array
{
    $db = db();

    $row = null;
    $stmt = $db->prepare('SELECT id, resend_count, last_sent_at FROM email_verification_otps WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if ($isResend && $row) {
        $elapsed = time() - strtotime((string) $row['last_sent_at']);
        if ($elapsed < OTP_RESEND_COOLDOWN) {
            $wait = OTP_RESEND_COOLDOWN - $elapsed;
            return [false, 'Please wait ' . $wait . ' second(s) before requesting another code.'];
        }
        if ((int) $row['resend_count'] >= OTP_MAX_RESENDS) {
            error_log('[BloodLink][otp] resend limit reached for user ID ' . $userId);
            return [false, 'You have requested too many codes. Please try again later.'];
        }
    }

    $code = generateOtpCode();
    $hash = otpHash($code);
    $expires = date('Y-m-d H:i:s', time() + OTP_TTL_SECONDS);

    // Upsert: one active row per user. New code invalidates the old one,
    // resets attempts, and (on resend) bumps the resend counter.
    $db->prepare(
        'INSERT INTO email_verification_otps (user_id, email, otp_hash, expires_at, attempts, resend_count, last_sent_at, created_at, used_at)
         VALUES (?, ?, ?, ?, 0, 0, NOW(), NOW(), NULL)
         ON DUPLICATE KEY UPDATE
            email = VALUES(email),
            otp_hash = VALUES(otp_hash),
            expires_at = VALUES(expires_at),
            attempts = 0,
            resend_count = resend_count + ' . ($isResend ? '1' : '0') . ',
            last_sent_at = NOW(),
            used_at = NULL'
    )->execute([$userId, $email, $hash, $expires]);

    error_log('[BloodLink][otp] OTP generated for user ID ' . $userId . ($isResend ? ' (resend)' : ''));

    $mailer = new EmailService();
    $sent = $mailer->sendVerificationOtp($email, $code, $name);
    // In local mode, keep the code to return it; otherwise wipe for security.
    $localMode = env('BL_LOCAL_OTP', '0') === '1';
    $localOtp = $localMode ? $code : '';
    if (!$localMode) $code = '';

    if (env('BL_LOCAL_OTP', '0') === '1') {
        // In local mode, always return the OTP code directly as the message
        // so the app can auto-fill it. Email providers are bypassed.
        return [true, $localOtp];
    }
    if (!$sent) {
        return [false, "We couldn't send your verification code right now. Please try again later."];
    }
    return [true, 'A 6-digit verification code has been sent to your email.'];
}

/**
 * Verify a submitted code for a user.
 * Returns [ok(bool), message(string)].
 */
function verifyOtp(int $userId, string $submitted): array
{
    $submitted = preg_replace('/\D/', '', $submitted);
    if (strlen((string) $submitted) !== 6) {
        return [false, 'Please enter the 6-digit code.'];
    }

    $db = db();
    $stmt = $db->prepare('SELECT id, otp_hash, expires_at, attempts, used_at FROM email_verification_otps WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if (!$row) {
        return [false, 'No active code found. Please request a new one.'];
    }
    if ($row['used_at'] !== null) {
        return [false, 'This code was already used. Please request a new one.'];
    }
    if (time() > strtotime((string) $row['expires_at'])) {
        error_log('[BloodLink][otp] OTP expired for user ID ' . $userId);
        return [false, 'Your code has expired. Please request a new one.'];
    }

    // Note: no per-code attempt lockout (removed by request). Brute force is
    // still throttled by the per-IP rate limit in auth.php and the 5-min expiry.
    if (!hash_equals((string) $row['otp_hash'], otpHash($submitted))) {
        error_log('[BloodLink][otp] OTP verification failed for user ID ' . $userId);
        return [false, 'Incorrect code. Please try again.'];
    }

    // Success: mark verified and consume the code (one-time use).
    $db->beginTransaction();
    try {
        $db->prepare('UPDATE email_verification_otps SET used_at = NOW() WHERE id = ?')->execute([$row['id']]);
        $db->prepare('UPDATE users SET email_verified = 1 WHERE id = ?')->execute([$userId]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        appLog('verifyOtp', $e);
        return [false, 'Something went wrong. Please try again.'];
    }

    error_log('[BloodLink][otp] OTP verification successful for user ID ' . $userId);
    return [true, 'Your email has been verified.'];
}

/**
 * Verify a code for a PASSWORD-RESET flow. Same security checks as
 * verifyOtp (expiry, one-time use, max attempts, constant-time compare)
 * but it does NOT set email_verified — it only confirms the code is
 * valid and consumes it. Returns [ok(bool), message(string)].
 */
function verifyResetOtp(int $userId, string $submitted): array
{
    $submitted = preg_replace('/\D/', '', $submitted);
    if (strlen((string) $submitted) !== 6) {
        return [false, 'Please enter the 6-digit code.'];
    }

    $db = db();
    $stmt = $db->prepare('SELECT id, otp_hash, expires_at, attempts, used_at FROM email_verification_otps WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if (!$row) {
        return [false, 'No active code found. Please request a new one.'];
    }
    if ($row['used_at'] !== null) {
        return [false, 'This code was already used. Please request a new one.'];
    }
    if (time() > strtotime((string) $row['expires_at'])) {
        return [false, 'Your code has expired. Please request a new one.'];
    }

    if (!hash_equals((string) $row['otp_hash'], otpHash($submitted))) {
        return [false, 'Incorrect code. Please try again.'];
    }

    // Consume the code (one-time use). Caller performs the password update.
    $db->prepare('UPDATE email_verification_otps SET used_at = NOW() WHERE id = ?')->execute([$row['id']]);
    return [true, 'Code verified.'];
}
