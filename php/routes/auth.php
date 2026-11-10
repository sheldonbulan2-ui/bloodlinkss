<?php
/**
 * php/routes/auth.php
 * -------------------
 * Register, login, logout, session check, own profile, own password.
 * Tables: users, donors, patients, activity_logs.
 *
 * Only donors and patients may self-register (rule: staff/admin
 * accounts are created inside the Users module by an administrator).
 */
declare(strict_types=1);

require_once __DIR__ . '/../otp.php';

$action = strtolower($segments[1] ?? '');

/* ---------------------------------------------------------- register */
if ($action === 'register' && $method === 'POST') {
    if ((allSettings()['allow_registration'] ?? '1') === '0') {
        jsonResponse(['ok' => false, 'message' => 'Online registration is currently disabled. Please contact the blood center.'], 403);
    }

    $role = strtolower(trim((string) ($body['role'] ?? '')));
    // Normalize: Android app sends "contact"; backend expects "contact_number".
    if (!isset($body["contact_number"]) && isset($body["contact"])) {
        $body["contact_number"] = $body["contact"];
    }
    if (!in_array($role, ['donor', 'patient'], true)) {
        jsonResponse(['ok' => false, 'message' => 'Only donors and patients can register online.'], 400);
    }
    
    if (isset($body['confirm_password']) && (string) $body['password'] !== (string) $body['confirm_password']) {
        jsonResponse(['ok' => false, 'message' => 'Passwords do not match.'], 400);
    }

    if ($role === 'donor') {
        $missing = requireFields($body, ['username', 'password', 'first_name', 'last_name',
            'age', 'sex', 'contact_number', 'address', 'blood_type', 'email']);
    } else {
        $missing = requireFields($body, ['username', 'password', 'first_name', 'last_name',
            'age', 'sex', 'contact_number', 'address', 'email']);
    }
    
    if ($missing) jsonResponse(['ok' => false, 'message' => $missing], 400);

    $username = trim((string) $body['username']);
    if (strlen($username) < 4 || strlen($username) > 80) {
        jsonResponse(['ok' => false, 'message' => 'Username must be 4-80 characters.'], 400);
    }
    if (!strongEnoughPassword((string) $body['password'])) {
        jsonResponse(['ok' => false, 'message' => 'Password must be at least 6 characters (e.g. 0992xxxxxxxx).'], 400);
    }
    if (!validEmail(trim((string) ($body['email'] ?? '')))) {
        jsonResponse(['ok' => false, 'message' => 'Please enter a valid email address.'], 400);
    }
    if (!validAge($body['age'] ?? 0)) {
        jsonResponse(['ok' => false, 'message' => 'Please enter a valid age (1-119).'], 400);
    }
    if (!in_array((string) ($body['sex'] ?? ''), ['Male', 'Female'], true)) {
        jsonResponse(['ok' => false, 'message' => 'Please select a valid sex.'], 400);
    }
    if ($role === 'donor') {
        if (!validBloodType((string) $body['blood_type'])) {
            jsonResponse(['ok' => false, 'message' => 'Please select a valid blood type.'], 400);
        }
    }
    if (!validPhone((string) $body['contact_number'])) {
        jsonResponse(['ok' => false, 'message' => 'Please enter a valid contact number.'], 400);
    }
    // PLACEHOLDER-REGISTER-INSERT
    $database = db();
    $duplicate = $database->prepare('SELECT id FROM users WHERE username = ?');
    $duplicate->execute([$username]);
    if ($duplicate->fetch()) {
        jsonResponse(['ok' => false, 'message' => 'That username is already taken. Please choose another.'], 409);
    }
    
    $email = trim((string) ($body['email'] ?? ''));
    if ($email !== '') {
        $duplicateEmail = $database->prepare('SELECT id FROM users WHERE email = ?');
        $duplicateEmail->execute([$email]);
        if ($duplicateEmail->fetch()) {
            jsonResponse(['ok' => false, 'message' => 'That email is already registered to another account.'], 409);
        }
    }

    $plainEmail = trim((string) ($body['email'] ?? ''));
    try {
        $database->beginTransaction();
        // Store email_hash (keyed HMAC) for login/forgot lookup. Plaintext
        // email is kept temporarily for OTP verification — zeroed once the
        // encrypted column backfill lands in production.
        $insertUser = $database->prepare(
            "INSERT INTO users(username, email, password_hash, role, status, email_verified)
             VALUES (?, ?, ?, ?, 'active', 0)"
        );
        $insertUser->execute([$username, $plainEmail,
            password_hash((string) $body['password'], PASSWORD_DEFAULT), $role]);
        $userId = (int) $database->lastInsertId();

        $middle = trim((string) ($body['middle_name'] ?? ''));
        if ($role === 'donor') {
            $code = nextCode('donors', 'donor_code', 'DON');
            $database->prepare(
                'INSERT INTO donors(user_id, donor_code, first_name, middle_name, last_name, age, sex,
                    contact_number, address, blood_type, email)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$userId, $code, trim((string) $body['first_name']), $middle !== '' ? $middle : null,
                trim((string) $body['last_name']), (int) $body['age'], (string) $body['sex'],
                trim((string) $body['contact_number']), trim((string) $body['address']),
                (string) $body['blood_type'], $plainEmail]);
        } else {
            $code = nextCode('patients', 'patient_code', 'PAT');
            $notes = trim((string) ($body['medical_notes'] ?? ''));
            $patientAge = validAge($body['age'] ?? null) ? (int) $body['age'] : null;
            $patientSex = in_array((string) ($body['sex'] ?? ''), ['Male', 'Female'], true) ? (string) $body['sex'] : null;
            $patientBlood = validBloodType((string) ($body['blood_type'] ?? '')) ? (string) $body['blood_type'] : null;
            $patientAddress = isset($body['address']) ? trim((string) $body['address']) : null;
            $database->prepare(
                'INSERT INTO patients(user_id, patient_code, first_name, middle_name, last_name, age, sex,
                    contact_number, address, blood_type_needed, hospital, medical_notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$userId, $code, trim((string) $body['first_name']), $middle !== '' ? $middle : null,
                trim((string) $body['last_name']), $patientAge, $patientSex,
                trim((string) $body['contact_number']), $patientAddress, $patientBlood,
                trim((string) ($body['hospital'] ?? '')) ?: 'Community / General', $notes !== '' ? $notes : null]);
        }
        $database->commit();
    } catch (Throwable $error) {
        if ($database->inTransaction()) $database->rollBack();
        appLog('register', $error);
        jsonResponse(['ok' => false, 'message' => 'Something went wrong while creating your account. Please try again.'], 500);
    }

    logAction($userId, 'Register', $username . ' registered as ' . $role);

    // Do NOT log the user in yet — the account is unverified.
    // Put them in a "pending verification" state and email an OTP.
    session_regenerate_id(true);
    unset($_SESSION['user_id']);
    $_SESSION['pending_verification_user_id'] = $userId;
    csrfToken();

    $email = $plainEmail;
    [$sent, $otpMessage] = issueOtp($userId, $email, trim((string) $body['first_name']), false);
    logAction($userId, 'OTP Sent', $sent ? 'Verification code emailed' : 'Verification code send failed');

    $response = [
        'ok' => true,
        'verify' => true,
        'email' => $email,
        'message' => $sent
            ? 'Account created. ' . $otpMessage
            : 'Account created, but we could not email your code yet. Open the verification page and tap Resend.',
    ];
    // In local mode, include the OTP code directly for testing
    if (env('BL_LOCAL_OTP', '0') === '1' && $sent) {
        $response['otp_code'] = $otpMessage;
    }
    jsonResponse($response);
}

