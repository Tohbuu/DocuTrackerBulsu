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
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $docName             = trim($_POST["docName"] ?? '');
    $referringTo         = trim($_POST["referringTo"] ?? '');
    $docType             = trim($_POST["docType"] ?? '');
    $sourceUsername      = trim($_POST["sourceUsername"] ?? '');
    $sourcePasswordRaw   = trim($_POST["sourcePassword"] ?? '');
    $receiverUsername    = trim($_POST["receiverUsername"] ?? '');
    $receiverPasswordRaw = trim($_POST["receiverPassword"] ?? '');
    $docTag              = trim($_POST["docTag"] ?? ''); // Get the generated tag
    
    // mysql query for username
    $stmt1 = $conn->prepare("SELECT password FROM office_accounts WHERE username = ?");
    $stmt1->bind_param("s", $sourceUsername);
    $stmt1->execute();
    $stmt1->store_result();

    if ($stmt1->num_rows !== 1) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Source office account not found."]);
        $stmt1->close();
        $conn->close();
        exit;
    }
    //add hashing to avoid display on screen
    $stmt1->bind_result($sourceHashed);
    $stmt1->fetch();
    if (!password_verify($sourcePasswordRaw, $sourceHashed)) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Incorrect source password."]);
        $stmt1->close();
        $conn->close();
        exit;
    }
    $stmt1->close();

    // process receiving office's data
    $stmt2 = $conn->prepare("SELECT password FROM office_accounts WHERE username = ?");
    $stmt2->bind_param("s", $receiverUsername);
    $stmt2->execute();
    $stmt2->store_result();

    if ($stmt2->num_rows !== 1) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Receiver office account not found."]);
        $stmt2->close();
        $conn->close();
        exit;
    }

    $stmt2->bind_result($receiverHashed);
    $stmt2->fetch();
    if (!password_verify($receiverPasswordRaw, $receiverHashed)) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Incorrect receiver password."]);
        $stmt2->close();
        $conn->close();
        exit;
    }
    $stmt2->close();

    // Use provided docTag or generate if missing
    if (empty($docTag)) {
        function generateUniqueFileId($conn, $length = 8) {
            do {
                $fileId = strtoupper(substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $length)), 0, $length));
                $check = $conn->prepare("SELECT 1 FROM documents WHERE unique_file_key = ?");
                $check->bind_param("s", $fileId);
                $check->execute();
                $check->store_result();
                $exists = $check->num_rows > 0;
                $check->close();
            } while ($exists);
            return $fileId;
        }
        $fileId = generateUniqueFileId($conn);
    } else {
        // Use the provided tag (already generated on frontend)
        $fileId = $docTag;
        
        // Check if tag already exists
        $checkStmt = $conn->prepare("SELECT 1 FROM documents WHERE unique_file_key = ?");
        $checkStmt->bind_param("s", $fileId);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            http_response_code(409);
            echo json_encode(["status" => "error", "message" => "Document tag already exists. Please generate a new one."]);
            $checkStmt->close();
            $conn->close();
            exit;
        }
        $checkStmt->close();
    }

    // Insert document into table
    $stmt3 = $conn->prepare("
        INSERT INTO documents 
        (unique_file_key, document_name, referring_to, document_type, source_username, receiver_username, date_received)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    if (!$stmt3) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Failed to prepare insert statement."]);
        exit;
    }

    $stmt3->bind_param(
        "ssssss",
        $fileId,
        $docName,
        $referringTo,
        $docType,
        $sourceUsername,
        $receiverUsername
    );

    if ($stmt3->execute()) {
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "success",
            "message" => "Document recorded successfully. File ID: ".$fileId,
            "fileId" => $fileId
        ]);
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Failed to insert document."]);
    }

    $stmt3->close();
    $conn->close();
} else {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>
