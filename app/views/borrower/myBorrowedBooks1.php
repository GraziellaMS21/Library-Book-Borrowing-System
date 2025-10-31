<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../../models/manageUsers.php");

$borrowObj = new BorrowDetails();
$userObj = new User();

//fetch user
$userID = $_SESSION["user_id"];
$user = $userObj->fetchUser($userID);

// Determines the current Modal
$current_modal = $_GET['modal'] ?? '';
$open_modal = '';
// Determines the current tab
$current_tab = $_GET['tab'] ?? 'pending';

if ($current_modal === 'view') {
    $open_modal = 'viewBorrowDetailsModal';
}


//open Success Modals
if ($success_modal === "cancel") {
    $success_message = "Cancelled";
} elseif ($success_modal === "delete") {
    $success_message = "Deleting";
}
