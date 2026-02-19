<?php
// admin_auth.php
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(["status" => false, "message" => "Unauthorized: Admin login required"]);
    exit;
}

require_once "config.php"; // DB connection

// get the admin info for reference
$stmt = $conn->prepare("SELECT id, name, email, role FROM admins WHERE id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();

if (!$admin) {
    session_destroy();
    http_response_code(401);
    echo json_encode(["status" => false, "message" => "Invalid admin session"]);
    exit;
}

// now $admin is available for all files
