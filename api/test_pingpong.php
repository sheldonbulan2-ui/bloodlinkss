<?php
/**
 * BloodLink Phase 1 - Local PoC Ping-Pong API Bridge
 * ---------------------------------------------------
 * Bridges the Android App with the Web Portal locally.
 */
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/helpers.php';
require_once __DIR__ . '/../php/ratelimit.php';
require_once __DIR__ . '/../php/otp.php';

startAppSession();

try {
    $pdo = db();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database service unavailable. Please try again shortly."]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$dataInput = json_decode($rawInput, true) ?? $_POST;
$action = $_GET['action'] ?? ($dataInput['action'] ?? '');

// 0. AUTHENTICATE USER (For Android App Login against Central MySQL)
if ($action === 'login') {
    // Brute-force flood guard
    [$ipAllowed, $ipWait] = rateLimitHit('pingpong_login:' . clientIp(), 30, 900);
    if (!$ipAllowed) {
        http_response_code(429);
        echo json_encode(["success" => false, "message" => "Too many login attempts. Please wait " . ceil($ipWait / 60) . " minute(s)."]);
        exit;
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;
    $identifier = trim($data['username'] ?? $data['identifier'] ?? '');
    $password = (string) ($data['password'] ?? '');

    if ($identifier === '' || $password === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Please enter username and password."]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, username, email, password_hash, role, status FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Incorrect username or password."]);
        exit;
    }

    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "This account is " . $user['status'] . ". Please contact an administrator."]);
        exit;
    }

    // Retrieve complete display & profile details
    $firstName = $user['username'];
    $lastName = '';
    $profileData = null;
    $profileImage = null;
    try {
        $chk = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'")->fetch();
        if (!$chk) {
            $pdo->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(500) NULL");
        }
        $imgStmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
        $imgStmt->execute([$user['id']]);
        $profileImage = $imgStmt->fetchColumn() ?: null;
    } catch (Throwable $ignored) {}

    if ($user['role'] === 'patient') {
        $p = $pdo->prepare("SELECT id, patient_code, first_name, middle_name, last_name, age, sex, contact_number, address, blood_type_needed, hospital, medical_notes FROM patients WHERE user_id = ?");
        $p->execute([$user['id']]);
        if ($row = $p->fetch(PDO::FETCH_ASSOC)) {
            $firstName = $row['first_name'] ?: $firstName;
            $lastName = $row['last_name'] ?: '';
            $row['code'] = $row['patient_code'];
            $profileData = $row;
        }
    } elseif ($user['role'] === 'donor') {
        $d = $pdo->prepare("SELECT id, donor_code, first_name, middle_name, last_name, age, sex, contact_number, address, blood_type, email FROM donors WHERE user_id = ?");
        $d->execute([$user['id']]);
        if ($row = $d->fetch(PDO::FETCH_ASSOC)) {
            $firstName = $row['first_name'] ?: $firstName;
            $lastName = $row['last_name'] ?: '';
            $row['code'] = $row['donor_code'];
            $profileData = $row;
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Login successful! Welcome back, " . $firstName . ".",
        "user_id" => (int) $user['id'],
        "username" => $user['username'],
        "email" => $user['email'] ?? '',
        "role" => $user['role'],
        "first_name" => $firstName,
        "last_name" => $lastName,
        "profile" => $profileData,
        "profile_image" => $profileImage,
        "status" => $user['status']
    ]);
    exit;
}

