<?php

declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer
{
    public static function sendVerification(string $toEmail, string $code): bool
    {
        $config = require __DIR__ . '/../../config/config.php';
        $mail   = $config['mail'];

        // Always log the code as a fallback so you can grab it if mail fails
        self::log($toEmail, $code);

        $phpMailer = new PHPMailer(true);

        try {
            // SMTP
            $phpMailer->isSMTP();
            $phpMailer->Host       = $mail['host'];
            $phpMailer->SMTPAuth   = true;
            $phpMailer->Username   = $mail['username'];
            $phpMailer->Password   = $mail['password'];
            $phpMailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $phpMailer->Port       = $mail['port'];
            $phpMailer->CharSet    = 'UTF-8';

            // Debug (set to 2 for verbose SMTP output, 0 to silence)
            $phpMailer->SMTPDebug   = 0;
            $phpMailer->Debugoutput = function ($str, $level) {
                error_log("[SMTP] $str");
            };

            // From / To
            $phpMailer->setFrom($mail['from_email'], $mail['from_name']);
            $phpMailer->addAddress($toEmail);

            // Content
            $phpMailer->isHTML(true);
            $phpMailer->Subject = 'Your PasaBuy verification code';
            $phpMailer->Body    = self::htmlBody($code);
            $phpMailer->AltBody = self::plainBody($code);

            $phpMailer->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log('[Mailer] Failed to send to ' . $toEmail . ': ' . $e->getMessage());
            return false;
        }
    }

    // ---- Internals -------------------------------------------------

    private static function log(string $email, string $code): void
    {
        $config = require __DIR__ . '/../../config/config.php';
        $logDir = $config['storage']['logs'];
        if (!is_dir($logDir)) mkdir($logDir, 0775, true);

        file_put_contents(
            $logDir . '/mail.log',
            date('c') . "  → {$email}  CODE: {$code}\n",
            FILE_APPEND
        );
    }

    private static function plainBody(string $code): string
    {
        return "Hello,\n\n"
             . "Your PasaBuy verification code is: {$code}\n"
             . "It expires in 15 minutes.\n\n"
             . "If you didn't request this, you can ignore this email.\n\n"
             . "— PasaBuy Mo, Pasaya Mo";
    }

    private static function htmlBody(string $code): string
    {
        $appName = 'PasaBuy Mo, Pasaya Mo';
        return <<<HTML
        <!DOCTYPE html>
        <html><body style="font-family:Arial,sans-serif;background:#f4f6f8;padding:24px;">
          <div style="max-width:520px;margin:auto;background:#fff;border-radius:12px;padding:32px;">
            <h2 style="color:#1B7F5C;margin:0 0 8px;">{$appName}</h2>
            <p style="color:#555;margin:0 0 24px;">Email Verification</p>

            <p style="color:#333;">Hello,</p>
            <p style="color:#333;">Use the code below to verify your email address:</p>

            <div style="text-align:center;margin:24px 0;">
              <span style="display:inline-block;background:#1B7F5C;color:#fff;
                           padding:14px 28px;border-radius:8px;font-size:28px;
                           letter-spacing:6px;font-weight:bold;">{$code}</span>
            </div>

            <p style="color:#888;font-size:13px;">
              This code expires in <strong>15 minutes</strong>.
              If you didn't request this, ignore this email.
            </p>

            <hr style="border:none;border-top:1px solid #eee;margin:24px 0;">
            <p style="color:#aaa;font-size:12px;text-align:center;">
              &copy; {$appName}
            </p>
          </div>
        </body></html>
        HTML;
    }
}