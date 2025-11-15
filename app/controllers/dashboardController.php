<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once(__DIR__ . "/../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../models/manageBook.php");

$borrowObj = new BorrowDetails();
$bookObj = new Book();
$errors = [];

$action = $_GET["action"] ?? null;
$borrowID = $_GET["id"] ?? null;
$current_tab = trim(htmlspecialchars($_GET["tab"] ?? 'pending'));


if ($borrowID && in_array($action, ['accept', 'reject'])) {

    $current_detail = $borrowObj->fetchBorrowDetail($borrowID);
    if (!$current_detail) {
        $_SESSION["errors"] = ["general" => "Borrow detail not found for processing."];
        header("Location: ../../app/views/librarian/dashboard.php"); // Redirect to dashboard
        exit;
    }

    $book_id_to_update = $current_detail['bookID'];
    $copies_to_move = (int) ($current_detail['no_of_copies'] ?? 1);

    if ($action === 'accept') {
        $borrowObj->borrow_request_status = 'Approved';
        $borrowObj->borrow_status = NULL;
        if (!$bookObj->decrementBookCopies($book_id_to_update, $copies_to_move)) {
            $_SESSION["errors"] = ["general" => "Failed to update book stock (decrement)."];
            header("Location: ../../app/views/librarian/dashboard.php");
            exit;
        }

    } elseif ($action === 'reject') {
        $borrowObj->borrow_request_status = 'Rejected';
        $borrowObj->borrow_status = NULL;
        if ($current_detail['borrow_request_status'] === 'Approved' || $current_detail['borrow_request_status'] === 'Pending') {
            if (!$bookObj->incrementBookCopies($book_id_to_update, $copies_to_move)) {
                $_SESSION["errors"] = ["general" => "Failed to update book stock (increment) on rejection."];
                header("Location: ../../app/views/librarian/dashboard.php");
                exit;
            }
        }
    } elseif ($action === 'return') {
        $borrowObj->borrow_request_status = NULL;
        $borrowObj->borrow_status = 'Returned';
        if (!$bookObj->incrementBookCopies($book_id_to_update, $copies_to_move)) {
            $_SESSION["errors"] = ["general" => "Failed to update book stock (increment) on rejection."];
            header("Location: ../../app/views/librarian/dashboard.php");
            exit;
        }

    }

    foreach ($current_detail as $key => $value) {
        if (property_exists($borrowObj, $key)) {
            $borrowObj->$key = ($value === 'NULL' || $value === null) ? null : $value;
        }
    }
    $borrowObj->borrow_request_status = ($action === 'accept') ? 'Approved' : 'Rejected';
    $borrowObj->borrow_status = NULL;


    if ($borrowObj->editBorrowDetail($borrowID)) {
        $_SESSION["success"] = "Borrow request {$action}ed successfully."; // Set a simple success message
        header("Location: ../../app/views/librarian/dashboard.php"); // Redirect back to dashboard
        exit;
    } else {
        $_SESSION["errors"] = ["general" => "Failed to update detail status due to a database error."];
        header("Location: ../../app/views/librarian/dashboard.php");
        exit;
    }
}

header("Location: ../../app/views/librarian/dashboard.php");
exit;

?>