// 0.05 GET USER PROFILE (For live sync between Web and App)
if ($action === 'get_profile' || $action === 'profile') {
    $userId = (int) ($_GET['user_id'] ?? 0);
    $username = trim($_GET['username'] ?? '');

    $user = null;
    if ($userId > 0) {
        $stmt = $pdo->prepare("SELECT id, username, email, role, status FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$user && $username !== '') {
        $stmt = $pdo->prepare("SELECT id, username, email, role, status FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$user) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "User not found."]);
        exit;
    }

    $profileData = null;
    $profileImage = null;
    try {
        $userRole = strtolower($user['role'] ?? '');
        if ($userRole === 'patient') {
            try {
                $pImg = $pdo->prepare("SELECT profile_image FROM patients WHERE user_id = ?");
                $pImg->execute([$user['id']]);
                $profileImage = $pImg->fetchColumn();
            } catch (Throwable $e) {}
            if (!$profileImage) {
                try {
                    $uImg = $pdo->prepare("SELECT patient_profile_image FROM users WHERE id = ?");
                    $uImg->execute([$user['id']]);
                    $profileImage = $uImg->fetchColumn();
                } catch (Throwable $e) {}
            }
        } elseif ($userRole === 'donor') {
            try {
                $dImg = $pdo->prepare("SELECT profile_image FROM donors WHERE user_id = ?");
                $dImg->execute([$user['id']]);
                $profileImage = $dImg->fetchColumn();
            } catch (Throwable $e) {}
            if (!$profileImage) {
                try {
                    $uImg = $pdo->prepare("SELECT donor_profile_image FROM users WHERE id = ?");
                    $uImg->execute([$user['id']]);
                    $profileImage = $uImg->fetchColumn();
                } catch (Throwable $e) {}
            }
        }
        if (!$profileImage) {
            try {
                $imgStmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
                $imgStmt->execute([$user['id']]);
                $profileImage = $imgStmt->fetchColumn() ?: null;
            } catch (Throwable $e) {}
        }
    } catch (Throwable $ignored) {}

    if ($user['role'] === 'patient') {
        $p = $pdo->prepare("SELECT id, patient_code, first_name, middle_name, last_name, age, sex, contact_number, address, blood_type_needed, hospital, medical_notes FROM patients WHERE user_id = ?");
        $p->execute([$user['id']]);
        if ($row = $p->fetch(PDO::FETCH_ASSOC)) {
            $row['code'] = $row['patient_code'];
            $profileData = $row;
        }
    } elseif ($user['role'] === 'donor') {
        $d = $pdo->prepare("SELECT id, donor_code, first_name, middle_name, last_name, age, sex, contact_number, address, blood_type, email FROM donors WHERE user_id = ?");
        $d->execute([$user['id']]);
        if ($row = $d->fetch(PDO::FETCH_ASSOC)) {
            $row['code'] = $row['donor_code'];
            $profileData = $row;
        }
    }

    echo json_encode([
        "success" => true,
        "user_id" => (int) $user['id'],
        "username" => $user['username'],
        "email" => $user['email'] ?? '',
        "role" => $user['role'],
        "profile" => $profileData,
        "profile_image" => $profileImage
    ]);
    exit;
}

// 0.05 UPDATE USER PROFILE (From Android App)
if ($action === 'update_profile') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;

    $userId = (int) ($data['user_id'] ?? ($_GET['user_id'] ?? 0));
    $username = trim($data['username'] ?? ($_GET['username'] ?? ''));
    $role = trim(strtolower($data['role'] ?? ($_GET['role'] ?? '')));

    if ($userId <= 0 && $username !== '') {
        $uStmt = $pdo->prepare("SELECT id, role FROM users WHERE username = ? LIMIT 1");
        $uStmt->execute([$username]);
        $u = $uStmt->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            $userId = (int) $u['id'];
            if (!$role) $role = strtolower($u['role']);
        }
    }

    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Valid user_id required."]);
        exit;
    }

    $firstName = trim($data['first_name'] ?? '');
    $middleName = trim($data['middle_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $age = (int) ($data['age'] ?? 0);
    $sex = trim($data['sex'] ?? '');
    $contactNumber = trim($data['contact_number'] ?? '');
    $address = trim($data['address'] ?? '');
    $bloodType = trim($data['blood_type'] ?? '');
    $hospital = trim($data['hospital'] ?? '');
    $medicalNotes = trim($data['medical_notes'] ?? '');

    try {
        if ($role === 'donor') {
            $stmt = $pdo->prepare("UPDATE donors SET first_name = ?, last_name = ?, age = ?, sex = ?, blood_type = ?, contact_number = ?, address = ? WHERE user_id = ?");
            $stmt->execute([$firstName, $lastName, $age, $sex, $bloodType, $contactNumber, $address, $userId]);
        } else if ($role === 'patient') {
            $stmt = $pdo->prepare("UPDATE patients SET first_name = ?, last_name = ?, age = ?, sex = ?, blood_type_needed = ?, blood_type = ?, contact_number = ?, address = ?, hospital = ?, medical_notes = ? WHERE user_id = ?");
            $stmt->execute([$firstName, $lastName, $age, $sex, $bloodType, $bloodType, $contactNumber, $address, $hospital, $medicalNotes, $userId]);
        }

        echo json_encode(["success" => true, "message" => "Profile updated successfully!"]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to update profile: " . $e->getMessage()]);
    }
    exit;
}

// 0.1 REGISTER USER (From Android App)
if ($action === 'register') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;

    $username = trim($data['username'] ?? '');
    $password = (string) ($data['password'] ?? '');
    $role = strtolower(trim($data['role'] ?? 'patient'));
    $email = trim($data['email'] ?? '');
    $firstName = trim($data['first_name'] ?? 'User');
    $middleName = trim($data['middle_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $age = (int) ($data['age'] ?? 25);
    if ($age <= 0) $age = 25;
    
    $sex = trim($data['gender'] ?? $data['sex'] ?? 'Male');
    $sex = (strcasecmp($sex, 'Female') === 0 || strcasecmp($sex, 'F') === 0) ? 'Female' : 'Male';
    
    $contact = trim($data['contact_number'] ?? $data['contact'] ?? $data['phone'] ?? '09170000000');
    $address = trim($data['address'] ?? 'General Location');
    $hospital = trim($data['hospital'] ?? ($address ?: 'General Hospital'));
    $bloodType = trim($data['blood_type'] ?? 'O+');
    $allowedBlood = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    if (!in_array($bloodType, $allowedBlood, true)) {
        $bloodType = 'O+';
    }

    if ($username === '' || $password === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Username and password are required."]);
        exit;
    }

    $confirmPassword = (string) ($data['confirm_password'] ?? '');
    if ($confirmPassword !== '' && $password !== $confirmPassword) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Passwords do not match."]);
        exit;
    }

    if (strlen($password) < 6 || (!preg_match('/[a-zA-Z]/', $password) && !preg_match('/\d/', $password))) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Password must be at least 6 characters (e.g. 0992xxxxxxxx)."]);
        exit;
    }

    // Check if username exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(["success" => false, "message" => "Username is already taken."]);
        exit;
    }

    if ($email !== '') {
        $stmtEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmtEmail->execute([$email]);
        if ($stmtEmail->fetch()) {
            http_response_code(409);
            echo json_encode(["success" => false, "message" => "That email is already registered to another account."]);
            exit;
        }
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username, email, password_hash, role, status, email_verified) VALUES (?, ?, ?, ?, 'active', 0)")
        ->execute([$username, $email !== '' ? $email : null, $hash, $role]);
    $userId = (int) $pdo->lastInsertId();

    if ($role === 'patient') {
        $lastRow = $pdo->query("SELECT patient_code FROM patients WHERE patient_code LIKE 'PAT-%' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $lastNum = 0;
        if ($lastRow && preg_match('/(\d+)$/', (string) $lastRow['patient_code'], $m)) {
            $lastNum = (int) $m[1];
        }
        $pCode = 'PAT-' . str_pad((string) ($lastNum + 1), 5, '0', STR_PAD_LEFT);
        $pdo->prepare("INSERT INTO patients (user_id, patient_code, first_name, middle_name, last_name, age, sex, contact_number, address, blood_type_needed, hospital) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$userId, $pCode, $firstName, $middleName ?: null, $lastName, $age, $sex, $contact, $address, $bloodType, $hospital]);
    } else {
        $lastRow = $pdo->query("SELECT donor_code FROM donors WHERE donor_code LIKE 'DON-%' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $lastNum = 0;
        if ($lastRow && preg_match('/(\d+)$/', (string) $lastRow['donor_code'], $m)) {
            $lastNum = (int) $m[1];
        }
        $dCode = 'DON-' . str_pad((string) ($lastNum + 1), 5, '0', STR_PAD_LEFT);
        $donorEmail = $email !== '' ? $email : ($username . '@bloodlink.local');
        $pdo->prepare("INSERT INTO donors (user_id, donor_code, first_name, middle_name, last_name, age, sex, contact_number, address, blood_type, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$userId, $dCode, $firstName, $middleName ?: null, $lastName, $age, $sex, $contact, $address, $bloodType, $donorEmail]);
    }

    $otpSent = false;
    $otpMsg = '';
    if ($email !== '') {
        [$otpSent, $otpMsg] = issueOtp($userId, $email, $firstName, false);
    }

    echo json_encode([
        "success" => true,
        "message" => $otpSent ? "Account created! A 6-digit OTP code has been sent to " . $email : "Account created successfully!",
        "user_id" => $userId,
        "username" => $username,
        "role" => $role,
        "email" => $email,
        "otp_sent" => $otpSent,
        "otp_code" => (defined('BL_LOCAL_OTP') && BL_LOCAL_OTP ? $otpMsg : null)
    ]);
    exit;
}

// 0.2 REQUEST OTP (Step 1: Request 6-digit code for Forgot Password)
if ($action === 'request_otp') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;
    $identifier = trim($data['identifier'] ?? $data['username'] ?? $data['email'] ?? '');

    if ($identifier === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Please enter your username or email."]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, username, email, role, status FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "No account found matching: " . $identifier]);
        exit;
    }

    $email = trim((string)($user['email'] ?? ''));
    if ($email === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "No email registered for this account."]);
        exit;
    }

    [$sent, $otpMessage] = issueOtp((int)$user['id'], $email, null, false);

    echo json_encode([
        "success" => true,
        "email" => $email, // Full unmasked email!
        "message" => "A 6-digit OTP code has been sent to " . $email,
        "otp_code" => (defined('BL_LOCAL_OTP') && BL_LOCAL_OTP ? $otpMessage : null)
    ]);
    exit;
}

// 0.3 VERIFY OTP (Step 2: Check 6-digit OTP code)
if ($action === 'verify_otp') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;
    $identifier = trim($data['identifier'] ?? $data['username'] ?? $data['email'] ?? '');
    $otp = trim((string)($data['otp'] ?? $data['code'] ?? ''));

    if ($identifier === '' || $otp === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Please enter both username/email and OTP code."]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Account not found."]);
        exit;
    }

    [$verified, $message] = verifyResetOtp((int)$user['id'], $otp);
    if (!$verified) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => $message]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "OTP verified successfully! You may now set your new password."
    ]);
    exit;
}

