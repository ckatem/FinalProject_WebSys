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
        <div style='font-family: Arial, sans-serif;'>
            <h2>ReSource Email Verification</h2>

            <p>Your verification code is:</p>

            <h1 style='letter-spacing: 8px;'>
                {$verificationCode}
            </h1>

            <p>
                This code will expire in
                <strong>5 minutes</strong>.
            </p>

            <p>
                If you did not try to log in to ReSource,
                you can safely ignore this email.
            </p>
        </div>
    ";

    $mail->AltBody =
        "Your ReSource verification code is: "
        . $verificationCode
        . ". This code expires in 5 minutes.";


    $mail->send();


} catch (Exception $e) {

    unset(
        $_SESSION['pending_2fa_user_id'],
        $_SESSION['pending_2fa_email'],
        $_SESSION['pending_2fa_code_hash'],
        $_SESSION['pending_2fa_expires']
    );

    jsonResponse(
        false,
        'Email error: ' . $mail->ErrorInfo,
        [],
        500
    );
}


/*
|--------------------------------------------------------------------------
| DO NOT LOG THE USER IN YET
|--------------------------------------------------------------------------
|
| The actual $_SESSION['user_id'] will only be created
| after the verification code is entered correctly.
|
|--------------------------------------------------------------------------
*/

jsonResponse(
    true,
    'Verification code sent.',
    [
        'requires_2fa' => true
    ]
);