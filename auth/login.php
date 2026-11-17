<?php
session_start();

// Include helpers for token management
require_once __DIR__ . '/../includes/helpers.php';

// CORS Headers
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? ''; 

    // Debug logging
    error_log("Login attempt - Email: $email, Role: $role");
    
    if (empty($email) || empty($password) || empty($role)) {
        echo json_encode(["status" => false, "message" => "Email, password, and role are required"]);
        exit();
    }

    // Determine which table to check based on role
    $table = '';
    switch ($role) {
        case 'admin':
            $table = 'admins';
            break;
        case 'doctor':
            $table = 'doctors';
            break;
        case 'patient':
            $table = 'patients';
            break;
        default:
            echo json_encode(["status" => false, "message" => "Invalid role"]);
            exit();
    }

    // Check if user exists in the appropriate table
    $stmt = $conn->prepare("SELECT id, name, email, password FROM $table WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        error_log("User found in $table: " . $row['email']);
        
        if (password_verify($password, $row['password'])) {
            // Generate authentication token
            $token = issue_token($conn, $role, $row['id'], 86400); // 24 hours expiry
            
            // Set session variables
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['role'] = $role;
            
            echo json_encode([
                "status" => true,
                "message" => ucfirst($role) . " login successful",
                "token" => $token,
                "data" => [
                    "id" => $row['id'],
                    "name" => $row['name'],
                    "email" => $row['email'],
                    "role" => $role
                ]
            ]);
        } else {
            error_log("Password verification failed for: " . $email);
            echo json_encode(["status" => false, "message" => "Invalid password"]);
        }
    } else {
        error_log("User not found in $table for email: " . $email);
        echo json_encode(["status" => false, "message" => ucfirst($role) . " not found"]);
    }
    
    $stmt->close();
} else {
    echo json_encode(["status" => false, "message" => "Invalid request method"]);
}

$conn->close();
?>