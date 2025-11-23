<?php
session_start();
header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status"=>"error","message"=>"Invalid method"]);
    exit;
}

// Check database connection
if (!$con || $con->connect_error) {
    http_response_code(500);
    echo json_encode(["status"=>"error","message"=>"Database connection failed"]);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(["status"=>"error","message"=>"Username and password required"]);
    exit;
}

$stmt = $con->prepare("SELECT password FROM office_accounts WHERE username = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status"=>"error","message"=>"Query preparation failed"]);
    exit;
}

$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Invalid credentials"]);
    $stmt->close();
    exit;
}

$stmt->bind_result($hash);
$stmt->fetch();

if (!password_verify($password, $hash)) {
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Invalid credentials"]);
    $stmt->close();
    exit;
}

$stmt->close();

$_SESSION['auth_user'] = $username;
echo json_encode(["status"=>"success","message"=>"Login successful","user"=>$username]);
?>