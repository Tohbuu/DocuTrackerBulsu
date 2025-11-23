<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status"=>"error","message"=>"Invalid method"]);
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