// 0.4 RESET PASSWORD (Step 3: Update Password)
if ($action === 'reset_password' || $action === 'forgot_password') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;

    $identifier = trim($data['identifier'] ?? $data['username'] ?? $data['email'] ?? '');
    $newPassword = (string) ($data['new_password'] ?? $data['password'] ?? '');

    if ($identifier === '' || $newPassword === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Please enter your username/email and new password."]);
        exit;
    }

    if (strlen($newPassword) < 6) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Password must be at least 6 characters long."]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, username, email, role, status FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "No account found matching: " . $identifier]);
        exit;
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $upd->execute([$hash, $user['id']]);

    echo json_encode([
        "success" => true,
        "message" => "Password reset successfully! You can now log in with your new password.",
        "username" => $user['username']
    ]);
    exit;
}

// 0.5 SUBMIT USER FEEDBACK (From Mobile App Donor/Patient)
if ($action === 'submit_feedback') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;

    $userId = (int) ($data['user_id'] ?? 0);
    $username = trim((string) ($data['username'] ?? ''));
    $role = strtolower(trim((string) ($data['role'] ?? '')));
    $rating = max(1, min(5, (int) ($data['rating'] ?? 5)));
    $category = trim((string) ($data['category'] ?? 'Mobile App Experience'));
    $message = trim((string) ($data['message'] ?? ($data['suggestion'] ?? '')));
    $suggestion = trim((string) ($data['suggestion'] ?? $message));

    if ($userId <= 0 && $username !== '') {
        $uStmt = $pdo->prepare("SELECT id, role FROM users WHERE username = ? LIMIT 1");
        $uStmt->execute([$username]);
        if ($row = $uStmt->fetch(PDO::FETCH_ASSOC)) {
            $userId = (int) $row['id'];
            if ($role === '') $role = $row['role'];
        }
    }

    if ($userId <= 0 || !in_array($role, ['donor', 'patient'], true)) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Only Donors and Patients may submit feedback."]);
        exit;
    }

    if ($message === '' && $suggestion === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Please provide your comments or suggestions."]);
        exit;
    }

    try {
        ensureFeedbacksTableExists();
        $ins = $pdo->prepare("INSERT INTO feedbacks (user_id, role, rating, category, message, suggestion, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'New', NOW())");
        $ins->execute([$userId, $role, $rating, $category, $message, $suggestion]);

        echo json_encode([
            "success" => true,
            "message" => "Thank you! Your feedback (" . $rating . " stars) has been forwarded directly to the Superadmin dashboard."
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to save feedback: " . $e->getMessage()]);
    }
    exit;
}

// 1. APPROVE REQUEST (Protected: Authorized Healthcare Personnel Only)
if ($action === 'approve') {
    $curUser = currentUser();
    $apiSecret = env('API_SECRET_KEY', 'BL_INTERNAL_SECRET_2026');
    $headerKey = (string)($_SERVER['HTTP_X_API_KEY'] ?? '');
    $isKeyAuth = ($headerKey !== '' && hash_equals($apiSecret, $headerKey));
    $isRoleAuth = ($curUser && in_array($curUser['role'], ['staff', 'admin', 'superadmin'], true));

    if (!$isKeyAuth && !$isRoleAuth) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Access denied. Only authorized healthcare staff or administrators may approve blood requests."]);
        exit;
    }
    $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $id = (int) ($data['id'] ?? 0);
    }

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid request ID"]);
        exit;
    }

    $reqStmt = $pdo->prepare("SELECT * FROM blood_requests WHERE id = ?");
    $reqStmt->execute([$id]);
    $req = $reqStmt->fetch(PDO::FETCH_ASSOC);
    if (!$req) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Request not found"]);
        exit;
    }

    $bType = $req['blood_type'];
    $uNeed = (int)$req['units_needed'];
    $locName = trim($req['location'] ?? '');

    // Deduct blood from hospital inventory if not yet deducted
    if (empty($req['inventory_deducted'])) {
        $locId = 0;
        if ($locName !== '') {
            $lStmt = $pdo->prepare("SELECT id FROM blood_locations WHERE location_name = ? OR address LIKE ? LIMIT 1");
            $lStmt->execute([$locName, '%' . $locName . '%']);
            $locId = (int)$lStmt->fetchColumn();
        }
        if ($locId <= 0) {
            $locId = (int)$pdo->query("SELECT id FROM blood_locations WHERE available_status = 'Active' ORDER BY id ASC LIMIT 1")->fetchColumn();
        }

        if ($locId > 0) {
            $batches = $pdo->prepare("SELECT id, blood_units FROM blood_inventory WHERE blood_type = ? AND location_id = ? AND status = 'Available' AND expiration_date >= CURRENT_DATE ORDER BY expiration_date ASC, id ASC");
            $batches->execute([$bType, $locId]);
            $bRows = $batches->fetchAll(PDO::FETCH_ASSOC);

            $rem = $uNeed;
            $upd = $pdo->prepare("UPDATE blood_inventory SET blood_units = ?, status = CASE WHEN ? = 0 THEN 'Used' ELSE status END WHERE id = ?");
            foreach ($bRows as $brow) {
                if ($rem <= 0) break;
                $have = (int)$brow['blood_units'];
                $take = min($have, $rem);
                $left = $have - $take;
                $upd->execute([$left, $left, $brow['id']]);
                $rem -= $take;
            }
        }
        $pdo->prepare("UPDATE blood_requests SET status = 'Approved', inventory_deducted = 1 WHERE id = ?")->execute([$id]);
    } else {
        $pdo->prepare("UPDATE blood_requests SET status = 'Approved' WHERE id = ?")->execute([$id]);
    }

    echo json_encode([
        "success" => true,
        "message" => "Request #$id has been APPROVED and $uNeed unit(s) of $bType deducted from hospital inventory! 🎉",
        "id" => $id,
        "status" => "Approved"
    ]);
    exit;
}

