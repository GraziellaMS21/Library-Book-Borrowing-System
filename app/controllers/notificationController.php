<?php
session_start();
// FIX: The file is named manageNotifications.php, not Notification.php
require_once(__DIR__ . "/../models/manageNotifications.php");

// Ensure user is logged in
if (!isset($_SESSION["user_id"])) {
    exit; // Do nothing if not logged in
}

$userID = $_SESSION["user_id"];
$action = $_GET['action'] ?? '';
$notifID = $_GET['id'] ?? '';
$page = $_GET['page'];

if ($action === 'markRead') {
    $notificationObj = new Notification();
    $notificationObj->markAsRead( $notifID);

    header("Location: ../../app/views/borrower/{$page}");
    exit;
}

// Intentionally output nothing.
// This prevents the iframe from showing errors and respects "no json_encode".
exit;
?>