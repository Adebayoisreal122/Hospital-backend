<?php
require_once __DIR__ . "/helpers.php";
require_once __DIR__ . "/mailer.php";

// Get the action and method
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

// ✅ Define actions that DON'T require admin authentication
$publicActions = ['accept-invite', 'complete-invite'];

// Only require admin authentication for non-public actions
if (!in_array($action, $publicActions)) {
    $info = require_admin($conn);
}

// ============================================
// ADMIN ONLY: Invite Doctor
// ============================================
if ($method == 'POST' && $action === 'invite-doctor') {
    $input = json_decode(file_get_contents("php://input"), true);
    $email = $input['email'] ?? null;

    if (!$email) {
        json_response(false, "Doctor email is required");
    }

    // Generate a secure token
    $token = bin2hex(random_bytes(16));

    // Store the invitation
    $stmt = $conn->prepare("INSERT INTO doctor_invitations (email, token, invited_by) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $email, $token, $info['user_id']);
    
    if ($stmt->execute()) {
        // Send email with link
        $frontendUrl = getenv('FRONTEND_URL') ?: 'http://localhost:4200';
        $inviteLink = $frontendUrl . "/accept-invitation?token=" . urlencode($token);
        
        $subject = "Doctor Invitation - Hospital Management System";
        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #1e40af;'>Doctor Invitation</h2>
                <p>You have been invited to join the Hospital Management System</p>
                <p>Please click the link below to accept and complete your registration:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$inviteLink' style='background-color: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Accept Invitation</a>
                </div>
                <p style='color: #666; font-size: 14px;'>Or copy this link:</p>
                <p style='color: #1e40af; word-break: break-all;'>$inviteLink</p>
                <p style='color: #999; font-size: 12px; margin-top: 30px;'>This link will expire once used.</p>
            </div>
        ";

        send_mail($email, $subject, $body);

        json_response(true, "Invitation sent successfully", ["token" => $token]);
    } else {
        json_response(false, "Error sending invitation: " . $stmt->error);
    }
}

// ============================================
// PUBLIC: Validate Invitation Token
// ============================================
if ($method == 'GET' && $action === 'accept-invite') {
    $token = $_GET['token'] ?? null;
    
    if (!$token) {
        json_response(false, "Token missing");
    }

    $stmt = $conn->prepare("SELECT email FROM doctor_invitations WHERE token = ? AND status = 'pending'");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $invite = $result->fetch_assoc();

    if ($invite) {
        json_response(true, "Token valid", ["email" => $invite['email']]);
    } else {
        json_response(false, "Invalid or expired token");
    }
}

// ============================================
// PUBLIC: Complete Doctor Registration
// ============================================
if ($method == 'POST' && $action === 'complete-invite') {
    $input = json_decode(file_get_contents("php://input"), true);
    $token = $input['token'] ?? null;
    $name = $input['name'] ?? null;
    $specialization = $input['specialization'] ?? null;
    $phone = $input['phone'] ?? null;
    $password = $input['password'] ?? null;

    if (!$token || !$name || !$specialization || !$password) {
        json_response(false, "All fields are required");
    }

    // Check token and get invited_by
    $stmt = $conn->prepare("SELECT email, invited_by FROM doctor_invitations WHERE token = ? AND status = 'pending'");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();
    $invite = $res->fetch_assoc();

    if (!$invite) {
        json_response(false, "Invalid or expired invitation token");
    }

    // Create doctor account
    $email = $invite['email'];
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $createdBy = $invite['invited_by']; // Get from invitation record

    $stmt = $conn->prepare("INSERT INTO doctors (name, specialization, email, phone, password, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $name, $specialization, $email, $phone, $hashed, $createdBy);

    if ($stmt->execute()) {
        // Mark invitation as accepted (using prepared statement to prevent SQL injection)
        $updateStmt = $conn->prepare("UPDATE doctor_invitations SET status = 'accepted' WHERE token = ?");
        $updateStmt->bind_param("s", $token);
        $updateStmt->execute();

        json_response(true, "Doctor account created successfully");
    } else {
        json_response(false, "Error creating doctor: " . $stmt->error);
    }
}

// ============================================
// ADMIN ONLY: Dashboard Overview
// ============================================
if ($method == 'GET' && !$action) {
    $counts = [];
    $r = $conn->query("SELECT COUNT(*) AS c FROM doctors")->fetch_assoc(); 
    $counts['doctors'] = (int)$r['c'];
    $r = $conn->query("SELECT COUNT(*) AS c FROM patients")->fetch_assoc(); 
    $counts['patients'] = (int)$r['c'];
    $r = $conn->query("SELECT COUNT(*) AS c FROM appointments")->fetch_assoc(); 
    $counts['appointments'] = (int)$r['c'];

    $appointments = $conn->query("SELECT a.id, a.appointment_date, a.status, p.name AS patient_name, d.name AS doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        ORDER BY a.appointment_date DESC
        LIMIT 10")->fetch_all(MYSQLI_ASSOC);

    json_response(true, "Admin overview", ["counts" => $counts, "recent_appointments" => $appointments]);
}

json_response(false, "Invalid request");
?>