// 1.1 COMPLETE REQUEST (Protected: Authorized Healthcare Personnel Only)
if ($action === 'complete' || $action === 'complete_request') {
    $curUser = currentUser();
    $apiSecret = env('API_SECRET_KEY', 'BL_INTERNAL_SECRET_2026');
    $headerKey = (string)($_SERVER['HTTP_X_API_KEY'] ?? '');
    $isKeyAuth = ($headerKey !== '' && hash_equals($apiSecret, $headerKey));
    $isRoleAuth = ($curUser && in_array($curUser['role'], ['staff', 'admin', 'superadmin'], true));

    if (!$isKeyAuth && !$isRoleAuth) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Access denied. Only authorized healthcare staff or administrators may complete blood requests."]);
        exit;
    }
    $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $id = (int) ($data['id'] ?? 0);
    }

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid request ID"]);
        exit;
    }

    $reqStmt = $pdo->prepare("SELECT * FROM blood_requests WHERE id = ?");
    $reqStmt->execute([$id]);
    $req = $reqStmt->fetch(PDO::FETCH_ASSOC);
    if (!$req) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Request not found"]);
        exit;
    }

    $bType = $req['blood_type'];
    $uNeed = (int)$req['units_needed'];
    $locName = trim($req['location'] ?? '');

    // Deduct blood from hospital inventory if not yet deducted
    if (empty($req['inventory_deducted'])) {
        $locId = 0;
        if ($locName !== '') {
            $lStmt = $pdo->prepare("SELECT id FROM blood_locations WHERE location_name = ? OR address LIKE ? LIMIT 1");
            $lStmt->execute([$locName, '%' . $locName . '%']);
            $locId = (int)$lStmt->fetchColumn();
        }
        if ($locId <= 0) {
            $locId = (int)$pdo->query("SELECT id FROM blood_locations WHERE available_status = 'Active' ORDER BY id ASC LIMIT 1")->fetchColumn();
        }

        if ($locId > 0) {
            $batches = $pdo->prepare("SELECT id, blood_units FROM blood_inventory WHERE blood_type = ? AND location_id = ? AND status = 'Available' AND expiration_date >= CURRENT_DATE ORDER BY expiration_date ASC, id ASC");
            $batches->execute([$bType, $locId]);
            $bRows = $batches->fetchAll(PDO::FETCH_ASSOC);

            $rem = $uNeed;
            $upd = $pdo->prepare("UPDATE blood_inventory SET blood_units = ?, status = CASE WHEN ? = 0 THEN 'Used' ELSE status END WHERE id = ?");
            foreach ($bRows as $brow) {
                if ($rem <= 0) break;
                $have = (int)$brow['blood_units'];
                $take = min($have, $rem);
                $left = $have - $take;
                $upd->execute([$left, $left, $brow['id']]);
                $rem -= $take;
            }
        }
        $pdo->prepare("UPDATE blood_requests SET status = 'Completed', inventory_deducted = 1 WHERE id = ?")->execute([$id]);
    } else {
        $pdo->prepare("UPDATE blood_requests SET status = 'Completed' WHERE id = ?")->execute([$id]);
    }

    echo json_encode([
        "success" => true,
        "message" => "Request #$id has been marked COMPLETED and $uNeed unit(s) of $bType deducted from hospital inventory! 🎉",
        "id" => $id,
        "status" => "Completed"
    ]);
    exit;
}

// 2. CHECK STATUS OF SPECIFIC REQUEST (Polling from Mobile App)
if ($action === 'check_status') {
    $id = (int) ($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id, patient_name, blood_type, units_needed, priority, status, medical_document_url, created_at FROM blood_requests WHERE id = ?");
    $stmt->execute([$id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($req) {
        echo json_encode(["success" => true, "request" => $req]);
    } else {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Request not found"]);
    }
    exit;
}

// 3. GET APPROVED REQUESTS (For Donor Screen in Android App)
if ($action === 'get_approved_requests') {
    $stmt = $pdo->query("SELECT id, patient_name, blood_type, units_needed, priority, reason, location, contact_number, status, medical_document_url, created_at FROM blood_requests WHERE status = 'Approved' ORDER BY id DESC");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "requests" => $requests]);
    exit;
}

// 4. GET PATIENT REQUESTS (Strictly filtered for the logged-in Patient Account)
if ($action === 'get_patient_requests') {
    $userId = (int) ($_GET['user_id'] ?? 0);
    $username = trim($_GET['username'] ?? '');
    $patientName = trim($_GET['patient_name'] ?? '');

    $patientId = null;
    if ($userId > 0) {
        $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
        $stmt->execute([$userId]);
        $patientId = $stmt->fetchColumn();
    }
    if (!$patientId && $username !== '') {
        $stmt = $pdo->prepare("SELECT p.id FROM patients p JOIN users u ON p.user_id = u.id WHERE u.username = ?");
        $stmt->execute([$username]);
        $patientId = $stmt->fetchColumn();
    }

    if ($patientId) {
        $stmt = $pdo->prepare("SELECT id, patient_name, blood_type, units_needed, priority, reason, location, contact_number, status, medical_document_url, created_at FROM blood_requests WHERE patient_id = ? ORDER BY id DESC");
        $stmt->execute([$patientId]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($patientName !== '') {
        $stmt = $pdo->prepare("SELECT id, patient_name, blood_type, units_needed, priority, reason, location, contact_number, status, medical_document_url, created_at FROM blood_requests WHERE patient_name LIKE ? ORDER BY id DESC");
        $stmt->execute(['%' . $patientName . '%']);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Return empty list if user is unidentified - never leak other accounts' requests!
        $requests = [];
    }

    echo json_encode(["success" => true, "requests" => $requests]);
    exit;
}

// 5. DONOR DONATE ACTION (Donor pledges to donate for an approved request)
if ($action === 'donate') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;
    $reqId = (int) ($data['request_id'] ?? $_GET['request_id'] ?? 0);
    $donorName = trim($data['donor_name'] ?? 'Verified Donor');

    $stmt = $pdo->prepare("SELECT * FROM blood_requests WHERE id = ? AND status = 'Approved'");
    $stmt->execute([$reqId]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "This request is not approved or was not found."]);
        exit;
    }

    // Cooldown check for donor
    $dStmt = $pdo->prepare("SELECT id FROM donors WHERE first_name LIKE ? OR last_name LIKE ?");
    $dStmt->execute(['%' . $donorName . '%', '%' . $donorName . '%']);
    $donorId = $dStmt->fetchColumn();

    if ($donorId) {
        $cooldownStmt = $pdo->prepare("SELECT id, donation_date, DATEDIFF(NOW(), donation_date) AS days_passed FROM donations WHERE donor_id = ? AND status = 'Completed' ORDER BY id DESC LIMIT 1");
        $cooldownStmt->execute([$donorId]);
        $lastDon = $cooldownStmt->fetch(PDO::FETCH_ASSOC);
        if ($lastDon && (int)$lastDon['days_passed'] < 56) {
            $rem = 56 - (int)$lastDon['days_passed'];
            http_response_code(429);
            echo json_encode([
                "success" => false,
                "message" => "Donation Cooldown Active: May natitira ka pang $rem araw sa iyong 3-month (90 days) cooldown bago muling mag-donate."
            ]);
            exit;
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "❤️ Maraming Salamat, $donorName!\n\nAng iyong donation pledge para kay " . ($req['patient_name'] ?: 'Patient') . " (" . $req['blood_type'] . " sa " . ($req['location'] ?: 'Hospital') . ") ay naitala na.\n\nNaabisuhan na ang hospital staff. Nagsimula na ang iyong 56-day recovery cooldown."
    ]);
    exit;
}

