<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once(__DIR__ . "/../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../models/manageBook.php");
require_once(__DIR__ . "/../models/manageUsers.php");

$borrowObj = new BorrowDetails();
$bookObj = new Book();
$userObj = new User();
$errors = [];

$action = $_POST["action"] ?? $_GET["action"] ?? null;
$borrowID = $_POST["borrowID"] ?? $_GET["id"] ?? null;
$current_tab = trim(htmlspecialchars($_POST["current_tab"] ?? $_GET["tab"] ?? 'currently_borrowed'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $detail['borrowID'] = $borrowID;
    $detail['userID'] = trim(htmlspecialchars($_POST["userID"] ?? ''));
    $detail['bookID'] = trim(htmlspecialchars($_POST["bookID"] ?? ''));
    $detail['pickup_date'] = trim(htmlspecialchars($_POST["pickup_date"] ?? ''));
    $detail['expected_return_date'] = trim(htmlspecialchars($_POST["expected_return_date"] ?? ''));
    $detail['no_of_copies'] = (int) trim(htmlspecialchars($_POST["no_of_copies"] ?? $_POST["copies"] ?? 1));
    $detail['request_date'] = trim(htmlspecialchars($_POST["request_date"] ?? date("Y-m-d")));
    $detail['return_date'] = trim(htmlspecialchars($_POST["return_date"] ?? NULL));
    $detail['returned_condition'] = trim(htmlspecialchars($_POST["returned_condition"] ?? NULL));
    $detail['borrow_request_status'] = trim(htmlspecialchars($_POST["borrow_request_status"] ?? NULL));
    $detail['borrow_status'] = trim(htmlspecialchars($_POST["borrow_status"] ?? NULL));
    $detail['fine_amount'] = (float) trim(htmlspecialchars($_POST["fine_amount"] ?? 0.00));
    $detail['fine_reason'] = trim(htmlspecialchars($_POST["fine_reason"] ?? NULL));
    $detail['fine_status'] = trim(htmlspecialchars($_POST["fine_status"] ?? NULL));

    // Validation
    if (empty($detail['userID']) && $action !== 'return') {
        $errors['userID'] = "User ID is required.";
    }
    if (empty($detail['bookID']) && $action !== 'return') {
        $errors['bookID'] = "Book ID is required.";
    }
    if (empty($detail['expected_return_date']) && $action !== 'return') {
        $errors['expected_return_date'] = "Expected Return Date is required.";
    }
    if ($detail['fine_amount'] < 0) {
        $errors['fine_amount'] = "Fine amount cannot be negative.";
    }
    if ($detail['no_of_copies'] < 1 && $action !== 'return') {
        $errors['no_of_copies'] = "At least one copy must be requested.";
    }
    if ($action === 'return' && empty($detail['returned_condition'])) {
        $errors['returned_condition'] = "Returned condition is required to complete the return.";
    }

    if (empty(array_filter($errors))) {
        foreach ($detail as $key => $value) {
            if (property_exists($borrowObj, $key)) {
                $borrowObj->$key = $value;
            }
        }

        if ($action === 'edit' && $borrowID) {
            // Apply fine calculation logic only if a return date is present (manual return/update)
            if ($borrowObj->borrow_status === 'Returned' && $borrowObj->return_date) {

                $fine_results = $borrowObj->calculateFinalFine(
                    $borrowObj->expected_return_date,
                    $borrowObj->return_date,
                    $bookObj,
                    $borrowObj->bookID
                );

                if ($fine_results['fine_amount'] > 0) {
                    if (empty($borrowObj->fine_reason) || $borrowObj->fine_reason === 'Late') {
                        $borrowObj->fine_amount = $fine_results['fine_amount'];
                        $borrowObj->fine_reason = $fine_results['fine_reason'];
                    }
                    if (empty($borrowObj->fine_status) || $borrowObj->fine_status === 'Unpaid') {
                        $borrowObj->fine_status = 'Unpaid';
                    }
                }
            }

            if ($borrowObj->editBorrowDetail($borrowID)) {
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}&success=edit");
                exit;
            } else {
                $errors["general"] = "Failed to edit detail due to a database error.";
            }

        } elseif ($action === 'return' && $borrowID) {
            $current_detail = $borrowObj->fetchBorrowDetail($borrowID);
            if (!$current_detail) {
                $errors['general'] = "Cannot find loan detail to process return.";
            } else {
                // Ensure necessary properties are set for increment and fine calculation
                $borrowObj->userID = $current_detail['userID'];
                $borrowObj->pickup_date = $current_detail['pickup_date'];
                $borrowObj->expected_return_date = $current_detail['expected_return_date'];
                $borrowObj->bookID = $current_detail['bookID'];
                $borrowObj->no_of_copies = $current_detail['no_of_copies'];
            }

            if(empty($errors)){
                $borrowObj->return_date = date("Y-m-d");
                $borrowObj->borrow_request_status = NULL;
                $borrowObj->borrow_status = 'Returned';

                $fine_results = $borrowObj->calculateFinalFine(
                    $borrowObj->expected_return_date,
                    $borrowObj->return_date,
                    $bookObj,
                    $borrowObj->bookID
                );

                // Update detail object with fine results
                $borrowObj->fine_amount = $fine_results['fine_amount'];
                $borrowObj->fine_reason = $fine_results['fine_reason'];
                $borrowObj->fine_status = $fine_results['fine_status'];
                $borrowObj->returned_condition = $detail['returned_condition']; // Use posted condition

                if (!$bookObj->incrementBookCopies($borrowObj->bookID, $borrowObj->no_of_copies)) {
                    $errors["general"] = "Failed to update book stock (increment).";
                }
            }

            if (empty($errors) && $borrowObj->editBorrowDetail($borrowID)) {
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=returned&success=returned");
                exit;
            } else {
                $errors["general"] = $errors["general"] ?? "Failed to complete book return process.";
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        $_SESSION["old"] = $detail;

        $modal_param = match ($action) {
            'return' => 'return',
            'edit' => 'edit',
            default => '',
        };

        header("Location: ../../app/views/librarian/borrowDetailsSection.php?modal={$modal_param}&id={$borrowID}&tab={$current_tab}");
        exit;
    }

}

if ($borrowID) {
    if ($action === 'blockUser') {
        if ($userObj->updateUserStatus($userID, "", 'Blocked')) {
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=currently_borrowed&success=blocked");
            exit;
        } else {
            $_SESSION["errors"] = ["general" => "Failed to block user. Check if User model is correctly linked."];
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=currently_borrowed");
            exit;
        }
    }

    $current_detail = $borrowObj->fetchBorrowDetail($borrowID);
    if (!$current_detail) {
        $_SESSION["errors"] = ["general" => "Borrow detail not found."];
        header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
        exit;
    }
    foreach ($current_detail as $key => $value) {
        if (property_exists($borrowObj, $key)) {
            $borrowObj->$key = ($value === 'NULL' || $value === null) ? null : $value;
        }
    }

    $book_id_to_update = $current_detail['bookID'];
    $copies_to_move = (int) ($current_detail['no_of_copies'] ?? 1);

    if ($action === 'delete') {
        if ($borrowObj->deleteBorrowDetail($borrowID)) {
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}&success=delete");
            exit;
        } else {
            $_SESSION["errors"] = ["general" => "Failed to delete detail."];
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?modal=delete&id={$borrowID}&tab={$current_tab}");
            exit;
        }
    }

    if ($action === 'paid') {
        $borrowObj->return_date = date("Y-m-d");
        $fine_amount = $borrowObj->fine_amount; 
        $fine_reason = $borrowObj->fine_reason;

        $borrowObj->fine_status = 'Paid';
        $borrowObj->fine_amount = $fine_amount;
        $borrowObj->fine_reason = $fine_reason;
        $borrowObj->returned_condition = 'Good';
        $borrowObj->borrow_request_status = NULL;
        $borrowObj->borrow_status = 'Returned';

        if (!$bookObj->incrementBookCopies($borrowObj->bookID, $borrowObj->no_of_copies)) {
            $_SESSION["errors"] = ["general" => "Failed to update book stock (increment) on fine payment."];
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=borrowed"); 
            exit;
        }

        if ($borrowObj->editBorrowDetail($borrowID)) {
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=returned&success=paid");
            exit;
        } else {
            $_SESSION["errors"] = ["general" => "Failed to mark fine as paid. Database error."];
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
            exit;
        }
    }

    if (in_array($action, ['accept', 'reject', 'pickup', 'cancel'])) {
        $final_redirect_tab = $current_tab;

        if ($action === 'accept') {
            $borrowObj->borrow_request_status = 'Approved';
            $borrowObj->borrow_status = NULL;

        } elseif ($action === 'reject') {
            $borrowObj->borrow_request_status = 'Rejected';
            $borrowObj->borrow_status = NULL;
            $final_redirect_tab = 'rejected';
            if ($current_detail['borrow_request_status'] === 'Approved' || $current_detail['borrow_request_status'] === 'Pending') {
                if (!$bookObj->incrementBookCopies($book_id_to_update, $copies_to_move)) {
                    $_SESSION["errors"] = ["general" => "Failed to update book stock (increment) on admin reject."];
                    header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
                    exit;
                }
            }
        } elseif ($action === 'pickup') {
            $borrowObj->borrow_request_status = 'Approved';
            $borrowObj->borrow_status = 'Borrowed';
            $borrowObj->pickup_date = date("Y-m-d");
            $final_redirect_tab = 'borrowed';

            $book_info = $bookObj->fetchBook($book_id_to_update);
            $available_physical_copies = (int) ($book_info['book_copies'] ?? 0);

            if ($available_physical_copies < $copies_to_move) {
                $_SESSION["errors"] = ["general" => "Error: Cannot fulfill claim (BorrowID {$borrowID}). Only {$available_physical_copies} copies remaining, but {$copies_to_move} are requested. Reject this request manually."];
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
                exit;
            }

            if (!$bookObj->decrementBookCopies($book_id_to_update, $copies_to_move)) {
                $_SESSION["errors"] = ["general" => "Failed to update book stock (decrement)."];
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
                exit;
            }
        } elseif ($action === 'cancel') {
            $borrowObj->borrow_request_status = 'Cancelled';
            $borrowObj->borrow_status = NULL;
            $borrowObj->return_date = NULL;
            $final_redirect_tab = 'cancelled';
            if ($current_detail['borrow_request_status'] === 'Approved' && $current_detail['borrow_status'] !== 'Borrowed') {
                if (!$bookObj->incrementBookCopies($book_id_to_update, $copies_to_move)) {
                    $_SESSION["errors"] = ["general" => "Failed to update book stock (increment) on admin cancel."];
                    header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
                    exit;
                }
            }
        }

        if ($borrowObj->editBorrowDetail($borrowID)) {
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$final_redirect_tab}&success={$action}");
            exit;
        } else {
            $_SESSION["errors"] = ["general" => "Failed to update detail status."];
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
            exit;
        }
    }
}

if (!isset($_GET['action']) && !isset($_POST['action']) && $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
    exit;
}
?>