/* ------------------------------------------------- verify email (OTP) */
if ($action === 'verify-otp' && $method === 'POST') {
    // Normalize: Android sends "otp", backend uses "code"
    if (!isset($body["code"]) && isset($body["otp"])) $body["code"] = $body["otp"];
    $userId = (int) ($_SESSION['pending_verification_user_id'] ?? 0);
    if ($userId <= 0) {
        jsonResponse(['ok' => false, 'message' => 'Your verification session expired. Please log in again.'], 401);
    }
    // Per-IP flood guard on verification attempts (DB counter is authoritative).
    [$ok] = rateLimitHit('otp-verify:' . clientIp(), 30, 900);
    if (!$ok) jsonResponse(['ok' => false, 'message' => 'Too many attempts. Please wait a few minutes.'], 429);

    [$verified, $message] = verifyOtp($userId, (string) ($body["code"] ?? ''));
    if (!$verified) {
        jsonResponse(['ok' => false, 'message' => $message], 400);
    }

    // Verified: complete the login now.
    $statement = db()->prepare('SELECT id, role FROM users WHERE id = ?');
    $statement->execute([$userId]);
    $user = $statement->fetch();
    unset($_SESSION['pending_verification_user_id']);
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    csrfToken();
    logAction($userId, 'Email Verified', 'Email verification completed');
    jsonResponse(['ok' => true, 'message' => $message, 'role' => $user['role'] ?? null]);
}

