<?php
session_start();
require_once(__DIR__ . "/../models/manageList.php");
require_once(__DIR__ . "/../models/manageBook.php");
require_once(__DIR__ . "/../models/manageUsers.php");
require_once(__DIR__ . "/../models/manageBorrowDetails.php"); // NEW INCLUDE

$borrowListObj = new BorrowLists();
$bookObj = new Book();
$userObj = new User();
$borrowDetailsObj = new BorrowDetails(); // NEW INSTANTIATION
$userID = $_SESSION["user_id"];
$action = $_GET["action"] ?? null;
$listID = $_GET["listID"] ?? null;
$bookID = $_GET["bookID"] ?? null;
$copies = (int) ($_GET["copies"] ?? 1);

// New: Capture the source page for redirection
$source = $_GET["source"] ?? "myList.php";

//fetch user information based on ID
$userID = $_SESSION["user_id"];
$user = $userObj->fetchUser($userID);
$userTypeID = $user["userTypeID"];

if ($action === 'add' && $bookID) {
    $book = $bookObj->fetchBook($bookID);

    // --- START NEW LOGIC: Calculate Available-for-Request ---
    $pending_copies_count = $borrowDetailsObj->fetchPendingAndApprovedCopiesForBook($bookID);
    $available_for_request = max(0, $book['book_copies'] - $pending_copies_count);
    // --- END NEW LOGIC ---

    // Check for general unavailability or insufficient copies against the new available count
    if (!$book || $available_for_request < $copies) {
        // Failure: redirect to source with error
        header("Location: ../../app/views/borrower/{$source}?status=error_unavailable&bookID={$bookID}");
        exit;
    }

    $existing = $borrowListObj->fetchBorrrowListByBook($userID, $bookID);
    $new_copies = $copies;
    $success = false;

    // Determine the number of copies to add/update
    if ($existing) {
        $existing_listID = $existing['listID'];
        // Staff can add more copies
        if ($userTypeID == 2) {
            $new_copies = $existing['no_of_copies'] + $copies;

            // Limit staff to the remaining available copies
            if ($new_copies > $available_for_request) { // Check against the new count
                $new_copies = $available_for_request;
            }
        } else {
            // Non-staff is limited to 1 copy per book in the list
            $new_copies = 1;
            // Redirect immediately for non-staff attempting to add an existing book
            header("Location: ../../app/views/borrower/{$source}?status=existing&copies={$new_copies}&bookID={$bookID}");
            exit;
        }

        // Only update if copies actually change
        if ($existing['no_of_copies'] != $new_copies) {
            $borrowListObj->no_of_copies = $new_copies;
            $success = $borrowListObj->editBorrrowListCopies($existing_listID);
        } else {
            $success = true; // Already exists with the intended copies, report success
        }

    } else {
        // Add new entry
        // Non-staff is limited to 1 copy, so we enforce it if they pass more than 1
        if ($userTypeID != 2) {
            $new_copies = 1;
        }

        $borrowListObj->userID = $userID;
        $borrowListObj->bookID = $bookID;
        $borrowListObj->no_of_copies = $new_copies;
        $borrowListObj->date_added = date("Y-m-d H:i:s");
        $success = $borrowListObj->addBorrrowList();
        // $new_copies is already set here
    }

    if ($success) {
        // Success: redirect to source with status and details
        header("Location: ../../app/views/borrower/{$source}?status=added&copies={$new_copies}&bookID={$bookID}");
        exit;
    } else {
        // Generic error
        header("Location: ../../app/views/borrower/{$source}?status=error&bookID={$bookID}");
        exit;
    }

} elseif ($action === 'remove' && $listID) {
    if ($borrowListObj->deleteBorrrowList($listID)) {
        header("Location: ../../app/views/borrower/myList.php?status=removed");
        exit;
    } else {
        header("Location: ../../app/views/borrower/myList.php?status=error");
        exit;
    }
} elseif ($action === 'edit' && $listID) {
    $borrowListObj->no_of_copies = $copies;
    if ($borrowListObj->editBorrrowList($listID)) {
        header("Location: ../../app/views/borrower/myList.php?status=edit");
        exit;
    } else {
        header("Location: ../../app/views/borrower/myList.php?status=error");
        exit;
    }
} elseif ($action === 'clear') {
    if ($borrowListObj->clearBorrrowList($userID)) {
        return true;
    } else {
        return false;
    }
}

// Default redirect
header("Location: ../../app/views/borrower/myList.php");
exit;
