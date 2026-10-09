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
        <div style='font-family: Arial, sans-serif;'>

            <h2>Welcome to ReSource!</h2>

            <p>
                Thank you for creating a ReSource account.
            </p>

            <p>
                Your verification code is:
            </p>

            <h1 style='letter-spacing: 8px;'>
                {$verificationCode}
            </h1>

            <p>
                This code will expire in
                <strong>5 minutes</strong>.
            </p>

            <p>
                If you did not create a ReSource account,
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
    error_log('Registration verification email failed: ' . $mail->ErrorInfo);
}