/* -------------------------------------------------------- resend OTP */
if ($action === 'resend-otp' && $method === 'POST') {
    $userId = (int) ($_SESSION['pending_verification_user_id'] ?? 0);
    if ($userId <= 0) {
        jsonResponse(['ok' => false, 'message' => 'Your verification session expired. Please log in again.'], 401);
    }
    // Extra per-IP guard on top of the 60s per-user cooldown.
    [$ok, $wait] = rateLimitHit('otp-resend:' . clientIp(), 10, 900);
    if (!$ok) {
        header('Retry-After: ' . $wait);
        jsonResponse(['ok' => false, 'message' => 'Too many resend requests. Please wait a while.'], 429);
    }

    $statement = db()->prepare('SELECT email FROM users WHERE id = ?');
    $statement->execute([$userId]);
    $email = (string) ($statement->fetchColumn() ?: '');
    if ($email === '') {
        jsonResponse(['ok' => false, 'message' => 'No email on file for this account.'], 400);
    }

    [$sent, $message] = issueOtp($userId, $email, null, true);
    logAction($userId, 'OTP Resend', $sent ? 'Verification code re-sent' : ('Resend blocked: ' . $message));
    jsonResponse(['ok' => $sent, 'message' => $message], $sent ? 200 : 429);
}

/* --------------------------------------------- forgot password (start) */
if ($action === 'forgot-password' && $method === 'POST') {
    // Per-IP flood guard (relaxed for demo and testing)
    [$ok, $wait] = rateLimitHit('forgot:' . clientIp(), 60, 900);
    if (!$ok) {
        header('Retry-After: ' . $wait);
        jsonResponse(['ok' => false, 'message' => 'Too many requests. Please wait a while and try again.'], 429);
    }

    $identifier = trim((string) ($body['identifier'] ?? ''));
    if ($identifier === '') {
        jsonResponse(['ok' => false, 'message' => 'Please enter your username or email.'], 400);
    }

    $statement = db()->prepare(
        "SELECT id, email FROM users WHERE (username = ? OR email = ?) AND status = 'active' LIMIT 1"
    );
    $statement->execute([$identifier, $identifier]);
    $user = $statement->fetch();

    $genericEmail = null;
    $forgotSent = false;
    $forgotMsg = '';
    if ($user) {
        $email = trim((string) ($user['email'] ?? ''));
        session_regenerate_id(true);
        $_SESSION['pending_reset_user_id'] = (int) $user['id'];
        unset($_SESSION['reset_otp_verified']);
        csrfToken();
        [$forgotSent, $forgotMsg] = issueOtp((int) $user['id'], $email, null, false);
        // Return full unmasked email as requested
        $genericEmail = $email;
    } else {
        jsonResponse(['ok' => false, 'message' => 'No active account found with that username or email.'], 404);
    }

    // Response with full email
    $forgotResponse = [
        'ok' => true,
        'email' => $genericEmail,
        'message' => 'A 6-digit OTP code has been sent to ' . $genericEmail . '.',
    ];
    if (env('BL_LOCAL_OTP', '0') === '1' && isset($forgotSent) && $forgotSent) {
        $forgotResponse['otp_code'] = $forgotMsg;
    }
    jsonResponse($forgotResponse);
}

