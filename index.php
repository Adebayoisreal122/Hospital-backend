<?php
// index.php - router
header("Content-Type: application/json");
$route = $_GET['route'] ?? null;

if (!$route) {
    echo json_encode(["status" => true, "message" => "Hospital API - available routes: auth, admin, doctors, patients, appointments, invoices"]);
    exit;
}

$allowed = ['auth','admin','doctors','patients','appointments','invoices'];
if (!in_array($route, $allowed)) {
    http_response_code(404);
    echo json_encode(["status" => false, "message" => "Invalid route"]);
    exit;       
}

include __DIR__ . "/{$route}.php";
