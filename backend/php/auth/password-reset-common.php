<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function ensurePasswordResetTable(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS password_resets (
            user_id INT UNSIGNED PRIMARY KEY,
            code_hash VARCHAR(255) NOT NULL,
            expires_at BIGINT UNSIGNED NOT NULL,
            requested_at BIGINT UNSIGNED NOT NULL,
            attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id)
                REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );
}

function sendPasswordResetCode(string $email, string $code): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'resource.tip.marketplace@gmail.com';
        $mail->Password = 'qdhu rstf fycb eggj';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 10;
        $mail->setFrom('resource.tip.marketplace@gmail.com', 'ReSource');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'ReSource Password Reset Code';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif;'>
                <h2>ReSource Password Reset</h2>
                <p>Your password reset code is:</p>
                <h1 style='letter-spacing: 8px;'>{$code}</h1>
                <p>This code expires in <strong>10 minutes</strong>.</p>
                <p>If you did not request a password reset, you can ignore this email.</p>
            </div>
        ";
        $mail->AltBody = "Your ReSource password reset code is {$code}. It expires in 10 minutes.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Password reset email failed: ' . $mail->ErrorInfo);
        return false;
    }
}