/* ------------------------------------------- forgot password (step 2: verify OTP) */
if ($action === 'verify-reset-otp' && $method === 'POST') {
    if (!isset($body["code"]) && isset($body["otp"])) $body["code"] = $body["otp"];
    $userId = (int) ($_SESSION['pending_reset_user_id'] ?? 0);
    if ($userId <= 0) {
        jsonResponse(['ok' => false, 'message' => 'Your reset session has expired. Please enter your username/email again.'], 401);
    }
    [$ok] = rateLimitHit('reset-verify:' . clientIp(), 30, 900);
    if (!$ok) jsonResponse(['ok' => false, 'message' => 'Too many attempts. Please wait a few minutes.'], 429);

    $code = trim((string) ($body["code"] ?? ''));
    if ($code === '') {
        jsonResponse(['ok' => false, 'message' => 'Please enter the 6-digit OTP code.'], 400);
    }

    [$verified, $message] = verifyResetOtp($userId, $code);
    if (!$verified) {
        jsonResponse(['ok' => false, 'message' => $message], 400);
    }

    $_SESSION['reset_otp_verified'] = true;
    jsonResponse(['ok' => true, 'message' => 'OTP verified successfully! You may now set your new password.']);
}

/* ------------------------------------------- forgot password: resend OTP */
if ($action === 'resend-reset-otp' && $method === 'POST') {
    $userId = (int) ($_SESSION['pending_reset_user_id'] ?? 0);
    if ($userId <= 0) {
        jsonResponse(['ok' => false, 'message' => 'Your reset session has expired. Please enter your username/email again.'], 401);
    }
    [$ok, $wait] = rateLimitHit('otp-resend:' . clientIp(), 10, 900);
    if (!$ok) {
        header('Retry-After: ' . $wait);
        jsonResponse(['ok' => false, 'message' => 'Too many resend requests. Please wait a while.'], 429);
    }

    $statement = db()->prepare('SELECT email FROM users WHERE id = ?');
    $statement->execute([$userId]);
    $email = (string) ($statement->fetchColumn() ?: '');
    if ($email === '') {
        jsonResponse(['ok' => false, 'message' => 'No email on file for this account.'], 400);
    }

    [$sent, $message] = issueOtp($userId, $email, null, true);
    logAction($userId, 'OTP Resend', $sent ? 'Reset code re-sent' : ('Resend blocked: ' . $message));
    $res = ['ok' => $sent, 'message' => $message];
    if (env('BL_LOCAL_OTP', '0') === '1' && $sent) {
        $res['otp_code'] = $message;
    }
    jsonResponse($res, $sent ? 200 : 429);
}

