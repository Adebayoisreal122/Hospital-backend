<?php
require_once __DIR__ . "/helpers.php";

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    json_response(false, "Invalid method, use POST");
}
$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? null;
$password = $input['password'] ?? null;
$user_type = $input['user_type'] ?? null; // 'admin'|'doctor'|'patient' - optional: if not provided, search admin->doctors->patients

if (!$email || !$password) json_response(false, "email & password required");


$stmt = $conn->prepare("SELECT id, password FROM admin WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($res && password_verify($password, $res['password'])) {
    $token = issue_token($conn, 'admin', (int)$res['id']);
    json_response(true, "Login successful", ["token" => $token, "role" => "admin", "userId" => (int)$res['id']]);
}

$stmt = $conn->prepare("SELECT id, password FROM doctors WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($res && $res['password'] && password_verify($password, $res['password'])) {
    $token = issue_token($conn, 'doctor', (int)$res['id']);
    json_response(true, "Login successful", ["token" => $token, "role" => "doctor", "userId" => (int)$res['id']]);
}

$stmt = $conn->prepare("SELECT id,  password FROM patients WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($res && isset($res['password']) && $res['password'] && password_verify($password, $res['password'])) {
    $token = issue_token($conn, 'patient', (int)$res['id']);
    json_response(true, "Login successful", ["token" => $token, "role" => "patient", "userId" => (int)$res['id']]);
}

json_response(false, "Invalid credentials");
?>
