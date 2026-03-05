<?php

require_once __DIR__ . '/config.php';

header("Content-Type: application/json");

// CORS Configuration 
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:4200';
$allowedOrigins = [
    'http://localhost:4200',
    'http://localhost:4201', 
];

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: http://localhost:4200");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function json_response($status, $message, $data = null) {
    echo json_encode(["status" => $status, "message" => $message, "data" => $data]);
    exit;
}

function issue_token($conn, $user_type, $user_id, $ttl_seconds = 86400) {
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + $ttl_seconds);
    $stmt = $conn->prepare("INSERT INTO auth_tokens (user_type, user_id, token, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $user_type, $user_id, $token, $expires_at);
    $stmt->execute();
    $stmt->close();
    return $token;
}

function validate_token($conn, $token) {
    if (!$token) return null;
    $stmt = $conn->prepare("SELECT user_type, user_id, expires_at FROM auth_tokens WHERE token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$res) return null;
    if ($res['expires_at'] && strtotime($res['expires_at']) < time()) {
        $stmt = $conn->prepare("DELETE FROM auth_tokens WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute(); $stmt->close();
        return null;
    }
    return ["user_type" => $res['user_type'], "user_id" => (int)$res['user_id']];
}

function require_admin($conn) {
    $token = null;
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $token = trim(str_replace("Bearer", "", $headers['Authorization']));
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = trim(str_replace("Bearer", "", $_SERVER['HTTP_AUTHORIZATION']));
    }
    $info = validate_token($conn, $token);
    if (!$info || $info['user_type'] !== 'admin') {
        json_response(false, "Unauthorized: admin access required");
    }
    return $info;
}
?>