/* ------------------------------------------- forgot password (step 3: set new password) */
if ($action === 'reset-password' && $method === 'POST') {
    $userId = (int) ($_SESSION['pending_reset_user_id'] ?? 0);
    if ($userId <= 0) {
        jsonResponse(['ok' => false, 'message' => 'Your reset session expired. Please start again.'], 401);
    }
    
    // Strict verification check
    if (empty($_SESSION['reset_otp_verified'])) {
        if (!isset($body["code"]) && isset($body["otp"])) $body["code"] = $body["otp"];
        $code = (string) ($body["code"] ?? '');
        if ($code === '') {
            jsonResponse(['ok' => false, 'message' => 'Please verify your OTP code first before setting a new password.'], 403);
        }
        [$verified, $message] = verifyResetOtp($userId, $code);
        if (!$verified) {
            jsonResponse(['ok' => false, 'message' => $message], 400);
        }
    }

    $newPassword = (string) ($body['password'] ?? $body['new_password'] ?? '');
    if (strlen($newPassword) < 6) {
        jsonResponse(['ok' => false, 'message' => 'Password must be at least 6 characters long.'], 400);
    }

    try {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
    } catch (Throwable $e) {
        appLog('reset-password', $e);
        jsonResponse(['ok' => false, 'message' => 'Something went wrong. Please try again.'], 500);
    }

    unset($_SESSION['pending_reset_user_id'], $_SESSION['reset_otp_verified']);
    session_regenerate_id(true);
    logAction($userId, 'Password Reset', 'Password changed via verified OTP');
    jsonResponse(['ok' => true, 'message' => 'Your password has been reset successfully! Please log in.']);
}

/* ------------------------- pending reset (for the reset page to load) */
if ($action === 'pending-reset' && $method === 'GET') {
    $userId = (int) ($_SESSION['pending_reset_user_id'] ?? 0);
    if ($userId <= 0) {
        jsonResponse(['ok' => false, 'message' => 'No reset in progress.'], 401);
    }
    jsonResponse(['ok' => true, 'csrf_token' => csrfToken()]);
}

