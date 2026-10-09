<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST request required.', [], 405);
}

$first = trim($_POST['first_name'] ?? '');
$middle = trim($_POST['middle_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$studentId = trim($_POST['student_id'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$confirm = (string)($_POST['password2'] ?? '');
$course = trim($_POST['course'] ?? '');
$campus = trim($_POST['campus'] ?? '');

if (
    !$first ||
    !$last ||
    !$studentId ||
    !$email ||
    !$password ||
    !$course ||
    !$campus
) {
    jsonResponse(
        false,
        'Please complete all required fields.',
        [],
        422
    );
}

if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !str_ends_with($email, '@tip.edu.ph')
) {
    jsonResponse(
        false,
        'Only @tip.edu.ph emails are allowed.',
        [],
        422
    );
}

if ($password !== $confirm) {
    jsonResponse(
        false,
        'Passwords do not match.',
        [],
        422
    );
}

if (strlen($password) < 6) {
    jsonResponse(
        false,
        'Password must be at least 6 characters.',
        [],
        422
    );
}

/*
 * CHECK IF EMAIL OR STUDENT ID ALREADY EXISTS
 */

$stmt = $pdo->prepare(
    "SELECT id FROM users
     WHERE email = ? OR student_id = ?
     LIMIT 1"
);

$stmt->execute([
    $email,
    $studentId
]);

$existing = $stmt->fetch();

if ($existing) {

    $checkEmail = $pdo->prepare(
        "SELECT id FROM users WHERE email = ?"
    );

    $checkEmail->execute([$email]);

    if ($checkEmail->fetch()) {
        jsonResponse(
            false,
            'An account with that email already exists.',
            [],
            409
        );
    }

    jsonResponse(
        false,
        'That Student ID is already registered.',
        [],
        409
    );
}

/*
 * CREATE ACCOUNT
 */

$hash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$stmt = $pdo->prepare(
    "INSERT INTO users
    (
        first_name,
        middle_name,
        last_name,
        student_id,
        email,
        password,
        course,
        campus,
        role
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'student')"
);

$stmt->execute([
    $first,
    $middle ?: null,
    $last,
    $studentId,
    $email,
    $hash,
    $course,
    $campus
]);

$id = (int)$pdo->lastInsertId();

/*
 * GENERATE VERIFICATION CODE
 */

$verificationCode = (string) random_int(
    100000,
    999999
);

/*
 * STORE PENDING VERIFICATION
 */

$_SESSION['pending_2fa_user_id'] = $id;

$_SESSION['pending_2fa_email'] = $email;

$_SESSION['pending_2fa_code_hash'] =
    password_hash(
        $verificationCode,
        PASSWORD_DEFAULT
    );

$_SESSION['pending_2fa_expires'] =
    time() + (5 * 60);

/*
 * SEND VERIFICATION EMAIL
 */

jsonResponseAndContinue(
    true,
    'Verification code is being sent.',
    [
        'requires_2fa' => true
    ]
);

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();

    $mail->Host = 'smtp.gmail.com';

    $mail->SMTPAuth = true;

    $mail->Username =
        'resource.tip.marketplace@gmail.com';

    $mail->Password =
        'qdhu rstf fycb eggj';

    $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port = 587;

    $mail->Timeout = 10;

    $mail->setFrom(
        'resource.tip.marketplace@gmail.com',
        'ReSource'
    );

    $mail->addAddress($email);

    $mail->isHTML(true);

    $mail->Subject =
        'ReSource Account Verification Code';

    $mail->Body = "
        <div style='font-family: Arial, sans-serif; background: #f5f4f1; padding: 24px;'>
            <div style='max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e7e7e7; border-radius: 18px; overflow: hidden;'>
                <div style='background: #171717; color: #ffffff; padding: 18px 24px; border-bottom: 4px solid #f6b429;'>
                    <div style='display: flex; align-items: center; justify-content: space-between; gap: 18px;'>
                        <div style='display: flex; align-items: center; gap: 16px; min-width: 0;'>
                            <div style='display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 999px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.18); color: #ffffff; font-size: 12px; font-weight: 700; line-height: 1; white-space: nowrap;'>
                                <span style='font-size: 16px;'>☰</span>
                                <span>Menu</span>
                            </div>

                            <div style='display: flex; align-items: center; gap: 10px; min-width: 0;'>
                                <div style='display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; background: #f6b429; color: #171717; font-size: 11px; font-weight: 800; letter-spacing: 0.4px;'>TIP</div>
                                <div style='min-width: 0;'>
                                    <div style='font-size: 28px; line-height: 1; font-weight: 800; letter-spacing: 0.3px; white-space: nowrap;'>
                                        <span style='color: #f6b429;'>RE</span><span style='color: #ffffff;'>SOURCE</span>
                                    </div>
                                    <div style='margin-top: 4px; font-size: 9px; letter-spacing: 2px; color: #d7d7d7; white-space: nowrap;'>
                                        TIP CAMPUS MARKETPLACE
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style='display: flex; align-items: center; gap: 10px; flex-shrink: 0;'>
                            <div style='padding: 9px 18px; border-radius: 999px; background: #f6b429; color: #171717; font-size: 12px; font-weight: 800; border: 1px solid rgba(0,0,0,0.06); white-space: nowrap;'>Log In</div>
                            <div style='padding: 9px 18px; border-radius: 999px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.22); color: #ffffff; font-size: 12px; font-weight: 700; white-space: nowrap;'>Sign Up</div>
                            <div style='display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 50%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.22); font-size: 15px; white-space: nowrap;'>👤</div>
                        </div>
                    </div>
                </div>

                <div style='padding: 28px;'>
                    <h2 style='margin: 0 0 16px; color: #171717; font-size: 26px;'>Welcome to ReSource!</h2>

                    <p style='margin: 0 0 20px; color: #3c3c3c; font-size: 16px; line-height: 1.7;'>
                        Thank you for creating a ReSource account.
                    </p>

                    <p style='margin: 0 0 20px; color: #3c3c3c; font-size: 16px; line-height: 1.7;'>
                        Your verification code is:
                    </p>

                    <div style='margin: 0 0 20px; padding: 18px 20px; background: #fff6dc; border: 1px solid #f6d97d; border-radius: 12px; text-align: center;'>
                        <h1 style='margin: 0; letter-spacing: 10px; color: #171717; font-size: 34px;'>
                            {$verificationCode}
                        </h1>
                    </div>

                    <p style='margin: 0 0 12px; color: #3c3c3c; font-size: 15px; line-height: 1.7;'>
                        This code will expire in <strong>5 minutes</strong>.
                    </p>

                    <p style='margin: 0; color: #3c3c3c; font-size: 15px; line-height: 1.7;'>
                        If you did not create a ReSource account, you can safely ignore this email.
                    </p>
                </div>

                <div style='background: #f7f7f7; border-top: 1px solid #e7e7e7; padding: 18px 28px; text-align: center; color: #666666; font-size: 12px;'>
                    © 2026 ReSource • Made for TIPians • A Secure Student Marketplace
                </div>
            </div>
        </div>
    ";

    $mail->AltBody =
        "Your ReSource verification code is: "
        . $verificationCode
        . ". This code expires in 5 minutes.";

    $mail->send();

} catch (Exception $e) {
    error_log('Registration verification email failed: ' . $mail->ErrorInfo);
}