<?php
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $name = $input['name'] ?? '';
    $email = $input['email'] ?? '';
    $phone = $input['phone'] ?? '';
    $date_of_birth = $input['date_of_birth'] ?? null;
    $gender = $input['gender'] ?? '';
    $address = $input['address'] ?? '';
    $password = $input['password'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($gender)) {
        json_response(false, "All required fields must be filled");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(false, "Invalid email format");
    }

    if (strlen($password) < 6) {
        json_response(false, "Password must be at least 6 characters");
    }

    $stmt = $conn->prepare("SELECT id FROM patients WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        json_response(false, "Email already registered");
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO patients (name, email, phone, date_of_birth, gender, address, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $name, $email, $phone, $date_of_birth, $gender, $address, $hashed_password);

    if ($stmt->execute()) {
        json_response(true, "Registration successful", [
            "patient_id" => $conn->insert_id
        ]);
    } else {
        json_response(false, "Registration failed: " . $stmt->error);
    }
} else {
    json_response(false, "Invalid request method");
}
?>