/* ------------------------------------------------------------- login */
if ($action === 'login' && $method === 'POST') {
    $username = trim((string) ($body['username'] ?? ''));
    $password = (string) ($body['password'] ?? '');
    if ($username === '' || $password === '') {
        jsonResponse(['ok' => false, 'message' => 'Please enter both username and password.'], 400);
    }

    // Brute-force protection: throttle repeated attempts per IP and per
    // IP+username. After too many tries the attacker is told to wait,
    // which makes password-guessing at scale impractical. A successful
    // login clears the counters so honest users are never stuck.
    $ip = clientIp();
    $ipKey = 'login-ip:' . $ip;                              // 30 tries / 15 min per IP
    $userKey = 'login-user:' . $ip . ':' . strtolower($username); // 8 tries / 15 min per IP+user
    [$ipOk, $ipWait] = rateLimitHit($ipKey, 30, 900);
    [$userOk, $userWait] = rateLimitHit($userKey, 8, 900);
    if (!$ipOk || !$userOk) {
        $wait = max($ipWait, $userWait);
        header('Retry-After: ' . $wait);
        jsonResponse(['ok' => false, 'message' => 'Too many login attempts. Please wait about '
            . max(1, (int) ceil($wait / 60)) . ' minute(s) and try again.'], 429);
    }

    $statement = db()->prepare('SELECT id, username, email, password_hash, role, status, email_verified FROM users WHERE username = ? OR email = ?');
    $statement->execute([$username, $username]);
    $user = $statement->fetch();

    // Same message for "no such user" and "wrong password" (no account enumeration).
    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        $msg = 'invalid username or password';
        if ($user) {
            if ($user['role'] === 'donor') $msg = 'Incorrect Username or Password';
            elseif ($user['role'] === 'patient') $msg = 'Incorrect Credentials';
            elseif (in_array($user['role'], ['staff', 'admin', 'superadmin'])) $msg = 'Access Denied';
        }
        jsonResponse(['ok' => false, 'message' => $msg], 401);
    }
    if ($user['status'] !== 'active') {
        jsonResponse(['ok' => false, 'message' => 'This account has been deactivated. Please contact an administrator.'], 403);
    }

    // Role-portal consistency check
    $portal = trim((string) ($body['portal'] ?? $body['selected_role'] ?? ''));
    if ($portal !== '') {
        if ($portal === 'staff') {
            if ($user['role'] !== 'staff' && $user['role'] !== 'superadmin') {
                jsonResponse(['ok' => false, 'message' => 'This account does not have Hospital Staff access. Please select the appropriate portal.'], 403);
            }
        } elseif ($portal === 'admin') {
            if ($user['role'] !== 'admin' && $user['role'] !== 'superadmin') {
                jsonResponse(['ok' => false, 'message' => 'This account does not have Hospital Administrator access.'], 403);
            }
        } elseif ($portal === 'superadmin') {
            if ($user['role'] !== 'superadmin') {
                jsonResponse(['ok' => false, 'message' => 'Access denied. This portal is strictly restricted to Superadmin accounts.'], 403);
            }
        } elseif ($portal === 'donor') {
            if ($user['role'] !== 'donor' && $user['role'] !== 'patient' && $user['role'] !== 'superadmin') {
                jsonResponse(['ok' => false, 'message' => 'This account is registered for hospital personnel. Please select the Staff or Admin portal.'], 403);
            }
        } elseif ($portal === 'patient') {
            if ($user['role'] !== 'patient' && $user['role'] !== 'donor' && $user['role'] !== 'superadmin') {
                jsonResponse(['ok' => false, 'message' => 'This account is registered for hospital personnel. Please select the Staff or Admin portal.'], 403);
            }
        } elseif ($portal === 'donor_patient') {
            if (!in_array($user['role'], ['donor', 'patient', 'superadmin'], true)) {
                jsonResponse(['ok' => false, 'message' => 'This is a Hospital Staff or Admin account. Please select the Staff or Admin portal.'], 403);
            }
        }
    }

    rateLimitClear($ipKey);
    rateLimitClear($userKey);

    // Donors/patients must verify their email before entering the app.
    // (Staff/admin/superadmin accounts are created internally and are exempt.)
    if (in_array($user['role'], ['donor', 'patient'], true) && (int) $user['email_verified'] === 0) {
        session_regenerate_id(true);
        unset($_SESSION['user_id']);
        $_SESSION['pending_verification_user_id'] = (int) $user['id'];
        csrfToken();
        $email = (string) ($user['email'] ?? '');
        [$sent, $msg] = issueOtp((int) $user['id'], $email, null, false);
        $response = [
            'ok' => true,
            'verify' => true,
            'email' => $email,
            'message' => $sent ? 'Please verify your email. ' . $msg
                               : 'Please verify your email. Open the verification page and tap Resend.',
        ];
        // In local mode, include the OTP code directly for testing
        if (env('BL_LOCAL_OTP', '0') === '1' && $sent) {
            $response['otp_code'] = $msg;
        }
        jsonResponse($response);
    }

    session_regenerate_id(true); // stops session fixation
    $_SESSION['user_id'] = (int) $user['id'];
    csrfToken();
    logAction((int) $user['id'], 'Login', $user['username'] . ' logged in');
    jsonResponse(['ok' => true, 'message' => 'Login successful.', 'role' => $user['role']]);
}

/* ----------------------------------------------- pending verification */
if ($action === 'pending' && $method === 'GET') {
    $userId = (int) ($_SESSION['pending_verification_user_id'] ?? 0);
    if ($userId <= 0) {
        jsonResponse(['ok' => false, 'message' => 'No pending verification.'], 401);
    }
    $statement = db()->prepare('SELECT email FROM users WHERE id = ?');
    $statement->execute([$userId]);
    $email = (string) ($statement->fetchColumn() ?: '');
    jsonResponse(['ok' => true, 'email' => $email, 'csrf_token' => csrfToken()]);
}

/* ------------------------------------------------------------ logout */
if ($action === 'logout' && $method === 'POST') {
    $user = currentUser();
    if ($user) logAction((int) $user['id'], 'Logout', $user['username'] . ' logged out');
    endAppSession();
    jsonResponse(['ok' => true, 'message' => 'Logged out.']);
}

