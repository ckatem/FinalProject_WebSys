<?php

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| Only allow POST requests
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    jsonResponse(
        false,
        'POST request required.',
        [],
        405
    );
}


/*
|--------------------------------------------------------------------------
| Get verification code
|--------------------------------------------------------------------------
*/

$code = trim($_POST['code'] ?? '');


/*
|--------------------------------------------------------------------------
| Make sure a 6-digit code was entered
|--------------------------------------------------------------------------
*/

if (!preg_match('/^\d{6}$/', $code)) {

    jsonResponse(
        false,
        'Please enter the 6-digit verification code.',
        [],
        422
    );
}


/*
|--------------------------------------------------------------------------
| Check if there is a pending login
|--------------------------------------------------------------------------
*/

if (
    empty($_SESSION['pending_2fa_user_id']) ||
    empty($_SESSION['pending_2fa_code_hash']) ||
    empty($_SESSION['pending_2fa_expires'])
) {

    jsonResponse(
        false,
        'No verification request found. Please log in again.',
        [],
        401
    );
}


/*
|--------------------------------------------------------------------------
| Check if code has expired
|--------------------------------------------------------------------------
*/

if (time() > $_SESSION['pending_2fa_expires']) {

    unset(
        $_SESSION['pending_2fa_user_id'],
        $_SESSION['pending_2fa_email'],
        $_SESSION['pending_2fa_code_hash'],
        $_SESSION['pending_2fa_expires']
    );

    jsonResponse(
        false,
        'Your verification code has expired. Please log in again.',
        [],
        401
    );
}


/*
|--------------------------------------------------------------------------
| Verify the code
|--------------------------------------------------------------------------
*/

if (!password_verify(
    $code,
    $_SESSION['pending_2fa_code_hash']
)) {

    jsonResponse(
        false,
        'Incorrect verification code.',
        [],
        401
    );
}


/*
|--------------------------------------------------------------------------
| Get the user ID
|--------------------------------------------------------------------------
*/

$userId = (int) $_SESSION['pending_2fa_user_id'];


/*
|--------------------------------------------------------------------------
| Get user information
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        id,
        first_name,
        middle_name,
        last_name,
        student_id,
        email,
        course,
        campus,
        role,
        profile_photo,
        notify_messages,
        notify_listings,
        privacy_photo,
        privacy_course,
        created_at
     FROM users
     WHERE id=?
     LIMIT 1"
);

$stmt->execute([$userId]);

$user = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Make sure user still exists
|--------------------------------------------------------------------------
*/

if (!$user) {

    unset(
        $_SESSION['pending_2fa_user_id'],
        $_SESSION['pending_2fa_email'],
        $_SESSION['pending_2fa_code_hash'],
        $_SESSION['pending_2fa_expires']
    );

    jsonResponse(
        false,
        'User account could not be found.',
        [],
        404
    );
}


/*
|--------------------------------------------------------------------------
| Create the REAL authenticated session
|--------------------------------------------------------------------------
*/

session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];


/*
|--------------------------------------------------------------------------
| Remove temporary 2FA information
|--------------------------------------------------------------------------
*/

unset(
    $_SESSION['pending_2fa_user_id'],
    $_SESSION['pending_2fa_email'],
    $_SESSION['pending_2fa_code_hash'],
    $_SESSION['pending_2fa_expires']
);


/*
|--------------------------------------------------------------------------
| Return successful login
|--------------------------------------------------------------------------
*/

jsonResponse(
    true,
    'Verification successful. Login complete.',
    [
        'user' => $user
    ]
);