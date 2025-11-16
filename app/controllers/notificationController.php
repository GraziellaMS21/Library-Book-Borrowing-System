<?php
session_start();
// FIX: The file is named manageNotifications.php, not Notification.php
require_once(__DIR__ . "/../models/manageNotifications.php"); 

// Ensure user is logged in
if (!isset($_SESSION["user_id"])) {
    exit; // Do nothing if not logged in
}

$userID = $_SESSION["user_id"];
$action = $_POST['action'] ?? ''; // Switched to POST

if ($action === 'mark_all_read') {
    $notificationObj = new Notification();
    $notificationObj->markAllAsRead($userID);
}

// Intentionally output nothing.
// This prevents the iframe from showing errors and respects "no json_encode".
exit;
?>