// 5.1 BOOK DONATION (From Mobile App & Web)
if ($action === 'book_donation') {
    try {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? $_POST;

        $userId = (int) ($data['user_id'] ?? 0);
        $username = trim($data['username'] ?? '');
        $hospital = trim($data['hospital'] ?? $data['hospital_name'] ?? $data['donation_location'] ?? 'Blood Center');
        $bloodType = trim($data['blood_type'] ?? 'O+');
        $date = trim($data['date'] ?? $data['donation_date'] ?? date('Y-m-d'));
        $time = trim($data['time'] ?? $data['donation_time'] ?? date('H:i:s'));
        $notes = trim($data['notes'] ?? '');
        $quantity = max(1, (int) ($data['quantity'] ?? 1));

        // Find donor_id
        $donorId = null;
        if ($userId > 0) {
            $stmt = $pdo->prepare("SELECT id FROM donors WHERE user_id = ?");
            $stmt->execute([$userId]);
            $donorId = $stmt->fetchColumn();
        }
        if (!$donorId && $username !== '') {
            $stmt = $pdo->prepare("SELECT d.id FROM donors d JOIN users u ON d.user_id = u.id WHERE u.username = ?");
            $stmt->execute([$username]);
            $donorId = $stmt->fetchColumn();
        }
        if (!$donorId && $userId > 0) {
            $dCode = 'DON-' . strtoupper(substr(md5(uniqid()), 0, 8));
            $pdo->prepare("INSERT INTO donors (user_id, donor_code, first_name, last_name, blood_type, contact_number, address, email) VALUES (?, ?, ?, '', ?, '09170000000', ?, ?)")
                ->execute([$userId, $dCode, $username ?: 'Donor', $bloodType, $hospital, ($username ?: 'donor') . '@bloodlink.local']);
            $donorId = (int) $pdo->lastInsertId();
        }

        // Cooldown check (56 days) - strictly for COMPLETED donations only!
        $lastDonation = null;
        if ($donorId) {
            $cooldownStmt = $pdo->prepare("SELECT id, donation_date, created_at, reference_code
                FROM donations WHERE donor_id = ? AND status = 'Completed' ORDER BY donation_date DESC, id DESC LIMIT 1");
            $cooldownStmt->execute([$donorId]);
            $lastDonation = $cooldownStmt->fetch(PDO::FETCH_ASSOC);
        }

        $COOLDOWN_DAYS = 90; // 3 months donation interval
        if ($lastDonation) {
            $donationTs = !empty($lastDonation['donation_date']) ? strtotime($lastDonation['donation_date']) : time();
            $createdTs = !empty($lastDonation['created_at']) ? strtotime($lastDonation['created_at']) : $donationTs;
            $nowTs = time();
            $effectiveDate = min($donationTs, $nowTs);
            $daysPassed = max(0, (int) floor(($nowTs - $effectiveDate) / 86400));
            $secPassed = max(0, $nowTs - $createdTs);
            if ($daysPassed < $COOLDOWN_DAYS) {
                $daysRemaining = $COOLDOWN_DAYS - $daysPassed;
                $nextDate = date('F j, Y', strtotime("+{$COOLDOWN_DAYS} days", $effectiveDate));
                $secRemaining = max(0, $daysRemaining * 86400);

                http_response_code(429);
                echo json_encode([
                    "success" => false,
                    "in_cooldown" => true,
                    "days_remaining" => $daysRemaining,
                    "seconds_remaining" => $secRemaining,
                    "next_eligible_date" => $nextDate,
                    "message" => "Donation Cooldown Active: Para sa iyong kalusugan at pagbawi ng dugo, kailangang maghintay ng 3 buwan (90 araw). May natitira ka pang {$daysRemaining} araw (hanggang {$nextDate}) bago muling mag-donate."
                ]);
                exit;
            }
        }

        $refCode = 'BD-' . date('Y') . '-' . strtoupper(substr(md5((string)random_int(1, 10000)), 0, 4));

        $insert = $pdo->prepare(
            "INSERT INTO donations (donor_id, blood_type, donation_date, donation_time, blood_units, donation_location, notes, reference_code, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')"
        );
        $insert->execute([$donorId, $bloodType, $date, $time, $quantity, $hospital, $notes, $refCode]);

        echo json_encode([
            "success" => true,
            "message" => "❤️ Maraming Salamat sa Iyong Kabayanihan!\n\nAng iyong donation schedule sa $hospital para sa $date ($time) ay matagumpay na naisumite.\n\nReference Code: $refCode\n\nKatayuan: Pending (Hihintayin ang beripikasyon at completion ng ospital bago magsimula ang 3-month (90 days) recovery countdown).",
            "reference_code" => $refCode,
            "next_eligible_date" => null,
            "cooldown_days" => 0,
            "cooldown_seconds" => 0
        ]);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Donation booking failed: " . $e->getMessage()
        ]);
        exit;
    }
}

