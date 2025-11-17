<?php
session_start();
date_default_timezone_set('Asia/Manila');
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
} // Inside notificationController.php

if ($action === 'markAllRead') {
    if (isset($_SESSION['user_id'])) {
        $notifObj = new Notification();
        // You need to create this method in your model
        $notifObj->markAllAsRead($_SESSION['user_id']); 
    }
    // If this was an AJAX request, just exit so we don't redirect
    if (isset($_GET['ajax'])) {
        echo "success";
        exit;
    }
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// Intentionally output nothing.
// This prevents the iframe from showing errors and respects "no json_encode".
exit;
?>