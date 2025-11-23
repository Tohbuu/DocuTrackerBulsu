<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/connect.php';

if (!$con || $con->connect_error) {
    die("❌ Database connection failed: " . ($con->connect_error ?? 'Unknown error'));
}

echo "<h2>🔧 BulSU Document Tracker - Database Setup</h2>";
echo "<hr>";

// Create office_accounts table
$createAccountsTable = "
CREATE TABLE IF NOT EXISTS `office_accounts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `office_name` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// Create documents table
$createDocumentsTable = "
CREATE TABLE IF NOT EXISTS `documents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `unique_file_key` VARCHAR(50) NOT NULL UNIQUE,
  `document_name` VARCHAR(255) NOT NULL,
  `referring_to` VARCHAR(255) DEFAULT NULL,
  `document_type` ENUM('Internal Communication', 'External Communication') NOT NULL,
  `source_username` VARCHAR(100) NOT NULL,
  `receiver_username` VARCHAR(100) NOT NULL,
  `date_received` DATETIME NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_unique_file_key` (`unique_file_key`),
  INDEX `idx_source_username` (`source_username`),
  INDEX `idx_receiver_username` (`receiver_username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// Execute table creation
echo "<h3>Creating Tables...</h3>";
if ($con->query($createAccountsTable) === TRUE) {
    echo "✅ <strong>office_accounts</strong> table created/verified<br>";
} else {
    echo "❌ Error creating office_accounts: " . $con->error . "<br>";
}

if ($con->query($createDocumentsTable) === TRUE) {
    echo "✅ <strong>documents</strong> table created/verified<br>";
} else {
    echo "❌ Error creating documents: " . $con->error . "<br>";
}

// Default password for all accounts
$defaultPassword = 'password123';
$hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

// Accounts to create
$accounts = [
    ['username' => 'registrar', 'office_name' => 'Registrar Office'],
    ['username' => 'admin', 'office_name' => 'Admin Office'],
    ['username' => 'hr', 'office_name' => 'Human Resources Office'],
    ['username' => 'finance', 'office_name' => 'Finance Office'],
    ['username' => 'president', 'office_name' => 'President Office'],
    ['username' => 'vpaa', 'office_name' => 'VP for Academic Affairs'],
    ['username' => 'osas', 'office_name' => 'Office of Student Affairs and Services']
];

echo "<br><h3>Setting Up Office Accounts...</h3>";
echo "<p>Default Password: <strong style='color: #00bfa6;'>password123</strong></p>";
echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
echo "<tr style='background: #1a1d24; color: white;'><th>Status</th><th>Username</th><th>Office Name</th></tr>";

foreach ($accounts as $account) {
    // Check if account exists
    $checkStmt = $con->prepare("SELECT id FROM office_accounts WHERE username = ?");
    $checkStmt->bind_param("s", $account['username']);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows > 0) {
        // Update existing account
        $updateStmt = $con->prepare("UPDATE office_accounts SET password = ?, office_name = ? WHERE username = ?");
        $updateStmt->bind_param("sss", $hashedPassword, $account['office_name'], $account['username']);
        
        if ($updateStmt->execute()) {
            echo "<tr><td>🔄 Updated</td><td><strong>{$account['username']}</strong></td><td>{$account['office_name']}</td></tr>";
        } else {
            echo "<tr><td>❌ Error</td><td>{$account['username']}</td><td>{$updateStmt->error}</td></tr>";
        }
        $updateStmt->close();
    } else {
        // Insert new account
        $insertStmt = $con->prepare("INSERT INTO office_accounts (username, password, office_name) VALUES (?, ?, ?)");
        $insertStmt->bind_param("sss", $account['username'], $hashedPassword, $account['office_name']);
        
        if ($insertStmt->execute()) {
            echo "<tr><td>✅ Created</td><td><strong>{$account['username']}</strong></td><td>{$account['office_name']}</td></tr>";
        } else {
            echo "<tr><td>❌ Error</td><td>{$account['username']}</td><td>{$insertStmt->error}</td></tr>";
        }
        $insertStmt->close();
    }
    
    $checkStmt->close();
}

echo "</table>";

echo "<br><h3>✅ Setup Complete!</h3>";
echo "<p><strong>Test Login Credentials:</strong></p>";
echo "<ul>";
echo "<li>Username: <strong>admin</strong> | Password: <strong>password123</strong></li>";
echo "<li>Username: <strong>registrar</strong> | Password: <strong>password123</strong></li>";
echo "<li>Username: <strong>hr</strong> | Password: <strong>password123</strong></li>";
echo "<li>Username: <strong>finance</strong> | Password: <strong>password123</strong></li>";
echo "</ul>";

echo "<br><div style='background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px;'>";
echo "<strong>⚠️ SECURITY WARNING:</strong><br>";
echo "Please <strong style='color: red;'>DELETE this setup_database.php file immediately</strong> after running it!<br>";
echo "Leaving this file accessible is a security risk.";
echo "</div>";

echo "<br><p><a href='index.html' style='display: inline-block; padding: 10px 20px; background: #00bfa6; color: white; text-decoration: none; border-radius: 5px;'>← Back to Login</a></p>";

$con->close();
?>