// 5.2 DONOR COOLDOWN STATUS (Only COMPLETED donations start the countdown!)
if ($action === 'donor_cooldown' || $action === 'donor_status') {
    $userId = (int) ($_GET['user_id'] ?? 0);
    $username = trim($_GET['username'] ?? '');

    $donorId = null;
    if ($userId > 0) {
        $stmt = $pdo->prepare("SELECT id FROM donors WHERE user_id = ?");
        $stmt->execute([$userId]);
        $donorId = $stmt->fetchColumn();
    }
    if (!$donorId && $username !== '') {
        $stmt = $pdo->prepare("SELECT d.id FROM donors d JOIN users u ON d.user_id = u.id WHERE u.username = ?");
        $stmt->execute([$username]);
        $donorId = $stmt->fetchColumn();
    }

    $COOLDOWN_DAYS = 90; // 3 months interval
    $inCooldown = false;
    $cooldownSec = 0;
    $daysRemaining = 0;
    $nextDate = '';

    if ($donorId) {
        $stmt = $pdo->prepare("SELECT id, donation_date, created_at, reference_code
            FROM donations WHERE donor_id = ? AND status = 'Completed' ORDER BY donation_date DESC, id DESC LIMIT 1");
        $stmt->execute([$donorId]);
        $lastDonation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($lastDonation) {
            $donationTs = !empty($lastDonation['donation_date']) ? strtotime($lastDonation['donation_date']) : time();
            $createdTs = !empty($lastDonation['created_at']) ? strtotime($lastDonation['created_at']) : $donationTs;
            $nowTs = time();
            $effectiveDate = min($donationTs, $nowTs);
            $daysPassed = max(0, (int) floor(($nowTs - $effectiveDate) / 86400));
            $secPassed = max(0, $nowTs - $createdTs);
            if ($daysPassed < $COOLDOWN_DAYS) {
                $inCooldown = true;
                $daysRemaining = $COOLDOWN_DAYS - $daysPassed;
                $nextDate = date('F j, Y', strtotime("+{$COOLDOWN_DAYS} days", $effectiveDate));
                $secRem = ($COOLDOWN_DAYS * 86400) - $secPassed;
                $cooldownSec = max(0, $secRem);
            }
        }
    }

    echo json_encode([
        "success" => true,
        "in_cooldown" => $inCooldown,
        "cooldown_seconds" => $cooldownSec,
        "days_remaining" => $daysRemaining,
        "next_eligible_date" => $nextDate
    ]);
    exit;
}

// 5.3 GET DONOR DONATIONS (Real-time sync for Mobile APK & Web)
if ($action === 'get_donor_donations' || $action === 'get_donations') {
    $userId = (int) ($_GET['user_id'] ?? 0);
    $username = trim($_GET['username'] ?? '');
    $donorId = (int) ($_GET['donor_id'] ?? 0);

    if (!$donorId && $userId > 0) {
        $stmt = $pdo->prepare("SELECT id FROM donors WHERE user_id = ?");
        $stmt->execute([$userId]);
        $donorId = (int) $stmt->fetchColumn();
    }
    if (!$donorId && $username !== '') {
        $stmt = $pdo->prepare("SELECT d.id FROM donors d JOIN users u ON d.user_id = u.id WHERE u.username = ?");
        $stmt->execute([$username]);
        $donorId = (int) $stmt->fetchColumn();
    }

    if ($donorId > 0) {
        $stmt = $pdo->prepare(
            "SELECT d.id, d.reference_code, d.blood_type, d.donation_date, d.donation_time,
                    d.blood_units, d.donation_location, d.status, d.created_at,
                    CONCAT(COALESCE(don.first_name, ''), ' ', COALESCE(don.last_name, '')) AS donor_name,
                    don.donor_code
             FROM donations d
             JOIN donors don ON d.donor_id = don.id
             WHERE d.donor_id = ?
             ORDER BY d.donation_date DESC, d.id DESC"
        );
        $stmt->execute([$donorId]);
    } else if ($userId > 0) {
        $stmt = $pdo->prepare(
            "SELECT d.id, d.reference_code, d.blood_type, d.donation_date, d.donation_time,
                    d.blood_units, d.donation_location, d.status, d.created_at,
                    CONCAT(COALESCE(don.first_name, ''), ' ', COALESCE(don.last_name, '')) AS donor_name,
                    don.donor_code
             FROM donations d
             LEFT JOIN donors don ON d.donor_id = don.id
             WHERE don.user_id = ?
             ORDER BY d.donation_date DESC, d.id DESC"
        );
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT d.id, d.reference_code, d.blood_type, d.donation_date, d.donation_time,
                    d.blood_units, d.donation_location, d.status, d.created_at,
                    CONCAT(COALESCE(don.first_name, ''), ' ', COALESCE(don.last_name, '')) AS donor_name,
                    don.donor_code
             FROM donations d
             LEFT JOIN donors don ON d.donor_id = don.id
             ORDER BY d.donation_date DESC, d.id DESC LIMIT 50"
        );
        $stmt->execute();
    }

    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        "success" => true,
        "donations" => $donations ?: []
    ]);
    exit;
}

// 5.4 COMPLETE DONATION (From Mobile Staff or API)
if ($action === 'complete_donation') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;
    $donationId = (int) ($data['donation_id'] ?? $_GET['donation_id'] ?? 0);

    if ($donationId <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Donation ID is required."]);
        exit;
    }

    try {
        $pdo->exec("ALTER TABLE donations MODIFY COLUMN status ENUM('Pending','Verified','Rejected','Approved','Completed','Scheduled','Cancelled') NOT NULL DEFAULT 'Pending'");
    } catch (Throwable $ignored) {}

    $upd = $pdo->prepare("UPDATE donations SET status='Completed' WHERE id = ?");
    $upd->execute([$donationId]);

    echo json_encode(["success" => true, "message" => "Donation marked as completed successfully!"]);
    exit;
}

// 6B. GET DOH BLOOD FEE SCHEDULE (Fee Transparency for Android App)
if ($action === 'fees' || $action === 'get_fees' || $action === 'get_fee_schedule') {
    $stmt = $pdo->query("SELECT * FROM blood_fee_schedule WHERE is_active = 1 ORDER BY total_fee DESC");
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "fees" => $fees]);
    exit;
}

// 6C. DONOR PRE-SCREENING (Eligibility Check for Android App)
if ($action === 'screen_donor' || $action === 'donor_screening') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;
    $donorId = (int)($data['donor_id'] ?? 0);
    $userId = (int)($data['user_id'] ?? 0);
    $weight = (float)($data['weight_kg'] ?? 0);
    $compType = trim((string)($data['component_type'] ?? 'Whole Blood'));
    $minWeight = ($compType === 'Whole Blood' ? 50.0 : 45.0);
    $sleep = (int)($data['sleep_hours'] ?? 0);
    $alcohol = !empty($data['alcohol_24h']);
    $tattoos = !empty($data['tattoo_piercing_12m']);
    $meds = !empty($data['medications_7d']);
    $fever = !empty($data['fever_cough_today']);
    $pregnant = !empty($data['pregnancy_nursing']);

    $reasons = [];
    if ($weight < $minWeight) $reasons[] = "Minimum weight is {$minWeight} kg.";
    if ($sleep < 6) $reasons[] = "Minimum 6 hours sleep required.";
    if ($alcohol) $reasons[] = "Zero alcohol intake in last 24 hours required.";
    if ($tattoos) $reasons[] = "Tattoos/piercings require 12 months deferral.";
    if ($meds) $reasons[] = "Antibiotics/medications require medical clearance.";
    if ($fever) $reasons[] = "Fever/flu symptoms require 14 days deferral.";
    if ($pregnant) $reasons[] = "Pregnant/lactating is temporarily deferred.";

    $passed = empty($reasons);
    $deferralReason = $passed ? null : implode('; ', $reasons);
    $deferralUntil = $fever ? date('Y-m-d', strtotime('+14 days')) : ($tattoos ? date('Y-m-d', strtotime('+12 months')) : null);

    if ($donorId === 0 && $userId > 0) {
        $dStmt = $pdo->prepare("SELECT id FROM donors WHERE user_id = ? LIMIT 1");
        $dStmt->execute([$userId]);
        $donorId = (int)$dStmt->fetchColumn();
    }

    if ($userId > 0) {
        $scStmt = $pdo->prepare("INSERT INTO donor_screenings (donor_id, user_id, component_type, weight_kg, passed_screening, deferral_reason, deferral_until, screening_answers) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $scStmt->execute([$donorId > 0 ? $donorId : null, $userId, $compType, $weight, $passed ? 1 : 0, $deferralReason, $deferralUntil, json_encode($data)]);
    }

    echo json_encode([
        "success" => true,
        "eligible" => $passed,
        "reasons" => $reasons,
        "message" => $passed ? "Eligible to donate $compType!" : ("Deferred: " . implode(' ', $reasons)),
        "deferral_until" => $deferralUntil
    ]);
    exit;
}

