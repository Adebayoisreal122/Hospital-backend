<?php
require_once __DIR__ . "/helpers.php";
require_once __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require admin
$info = require_admin($conn);

// Get doctor email
$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? null;

if (!$email) {
    json_response(false, "Email is required");
}

// Generate a token
$token = bin2hex(random_bytes(16));

// Save invite in database
$stmt = $conn->prepare("INSERT INTO doctor_invitations (email, token, invited_by) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $email, $token, $info['user_id']);
$stmt->execute();

// Create acceptance link using environment variable
$frontendUrl = getenv('FRONTEND_URL') ?: 'http://localhost:4200';
$link = $frontendUrl . "/accept-invitation?token=" . urlencode($token);

// Send email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = getenv('SMTP_USER');
    $mail->Password = getenv('SMTP_PASS');
    $mail->SMTPSecure = 'tls';
    $mail->Port = getenv('SMTP_PORT') ?: 587;

    $mail->setFrom(getenv('SMTP_FROM'), getenv('SMTP_FROM_NAME') ?: 'Hospital System');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Doctor Invitation - Hospital Management System';
    $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #1e40af;'>Doctor Invitation</h2>
            <p>You've been invited to join our Hospital Management System as a doctor.</p>
            <p>Click the button below to accept your invitation and complete your registration:</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='$link' style='background-color: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Accept Invitation</a>
            </div>
            <p style='color: #666; font-size: 14px;'>Or copy and paste this link into your browser:</p>
            <p style='color: #1e40af; word-break: break-all;'>$link</p>
            <p style='color: #999; font-size: 12px; margin-top: 30px;'>This link will expire in 24 hours.</p>
        </div>
    ";

    $mail->send();
    json_response(true, "Invitation sent successfully");
} catch (Exception $e) {
    json_response(false, "Email sending failed: " . $mail->ErrorInfo);
}
?>