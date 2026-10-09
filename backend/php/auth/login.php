<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST request required.', [], 405);
}


$email = strtolower(trim($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$requestedRole = $_POST['role'] ?? 'student';


/*
|--------------------------------------------------------------------------
| Validate account type
|--------------------------------------------------------------------------
*/

if (!in_array($requestedRole, ['student', 'admin'], true)) {
    jsonResponse(false, 'Please choose a valid account type.', [], 422);
}


/*
|--------------------------------------------------------------------------
| Validate TIP email
|--------------------------------------------------------------------------
*/

if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !str_ends_with($email, '@tip.edu.ph')
) {
    jsonResponse(
        false,
        'Please use your TIP institutional email.',
        [],
        422
    );
}


/*
|--------------------------------------------------------------------------
| Find user
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT * FROM users WHERE email=? LIMIT 1"
);

$stmt->execute([$email]);

$user = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Check password
|--------------------------------------------------------------------------
*/

if (!$user || !password_verify($password, $user['password'])) {

    jsonResponse(
        false,
        'Incorrect email or password.',
        [],
        401
    );
}


/*
|--------------------------------------------------------------------------
| Check account role
|--------------------------------------------------------------------------
*/

if ($user['role'] !== $requestedRole) {

    jsonResponse(
        false,
        'This account does not have the selected account type.',
        [],
        403
    );
}

if ($user['role'] === 'admin') {

    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];

    jsonResponse(
        true,
        'Login successful.',
        [
            'requires_2fa' => false,
            'user' => $user
        ]
    );
}


/*
|--------------------------------------------------------------------------
| Generate 6-digit verification code
|--------------------------------------------------------------------------
*/

$verificationCode = (string) random_int(100000, 999999);


/*
|--------------------------------------------------------------------------
| Store temporary 2FA information
|--------------------------------------------------------------------------
*/

$_SESSION['pending_2fa_user_id'] = (int) $user['id'];

$_SESSION['pending_2fa_email'] = $email;

$_SESSION['pending_2fa_code_hash'] =
    password_hash($verificationCode, PASSWORD_DEFAULT);

$_SESSION['pending_2fa_expires'] =
    time() + (5 * 60);


/*
|--------------------------------------------------------------------------
| Send verification email
|--------------------------------------------------------------------------
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

    $mail->Username = 'resource.tip.marketplace@gmail.com';

    $mail->Password = 'qdhu rstf fycb eggj';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port = 587;

    $mail->Timeout = 10;


    /*
    |--------------------------------------------------------------------------
    | Sender
    |--------------------------------------------------------------------------
    */

    $mail->setFrom(
        'resource.tip.marketplace@gmail.com',
        'ReSource'
    );


    /*
    |--------------------------------------------------------------------------
    | Recipient
    |--------------------------------------------------------------------------
    */

    $mail->addAddress(
        $email
    );


    /*
    |--------------------------------------------------------------------------
    | Email content
    |--------------------------------------------------------------------------
    */

    $mail->isHTML(true);

    $mail->Subject = 'ReSource Login Verification Code';

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
                    <h2 style='margin: 0 0 16px; color: #171717; font-size: 26px;'>ReSource Email Verification</h2>

                    <p style='margin: 0 0 20px; color: #3c3c3c; font-size: 16px;'>Your verification code is:</p>

                    <div style='margin: 0 0 20px; padding: 18px 20px; background: #fff6dc; border: 1px solid #f6d97d; border-radius: 12px; text-align: center;'>
                        <h1 style='margin: 0; letter-spacing: 10px; color: #171717; font-size: 34px;'>
                            {$verificationCode}
                        </h1>
                    </div>

                    <p style='margin: 0 0 12px; color: #3c3c3c; font-size: 15px; line-height: 1.7;'>
                        This code will expire in <strong>5 minutes</strong>.
                    </p>

                    <p style='margin: 0; color: #3c3c3c; font-size: 15px; line-height: 1.7;'>
                        If you did not try to log in to ReSource, you can safely ignore this email.
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
    error_log('Login verification email failed: ' . $mail->ErrorInfo);
}