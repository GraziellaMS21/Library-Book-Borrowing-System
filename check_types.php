<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->connect();
$stmt = $conn->query("SELECT * FROM user_type");
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "User Types:\n";
foreach ($types as $t) {
    echo "ID: " . $t['userTypeID'] . " - Name: " . $t['type_name'] . " - Role: " . ($t['role'] ?? 'NULL') . "\n";
}
?>