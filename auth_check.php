<?php
session_start();

function require_role($roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], (array)$roles)) {
        echo json_encode(["status"=>false,"message"=>"Unauthorized"]);
        exit;
    }
}
