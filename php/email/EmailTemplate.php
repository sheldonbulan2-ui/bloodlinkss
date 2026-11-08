<?php
/**
 * php/email/EmailTemplate.php
 * ---------------------------
 * Builds the verification email (HTML + plain-text fallback).
 * No secrets, no OTP persistence here — just presentation.
 */
declare(strict_types=1);

final class EmailTemplate
{
    /** @return array{subject:string, html:string, text:string} */
    public static function verificationOtp(string $otp): array
    {
        $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
        $subject = 'Verify your email address';

        $text = "Hello,\n\n"
              . "Your BloodLink verification code is: {$otp}\n\n"
              . "This code expires in 5 minutes.\n\n"
              . "If you did not create an account, you can ignore this email.";

        $html = <<<HTML
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f5f5f7;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#222">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f7;padding:24px 0">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #eee">
        <tr><td style="background:#b00020;padding:20px 28px">
          <span style="color:#fff;font-size:20px;font-weight:700">BloodLink</span>
        </td></tr>
        <tr><td style="padding:28px">
          <p style="margin:0 0 14px;font-size:15px">Hello,</p>
          <p style="margin:0 0 18px;font-size:15px">Your verification code is:</p>
          <div style="text-align:center;margin:22px 0">
            <span style="display:inline-block;font-size:34px;font-weight:700;letter-spacing:10px;color:#b00020;background:#fdeff1;padding:16px 24px;border-radius:12px">{$safeOtp}</span>
          </div>
          <p style="margin:0 0 8px;font-size:14px;color:#555">This code expires in <strong>5 minutes</strong>.</p>
          <p style="margin:18px 0 0;font-size:13px;color:#888">If you did not create an account, you can safely ignore this email.</p>
        </td></tr>
        <tr><td style="padding:16px 28px;background:#fafafa;border-top:1px solid #eee">
          <p style="margin:0;font-size:12px;color:#aaa">© BloodLink — Blood Donation & Availability System</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

        return ['subject' => $subject, 'html' => $html, 'text' => $text];
    }
}