// 7. GET LIVE BLOOD LOCATIONS & MAP (Synchronized 100% with MySQL for Patient & Donor)
if ($action === 'locations' || $action === 'get_locations' || $action === 'blood_map') {
    $selectedType = trim((string) ($_GET['blood_type'] ?? 'ALL'));
    if ($selectedType === '') $selectedType = 'ALL';

    // Query active hospital locations from MySQL
    $stmt = $pdo->query("SELECT id, location_name, address, latitude, longitude, contact_number, available_status FROM blood_locations WHERE available_status = 'Active' ORDER BY location_name ASC");
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    $allTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    foreach ($locations as $loc) {
        $locId = (int) $loc['id'];
        
        // Sum available unexpired units per blood type
        $invStmt = $pdo->prepare("SELECT blood_type, SUM(blood_units) AS units FROM blood_inventory WHERE location_id = ? AND status = 'Available' AND expiration_date >= CURRENT_DATE GROUP BY blood_type");
        $invStmt->execute([$locId]);
        $stocks = $invStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $stockMap = [];
        $totalUnits = 0;
        foreach ($allTypes as $t) {
            $u = (int) ($stocks[$t] ?? 0);
            $stockMap[$t] = $u;
            $totalUnits += $u;
        }

        $results[] = [
            'id' => $locId,
            'name' => $loc['location_name'],
            'address' => $loc['address'],
            'latitude' => (float) $loc['latitude'],
            'longitude' => (float) $loc['longitude'],
            'contact' => $loc['contact_number'],
            'status' => $loc['available_status'],
            'total_units' => $totalUnits,
            'stocks' => $stockMap
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($results),
        'locations' => $results
    ]);
    exit;
}

// 9. CLEAR / RESET TEST DATA (Permanently Disabled for Production Safety)
if ($action === 'clear_test_data' || $action === 'reset_records') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Endpoint disabled for security and data integrity."]);
    exit;
}

// 6. GET ALL REQUESTS (For the Web PoC Live Monitor)
if ($method === 'GET' && ($action === '' || $action === 'get_requests' || $action === 'requests')) {
    $stmt = $pdo->query("SELECT id, patient_name, blood_type, units_needed, priority, reason, location, contact_number, status, medical_document_url, created_at FROM blood_requests ORDER BY id DESC LIMIT 30");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "requests" => $requests]);
    exit;
}

// 4. SUBMIT REQUEST (From Android App)
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;

    $userId = (int) ($data['user_id'] ?? 0);
    $username = trim($data['username'] ?? '');
    $patientName = trim($data['patient_name'] ?? 'Juan Dela Cruz (App Test)');
    $bloodType = trim($data['blood_type'] ?? 'O+');
    $unitsNeeded = max(1, (int) ($data['units_needed'] ?? 1));
    $priority = in_array($data['priority'] ?? '', ['Normal', 'Urgent', 'Critical']) ? $data['priority'] : 'Urgent';
    $reason = trim($data['reason'] ?? 'Emergency Transfusion - Sent from Android App');
    $location = trim($data['location'] ?? $data['hospital'] ?? 'San Mariano District Hospital');
    $contact = trim($data['contact_number'] ?? '09171234567');
    $medicalDocumentUrl = trim($data['medical_document_url'] ?? '');

    // Map to specific patient_id
    $patientId = null;
    if ($userId > 0) {
        $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
        $stmt->execute([$userId]);
        $patientId = $stmt->fetchColumn();
    }
    if (!$patientId && $username !== '') {
        $stmt = $pdo->prepare("SELECT p.id FROM patients p JOIN users u ON p.user_id = u.id WHERE u.username = ?");
        $stmt->execute([$username]);
        $patientId = $stmt->fetchColumn();
    }
    if (!$patientId) {
        if ($userId > 0) {
            $lastRow = $pdo->query("SELECT patient_code FROM patients WHERE patient_code LIKE 'PAT-%' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            $lastNum = 0;
            if ($lastRow && preg_match('/(\d+)$/', (string) $lastRow['patient_code'], $m)) {
                $lastNum = (int) $m[1];
            }
            $pCode = 'PAT-' . str_pad((string) ($lastNum + 1), 5, '0', STR_PAD_LEFT);
            $pdo->prepare("INSERT INTO patients (user_id, patient_code, first_name, last_name, contact_number, hospital, blood_type_needed) VALUES (?, ?, ?, '', ?, ?, ?)")
                ->execute([$userId, $pCode, $patientName, $contact, $location, $bloodType]);
            $patientId = (int) $pdo->lastInsertId();
        } else {
            $patientId = $pdo->query("SELECT id FROM patients LIMIT 1")->fetchColumn() ?: 1;
        }
    }

    // Anti-spam Cooldown & Daily Limit Checks
    if ($patientId) {
        $todayStmt = $pdo->prepare("SELECT COUNT(*) FROM blood_requests WHERE patient_id = ? AND DATE(created_at) = CURRENT_DATE");
        $todayStmt->execute([$patientId]);
        $dailyCount = (int) $todayStmt->fetchColumn();
        if ($dailyCount >= 3) {
            http_response_code(429);
            echo json_encode([
                "success" => false,
                "error" => "Daily limit reached. Naabot mo na ang limit na 3 blood requests kada araw para sa pasyenteng ito. Subukan muli bukas.",
                "daily_limit_reached" => true
            ]);
            exit;
        }

        $recentStmt = $pdo->prepare("SELECT created_at FROM blood_requests WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1");
        $recentStmt->execute([$patientId]);
        $recentRow = $recentStmt->fetch(PDO::FETCH_ASSOC);
        $secDiff = ($recentRow && !empty($recentRow['created_at'])) ? (time() - strtotime($recentRow['created_at'])) : null;
        if ($secDiff !== null && $secDiff >= 0 && $secDiff < 30) {
            $remaining = 30 - (int)$secDiff;
            http_response_code(429);
            echo json_encode([
                "success" => false,
                "error" => "Bawal ang sabay-sabay na request. Maghintay ng {$remaining} segundo bago mag-submit muli.",
                "cooldown_remaining" => $remaining
            ]);
            exit;
        }
    }

    $componentType = !empty($data['component_type']) ? trim((string)$data['component_type']) : 'Whole Blood';
    $urgencyLevel = !empty($data['urgency_level']) ? trim((string)$data['urgency_level']) : ($priority === 'Critical' ? 'Emergency' : ($priority === 'Urgent' ? 'Urgent' : 'Routine'));
    $hospDept = trim((string)($data['hospital_department'] ?? ''));
    $facilityAddr = trim((string)($data['facility_address'] ?? ''));
    $facilityContact = trim((string)($data['facility_contact'] ?? ''));
    $requestRefNo = trim((string)($data['request_ref_no'] ?? ''));
    if ($requestRefNo === '') {
        $requestRefNo = 'REQ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
    $patientDob = !empty($data['patient_dob']) ? date('Y-m-d', strtotime((string)$data['patient_dob'])) : null;
    $patientAge = !empty($data['patient_age']) && is_numeric($data['patient_age']) ? (int)$data['patient_age'] : null;
    $patientMrn = trim((string)($data['patient_mrn'] ?? ''));
    $patientSex = in_array((string)($data['patient_sex'] ?? ''), ['Male', 'Female'], true) ? (string)$data['patient_sex'] : null;
    $wardRoom = trim((string)($data['ward_room'] ?? ''));
    $clinicalIndication = trim((string)($data['clinical_indication'] ?? $reason));
    $specialRequirements = trim((string)($data['special_requirements'] ?? ''));
    $requesterName = trim((string)($data['requester_name'] ?? $patientName));
    $requesterRelationship = trim((string)($data['requester_relationship'] ?? 'Self / Mobile Request'));
    $requesterContact = trim((string)($data['requester_contact'] ?? $contact));
    $requesterEmail = trim((string)($data['requester_email'] ?? ''));
    $physicianName = trim((string)($data['physician_name'] ?? ''));
    $physicianLicenseNo = trim((string)($data['physician_license_no'] ?? ''));
    $physicianSignature = trim((string)($data['physician_signature'] ?? ''));
    $physicianSignedAt = (!empty($physicianSignature) || !empty($physicianName)) ? date('Y-m-d H:i:s') : null;

    $stmt = $pdo->prepare("INSERT INTO blood_requests (
        patient_id, patient_name, blood_type, component_type, units_needed, priority, reason, location, contact_number,
        status, medical_document_url, request_ref_no, hospital_department, facility_address, facility_contact,
        patient_dob, patient_age, patient_mrn, patient_sex, ward_room, clinical_indication,
        urgency_level, special_requirements, requester_name, requester_relationship, requester_contact, requester_email,
        physician_name, physician_license_no, physician_signature, physician_signed_at,
        document_status, final_status
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        'Pending', ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        'Pending', 'Pending'
    )");
    $stmt->execute([
        $patientId,
        $patientName,
        $bloodType,
        $componentType,
        $unitsNeeded,
        $priority,
        $reason,
        $location,
        $contact,
        $medicalDocumentUrl !== '' ? $medicalDocumentUrl : null,
        $requestRefNo,
        $hospDept,
        $facilityAddr,
        $facilityContact,
        $patientDob,
        $patientAge,
        $patientMrn,
        $patientSex,
        $wardRoom,
        $clinicalIndication,
        $urgencyLevel,
        $specialRequirements,
        $requesterName,
        $requesterRelationship,
        $requesterContact,
        $requesterEmail,
        $physicianName,
        $physicianLicenseNo,
        $physicianSignature,
        $physicianSignedAt
    ]);

    $insertedId = (int) $pdo->lastInsertId();

    $blankFormUrl = null;
    try {
        $stmtF = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'blank_requisition_form_url' LIMIT 1");
        $stmtF->execute();
        $blankFormUrl = $stmtF->fetchColumn();
    } catch (Throwable $e) {}
    if (!$blankFormUrl) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'bloodlinkss.onrender.com';
        $isSubdir = strpos($_SERVER['REQUEST_URI'] ?? '', 'webandapp_combine') !== false;
        $basePath = $isSubdir ? '/webandapp_combine/bloodlink_web/public/' : '/';
        $blankFormUrl = $protocol . '://' . $host . $basePath . 'official_blood_request_form.php';
    }

    echo json_encode([
        "success" => true,
        "message" => "Blood request #$insertedId successfully received from Mobile App!",
        "request_id" => $insertedId,
        "patient_name" => $patientName,
        "blood_type" => $bloodType,
        "blank_requisition_form_url" => $blankFormUrl,
        "status" => "Pending"
    ]);
    exit;
}


