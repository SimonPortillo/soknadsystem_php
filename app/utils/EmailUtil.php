<?php

namespace app\utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * EmailUtil
 * 
 * Utility class for sending emails using PHPMailer.
 * Handles SMTP configuration and provides methods for sending
 * various types of emails throughout the application.
 * 
 * @package app\utils
 */
class EmailUtil {
    /**
     * @var array Email configuration from config.php
     */
    private array $config;

    /**
     * Constructor
     * 
     * @param array $config Email configuration array from config.php
     */
    public function __construct(array $config) {
        $this->config = $config;
    }

    /**
     * Create and configure a PHPMailer instance
     * 
     * Sets up SMTP configuration, authentication, and security settings
     * based on the application configuration.
     * 
     * @return PHPMailer Configured PHPMailer instance
     * @throws Exception If PHPMailer configuration fails
     */
    private function createMailer(): PHPMailer {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $this->config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->config['smtp_username'];
        $mail->Password   = $this->config['smtp_password'];
        $mail->SMTPSecure = $this->config['smtp_secure'];
        $mail->Port       = $this->config['smtp_port'];
        $mail->CharSet    = 'UTF-8';
        
        // Default from address
        $mail->setFrom($this->config['from_email'], $this->config['from_name']);
        
        // Disable debug output in production
        $mail->SMTPDebug = 0;
        
        return $mail;
    }

    /**
     * Send a password reset email
     * 
     * Sends an email with a password reset link to the specified email address.
     * The link contains a secure token that expires after a set time period.
     * 
     * @param string $email The recipient's email address
     * @param string $token The password reset token
     * @param string $username The recipient's username (for personalization)
     * @return bool True if email was sent successfully, false otherwise
     */
    public function sendPasswordResetEmail(string $email, string $token, string $username): bool {
        try {
            $mail = $this->createMailer();
            
            // Recipient
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Tilbakestill passord - Søknadssystem';
            
            // Generate reset link 
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $resetLink = "{$protocol}://{$host}/reset-password/" . urlencode($token);
            
            // HTML body
            $mail->Body = $this->getPasswordResetHtmlBody($username, $resetLink);
            
            // Plain text alternative
            $mail->AltBody = $this->getPasswordResetTextBody($username, $resetLink);
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            // Log error but don't expose details to user
            error_log("Email sending failed: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Send a generic email
     * 
     * Sends an email to the specified recipient with the given subject and body.
     * Uses plain text format (not HTML).
     * 
     * @param string $toEmail The recipient's email address
     * @param string $subject The subject of the email
     * @param string $body The plain text body of the email
     * @return bool True if the email was sent successfully, false otherwise
     * @throws Exception If PHPMailer encounters an error during sending
     */
    public function sendMail(string $toEmail, string $subject, string $body): bool {
        try {
            $mail = $this->createMailer();
            
            // Recipient
            $mail->addAddress($toEmail);
            
            // Content
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            $mail->send();
            error_log("Email sent successfully to {$toEmail}: {$subject}");
            return true;
            
        } catch (Exception $e) {
            // Log detailed error information for debugging
            error_log("Email sending failed to {$toEmail}. PHPMailer Error: {$mail->ErrorInfo}. Exception: {$e->getMessage()}");
            return false;
        }
    }


    /**
     * Generate HTML email body for password reset
     * 
     * @param string $username The recipient's username
     * @param string $resetLink The password reset link
     * @return string HTML email content
     */
    private function getPasswordResetHtmlBody(string $username, string $resetLink): string {
        return "
<!DOCTYPE html>
<html lang='no'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #0d6efd; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f8f9fa; padding: 30px; border-radius: 5px; margin-top: 20px; }
        .button { display: inline-block; padding: 12px 24px; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; text-align: center; }
        .warning { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Tilbakestill passord</h1>
        </div>
        <div class='content'>
            <p>Hei {$username},</p>
            
            <p>Vi har mottatt en forespørsel om å tilbakestille passordet ditt for Søknadssystem-kontoen din.</p>
            
            <p>Klikk på knappen nedenfor for å tilbakestille passordet ditt:</p>
            
            <center>
                <a href='{$resetLink}' class='button'>Tilbakestill passord</a>
            </center>
            
            <p>Eller kopier og lim inn følgende lenke i nettleseren din:</p>
            <p style='word-break: break-all; background-color: #e9ecef; padding: 10px; border-radius: 3px;'>{$resetLink}</p>
            
            <div class='warning'>
                <strong>Viktig:</strong> Denne lenken utløper om 1 time av sikkerhetsgrunner.
            </div>
            
            <p>Hvis du ikke ba om å tilbakestille passordet ditt, kan du ignorere denne e-posten. Passordet ditt vil forbli uendret.</p>
        </div>
        <div class='footer'>
            <p>Dette er en automatisk e-post. Vennligst ikke svar på denne e-posten.</p>
            <p>&copy; " . date('Y') . " Søknadssystem. Martin og Simon.</p>
        </div>
    </div>
</body>
</html>
";
    }

    /**
     * Generate plain text email body for password reset
     * 
     * @param string $username The recipient's username
     * @param string $resetLink The password reset link
     * @return string Plain text email content
     */
    private function getPasswordResetTextBody(string $username, string $resetLink): string {
        return "
Hei {$username},

Vi har mottatt en forespørsel om å tilbakestille passordet ditt for Søknadssystem-kontoen din.

For å tilbakestille passordet ditt, besøk følgende lenke:
{$resetLink}

VIKTIG: Denne lenken utløper om 1 time av sikkerhetsgrunner.

Hvis du ikke ba om å tilbakestille passordet ditt, kan du ignorere denne e-posten. Passordet ditt vil forbli uendret.

Dette er en automatisk e-post. Vennligst ikke svar på denne e-posten.

© " . date('Y') . " Søknadssystem. Martin og Simon.
";
    }
}