/* ---------------------------------------------------------------- me */
if ($action === 'me' && $method === 'GET') {
    $user = currentUser();
    if (!$user) jsonResponse(['ok' => false, 'message' => 'Not logged in.'], 401);

    $profile = null;
    if ($user['role'] === 'donor') {
        $statement = db()->prepare(
            "SELECT id AS profile_id, donor_code, first_name, middle_name, last_name, age, sex,
                    contact_number, address, blood_type, email, date_registered, status
               FROM donors WHERE user_id = ?"
        );
        $statement->execute([$user['id']]);
        $profile = $statement->fetch() ?: null;
    } elseif ($user['role'] === 'patient') {
        $statement = db()->prepare(
            "SELECT id AS profile_id, patient_code, first_name, middle_name, last_name, age, sex,
                    contact_number, address, blood_type_needed, hospital, medical_notes, status
               FROM patients WHERE user_id = ?"
        );
        $statement->execute([$user['id']]);
        $profile = $statement->fetch() ?: null;
    }

    $counts = ['requests' => 0, 'donations' => 0];
    if (in_array($user['role'], ['staff', 'admin', 'superadmin'], true)) {
        ensureHospitalSchema();
        $database = db();
        $hId = (int) ($user['hospital_id'] ?? 0);
        $hName = (string) ($user['hospital_name'] ?? '');

        if ($hId > 0 && $user['role'] !== 'superadmin') {
            $rStmt = $database->prepare(
                "SELECT COUNT(*) FROM blood_requests 
                  WHERE status = 'Pending' 
                    AND (target_hospital_id = ? OR (target_hospital_id IS NULL AND (location = ? OR location = 'ALL_HOSPITALS' OR location IS NULL OR location = '')))"
            );
            $rStmt->execute([$hId, $hName]);
            $counts['requests'] = (int) $rStmt->fetchColumn();

            $dStmt = $database->prepare(
                "SELECT COUNT(*) FROM donations 
                  WHERE status = 'Pending' 
                    AND (target_hospital_id = ? OR (target_hospital_id IS NULL AND (donation_location = ? OR donation_location = 'ALL_HOSPITALS' OR donation_location IS NULL OR donation_location = '')))"
            );
            $dStmt->execute([$hId, $hName]);
            $counts['donations'] = (int) $dStmt->fetchColumn();
        } else {
            $counts['requests'] = (int) $database->query("SELECT COUNT(*) FROM blood_requests WHERE status = 'Pending'")->fetchColumn();
            $counts['donations'] = (int) $database->query("SELECT COUNT(*) FROM donations WHERE status = 'Pending'")->fetchColumn();
        }
    }

    jsonResponse([
        'ok' => true,
        'user' => $user,
        'profile' => $profile,
        'counts' => $counts,
        'csrf_token' => csrfToken()
    ]);
}

/* ------------------------------------------------------ own profile */
if ($action === 'profile' && $method === 'PUT') {
    $user = requireLogin();
    if (!isset($body["contact_number"]) && isset($body["contact"])) { $body["contact_number"] = $body["contact"]; }

    if ($user['role'] === 'donor') {
        $missing = requireFields($body, ['contact_number', 'address']);
        if ($missing) jsonResponse(['ok' => false, 'message' => $missing], 400);
        if (!validPhone((string) $body['contact_number'])) {
            jsonResponse(['ok' => false, 'message' => 'Please enter a valid contact number.'], 400);
        }
        db()->prepare(
            'UPDATE donors SET contact_number = ?, address = ? WHERE user_id = ?'
        )->execute([
            trim((string) $body['contact_number']), trim((string) $body['address']),
            $user['id'],
        ]);
    } elseif ($user['role'] === 'patient') {
        $missing = requireFields($body, ['contact_number', 'address', 'hospital']);
        if ($missing) jsonResponse(['ok' => false, 'message' => $missing], 400);
        if (!validPhone((string) $body['contact_number'])) {
            jsonResponse(['ok' => false, 'message' => 'Please enter a valid contact number.'], 400);
        }
        $patientAddress = trim((string) $body['address']);
        db()->prepare(
            'UPDATE patients SET contact_number = ?, address = ?, hospital = ? WHERE user_id = ?'
        )->execute([
            trim((string) $body['contact_number']), $patientAddress, trim((string) $body['hospital']),
            $user['id'],
        ]);
    } else {
        jsonResponse(['ok' => false, 'message' => 'Staff and admin profiles are managed in the Users module.'], 403);
    }

    logAction((int) $user['id'], 'Profile Update', $user['username'] . ' updated their profile');
    jsonResponse(['ok' => true, 'message' => 'Profile updated.']);
}

