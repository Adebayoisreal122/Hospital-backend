<?php
include "config.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM admins WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = 'admin';
            echo json_encode(["status"=>true,"message"=>"Admin login successful"]);
        } else {
            echo json_encode(["status"=>false,"message"=>"Invalid password"]);
        }
    } else {
        echo json_encode(["status"=>false,"message"=>"Admin not found"]);
    }
}
