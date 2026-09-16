<?php
// SMTP Mailer Utility for Brew Haven
require_once __DIR__ . '/config.php';

// SMTP Configuration (Gmail SMTP with App Password)
if (!defined('SMTP_HOST'))       define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT'))       define('SMTP_PORT', 587);
if (!defined('SMTP_USER'))       define('SMTP_USER', 'edmond.napil@csucc.edu.ph');
if (!defined('SMTP_PASS'))       define('SMTP_PASS', 'nfuugkgqxocnsoea');
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', 'edmond.napil@csucc.edu.ph');
if (!defined('SMTP_FROM_NAME'))  define('SMTP_FROM_NAME', 'Brew Haven Security');
if (!defined('SMTP_ENCRYPTION')) define('SMTP_ENCRYPTION', 'tls'); // 'tls', 'ssl', or 'none'

class BrewHavenSmtpMailer {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $fromEmail;
    private $fromName;
    private $encryption;
    private $lastError = '';

    public function __construct() {
        $this->host       = SMTP_HOST;
        $this->port       = SMTP_PORT;
        $this->user       = SMTP_USER;
        $this->pass       = SMTP_PASS;
        $this->fromEmail  = SMTP_FROM_EMAIL;
        $this->fromName   = SMTP_FROM_NAME;
        $this->encryption = SMTP_ENCRYPTION;
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function send($to, $subject, $bodyHtml) {
        if (!empty($this->host) && !empty($this->user)) {
            $success = $this->sendViaSocket($to, $subject, $bodyHtml);
            if ($success) return true;
        }

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $this->fromName . " <" . $this->fromEmail . ">\r\n";
        
        $mailOk = @mail($to, $subject, $bodyHtml, $headers);
        if (!$mailOk) {
            $this->lastError = "Mail function returned false.";
        }
        return $mailOk;
    }

    private function sendViaSocket($to, $subject, $bodyHtml) {
        $to = trim((string)$to);
        if (empty($to)) {
            $this->lastError = "Recipient address is empty.";
            return false;
        }

        $timeout = 20;
        $remote  = ($this->encryption === 'ssl' ? 'ssl://' : '') . $this->host . ':' . $this->port;
        
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ]);
        $socket = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            $this->lastError = "Connection error: $errstr ($errno)";
            return false;
        }

        $read = function() use ($socket) {
            $response = '';
            while ($str = fgets($socket, 515)) {
                $response .= $str;
                if (substr($str, 3, 1) === ' ') break;
            }
            return $response;
        };

        $write = function($cmd) use ($socket) {
            fputs($socket, $cmd . "\r\n");
        };

        $res = $read();
        if (substr($res, 0, 3) !== '220') {
            $this->lastError = "SMTP Server connect error: $res";
            fclose($socket);
            return false;
        }

        $write("EHLO " . gethostname());
        $res = $read();

        if ($this->encryption === 'tls') {
            $write("STARTTLS");
            $res = $read();
            if (substr($res, 0, 3) !== '220') {
                $this->lastError = "STARTTLS failed: $res";
                fclose($socket);
                return false;
            }
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write("EHLO " . gethostname());
            $res = $read();
        }

        if (!empty($this->user)) {
            $write("AUTH LOGIN");
            $res = $read();
            if (substr($res, 0, 3) !== '334') {
                $this->lastError = "AUTH LOGIN failed: $res";
                fclose($socket);
                return false;
            }

            $write(base64_encode($this->user));
            $res = $read();
            if (substr($res, 0, 3) !== '334') {
                $this->lastError = "Username failed: $res";
                fclose($socket);
                return false;
            }

            $write(base64_encode($this->pass));
            $res = $read();
            if (substr($res, 0, 3) !== '235') {
                $this->lastError = "Password auth failed: $res";
                fclose($socket);
                return false;
            }
        }

        $sender = !empty($this->user) ? $this->user : $this->fromEmail;
        $write("MAIL FROM: <$sender>");
        $res = $read();
        if (substr($res, 0, 3) !== '250') {
            $this->lastError = "MAIL FROM failed: $res";
            fclose($socket);
            return false;
        }

        $write("RCPT TO: <$to>");
        $res = $read();
        if (substr($res, 0, 3) !== '250') {
            $this->lastError = "RCPT TO failed: $res";
            fclose($socket);
            return false;
        }

        $write("DATA");
        $res = $read();
        if (substr($res, 0, 3) !== '354') {
            $this->lastError = "DATA command failed: $res";
            fclose($socket);
            return false;
        }

        $headers  = "From: " . $this->fromName . " <" . $sender . ">\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";

        $message = $headers . $bodyHtml . "\r\n.";
        $write($message);
        $res = $read();

        $write("QUIT");
        fclose($socket);

        return (substr($res, 0, 3) === '250');
    }
}