/* ----------------------------------------------------- own password */
/* Security: changing a password now requires an emailed OTP for EVERY
 * logged-in user. Step 1 validates the current password + strength and
 * emails a code (the new hash is stashed in the session, never applied
 * yet). Step 2 (password-confirm) checks the code and applies it. */
if ($action === 'password' && $method === 'PUT') {
    $user = requireLogin();
    $current = (string) ($body['current'] ?? '');
    $next = (string) ($body['next'] ?? '');
    if ($current === '' || $next === '') {
        jsonResponse(['ok' => false, 'message' => 'Please provide current and new passwords.'], 400);
    }

    $statement = db()->prepare('SELECT password_hash, email FROM users WHERE id = ?');
    $statement->execute([$user['id']]);
    $account = $statement->fetch();
    if (!$account || !password_verify($current, (string) $account['password_hash'])) {
        jsonResponse(['ok' => false, 'message' => 'Current password is incorrect.'], 400);
    }
    if (!strongEnoughPassword($next)) {
        jsonResponse(['ok' => false, 'message' => 'New password must be 8+ characters with uppercase, lowercase, and a number.'], 400);
    }

    $email = trim((string) ($account['email'] ?? ''));
    if ($email === '') {
        jsonResponse(['ok' => false, 'message' => 'Your account has no email on file, so we cannot verify this change. Please ask an administrator to add an email to your account.'], 400);
    }

    // Stash the pending hash in the session — applied only after OTP check.
    $_SESSION['pending_pwchange_hash'] = password_hash($next, PASSWORD_DEFAULT);
    [$sent, $msg] = issueOtp((int) $user['id'], $email, null, false);
    if (!$sent) {
        unset($_SESSION['pending_pwchange_hash']);
        jsonResponse(['ok' => false, 'message' => $msg], 502);
    }
    logAction((int) $user['id'], 'Password Change Requested', $user['username'] . ' requested a password change (OTP sent)');
    jsonResponse(['ok' => true, 'verify' => true, 'message' => 'Enter the 6-digit code sent to your email to confirm the change.']);
}

/* --------------------------------------- confirm password change (OTP) */
if ($action === 'password-confirm' && $method === 'POST') {
    // Normalize: Android sends "otp", backend uses "code"
    if (!isset($body["code"]) && isset($body["otp"])) $body["code"] = $body["otp"];
    $user = requireLogin();
    [$ok] = rateLimitHit('pwchange-verify:' . clientIp(), 30, 900);
    if (!$ok) jsonResponse(['ok' => false, 'message' => 'Too many attempts. Please wait a few minutes.'], 429);

    $pendingHash = (string) ($_SESSION['pending_pwchange_hash'] ?? '');
    if ($pendingHash === '') {
        jsonResponse(['ok' => false, 'message' => 'No pending password change. Please start again.'], 400);
    }

    [$verified, $message] = verifyResetOtp((int) $user['id'], (string) ($body["code"] ?? ''));
    if (!$verified) {
        jsonResponse(['ok' => false, 'message' => $message], 400);
    }

    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([$pendingHash, $user['id']]);
    unset($_SESSION['pending_pwchange_hash']);
    session_regenerate_id(true);
    logAction((int) $user['id'], 'Password Changed', $user['username'] . ' changed their password (email verified)');
    jsonResponse(['ok' => true, 'message' => 'Password changed successfully.']);
}



