<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once(__DIR__ . "/../models/manageNotifications.php");

// Ensure user is logged in
if (!isset($_SESSION["user_id"])) {
    exit;
}

$userID = $_SESSION["user_id"];
$action = $_GET['action'] ?? '';
$notifID = $_GET['id'] ?? '';
$page = $_GET['page'] ?? '';

$notificationObj = new Notification();

if ($action === 'markRead') {
    $notificationObj->markAsRead($notifID);

    // If request came from AJAX (header), just exit
    if (isset($_GET['ajax'])) {
        echo "success";
        exit;
    }

    // Redirect back to referring page or specific page
    if ($page === 'notifications.php') {
        header("Location: ../../app/views/borrower/notifications.php?tab=unread"); 
        // Redirecting to tab=unread or just notifications.php depending on UX preference
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    }
    exit;
}

if ($action === 'markAllRead') {
    $notificationObj->markAllAsRead($userID);
    
    if (isset($_GET['ajax'])) {
        echo "success";
        exit;
    }
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// New Delete Action
if ($action === 'delete') {
    $notificationObj->deleteNotification($notifID);
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

exit;
?>