function bh_send_otp_email($toEmail, $otpCode, $purpose = 'FORGOT_PASSWORD') {
    $isFirstLogin = ($purpose === 'FIRST_LOGIN');
    $subject = $isFirstLogin
        ? "Brew Haven - First Login Email Verification Code"
        : "Brew Haven - Password Reset Verification Code";
    $year = date('Y');

    $introMessage = $isFirstLogin
        ? "Welcome to Brew Haven! To activate your newly created account and proceed with your security setup, please verify your email address using the official verification code below:"
        : "We received a request to reset the password associated with your Brew Haven account. Please use the official verification code below to authorize this request:";

    $body = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>" . htmlspecialchars($subject) . "</title>
    </head>
    <body style='margin: 0; padding: 0; background-color: #f4ece1; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif; color: #2a170e;'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='table-layout: fixed;'>
            <tr>
                <td align='center' style='padding: 30px 15px;'>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 560px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.08); border: 1px solid #e2d2be;'>
                        <!-- Header Banner -->
                        <tr>
                            <td align='center' style='background: linear-gradient(135deg, #2a170e 0%, #3f2415 100%); padding: 28px 20px; border-bottom: 3px solid #c47a2c;'>
                                <h1 style='margin: 0; color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: 1px;'>☕ Brew Haven</h1>
                                <p style='margin: 5px 0 0 0; color: #f0c46b; font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px;'>Security & Authentication Services</p>
                            </td>
                        </tr>

                        <!-- Body Content -->
                        <tr>
                            <td style='padding: 32px 35px;'>
                                <p style='margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #2a170e;'>Dear Valued User,</p>
                                <p style='margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #5a3d28;'>
                                    $introMessage
                                </p>

                                <!-- Verification Code Box -->
                                <table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin: 25px 0;'>
                                    <tr>
                                        <td align='center' style='background-color: #faf5ed; border: 2px dashed #c47a2c; border-radius: 10px; padding: 20px;'>
                                            <span style='display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: #8b5a2b; font-weight: 700; margin-bottom: 8px;'>Your 6-Digit Verification Code</span>
                                            <span style='font-size: 36px; font-weight: 800; letter-spacing: 10px; color: #2a170e; font-family: \"Courier New\", Courier, monospace;'>$otpCode</span>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Important Security Notice -->
                                <div style='background-color: #fff8f0; border-left: 4px solid #c47a2c; padding: 14px 16px; border-radius: 4px; margin-bottom: 24px;'>
                                    <p style='margin: 0 0 6px 0; font-size: 13px; font-weight: 700; color: #8b4513;'>Important Security Reminders:</p>
                                    <ul style='margin: 0; padding-left: 18px; font-size: 13px; color: #6b4425; line-height: 1.5;'>
                                        <li>This verification code is valid for <strong>10 minutes</strong>.</li>
                                        <li>Never share this code with anyone. Brew Haven staff will never ask for your code.</li>
                                        <li>If you did not initiate this request, please contact system administration immediately.</li>
                                    </ul>
                                </div>

                                <p style='margin: 0 0 6px 0; font-size: 14px; color: #5a3d28;'>Sincerely,</p>
                                <p style='margin: 0; font-size: 14px; font-weight: 700; color: #2a170e;'>Brew Haven Security Team</p>
                                <p style='margin: 2px 0 0 0; font-size: 12px; color: #8b5a2b;'>Brew Haven System Administration</p>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td align='center' style='background-color: #f7efe4; padding: 18px 20px; border-top: 1px solid #eae0d0; font-size: 12px; color: #8c7361;'>
                                <p style='margin: 0 0 4px 0;'>&copy; $year Brew Haven. All rights reserved.</p>
                                <p style='margin: 0; font-size: 11px; color: #ab9584;'>This is an automated system email. Please do not reply directly to this message.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ";

    $mailer = new BrewHavenSmtpMailer();
    return $mailer->send($toEmail, $subject, $body);
}