// 5.4 GET BLANK BLOOD REQUISITION FORM (Official printable hospital template)
if ($action === 'get_blank_form' || $action === 'blank_form') {
    $formUrl = null;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'blank_requisition_form_url' LIMIT 1");
        $stmt->execute();
        $savedUrl = $stmt->fetchColumn();
        if ($savedUrl && filter_var($savedUrl, FILTER_VALIDATE_URL)) {
            $formUrl = $savedUrl;
        }
    } catch (Throwable $e) {}

    if (!$formUrl) {
        $glob = glob(__DIR__ . '/../public/uploads/blank_blood_requisition_form*');
        if (!empty($glob)) {
            $fn = basename($glob[0]);
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'bloodlinkss.onrender.com';
            $isSubdir = strpos($_SERVER['REQUEST_URI'] ?? '', 'webandapp_combine') !== false;
            $basePath = $isSubdir ? '/webandapp_combine/bloodlink_web/public/uploads/' : '/public/uploads/';
            $formUrl = $protocol . '://' . $host . $basePath . $fn;
        }
    }

    if (!$formUrl) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'bloodlinkss.onrender.com';
        $isSubdir = strpos($_SERVER['REQUEST_URI'] ?? '', 'webandapp_combine') !== false;
        $basePath = $isSubdir ? '/webandapp_combine/bloodlink_web/public/' : '/';
        $formUrl = $protocol . '://' . $host . $basePath . 'official_blood_request_form.php';
    }

    echo json_encode([
        "success" => true,
        "form_url" => $formUrl,
        "message" => "Official printable blank requisition form URL retrieved."
    ]);
    exit;
}

// 5.5 UPLOAD BLANK REQUISITION FORM (For Hospital Staff & Admin)
if ($action === 'upload_blank_form') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (empty($_FILES['form_file']) && empty($_FILES['file']))) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "No form file uploaded."]);
        exit;
    }

    $file = $_FILES['form_file'] ?? $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Upload failed with error code: " . $file['error']]);
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid format ($ext). Allowed: PDF, JPG, PNG, DOCX."]);
        exit;
    }

    $targetDir = realpath(__DIR__ . '/../public/uploads') ?: (__DIR__ . '/../public/uploads');
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    $saveName = 'blank_blood_requisition_form_' . date('Ymd_His') . '.' . $ext;
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $saveName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Could not save form file on server."]);
        exit;
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isSubdir = strpos($_SERVER['REQUEST_URI'] ?? '', 'webandapp_combine') !== false;
    $basePath = $isSubdir ? '/webandapp_combine/bloodlink_web/public/uploads/' : '/public/uploads/';
    $formUrl = $protocol . '://' . $host . $basePath . $saveName;

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (setting_key VARCHAR(80) PRIMARY KEY, setting_value TEXT)");
        $upsert = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('blank_requisition_form_url', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $upsert->execute([$formUrl, $formUrl]);
    } catch (Throwable $e) {}

    echo json_encode([
        "success" => true,
        "message" => "Official Blank Blood Request Form uploaded successfully!",
        "form_url" => $formUrl,
        "file_name" => $saveName
    ]);
    exit;
}
