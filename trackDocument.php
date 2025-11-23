<?php
session_start();

header('Content-Type: application/json');

// establish mysql connection
$host = "sql100.infinityfree.com";
$db   = "if0_39606867_bulsu_docu_tracker_v2";
$user = "if0_39606867";
$pass = "JsfJmMbmffxwzYV";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? '');
    $password = trim($_POST["password"] ?? '');
    $fileID   = trim($_POST["fileID"] ?? '');

    if (!$username || !$password || !$fileID) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit;
    }

    // verify office credentials (USE HASH)
    $stmt = $conn->prepare("SELECT password FROM office_accounts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows !== 1) {
        http_response_code(401);
        echo json_encode(["status"=>"error","message"=>"Office account not found."]);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->bind_result($storedHash);
    $stmt->fetch();
    if (!password_verify($password, $storedHash)) {
        http_response_code(401);
        echo json_encode(["status"=>"error","message"=>"Incorrect password."]);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // search database for document
    $stmt2 = $conn->prepare("SELECT document_name, referring_to, date_received FROM documents WHERE unique_file_key = ?");
    $stmt2->bind_param("s", $fileID);
    $stmt2->execute();
    $stmt2->store_result();

    if ($stmt2->num_rows === 1) {
        $stmt2->bind_result($docName, $referringTo, $dateReceived);
        $stmt2->fetch();
        echo json_encode([
            "status" => "success",
            "message" => "Document delivered.",
            "data" => [
                "documentName" => $docName,
                "referringTo" => $referringTo,
                "dateReceived" => $dateReceived
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Document not found."]);
    }

    $stmt2->close();
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>
