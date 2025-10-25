<?php
session_start();
require_once(__DIR__ . "/../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../models/manageBook.php");
// require_once(__DIR__ . "/../models/manageUsers.php"); // Not strictly needed here, but kept if user model logic is elsewhere

$borrowObj = new BorrowDetails();
$bookObj = new Book();
$errors = [];

$action = $_GET["action"] ?? null;
$borrowID = $_POST["borrowID"] ?? $_GET["id"] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Handling Multiple Books (New Action: add_multiple) ---
    if ($action === 'add_multiple') {
        $userID = trim(htmlspecialchars($_POST["userID"] ?? ''));
        $pickup_date = trim(htmlspecialchars($_POST["pickup_date"] ?? ''));
        $expected_return_date = trim(htmlspecialchars($_POST["expected_return_date"] ?? ''));
        $is_list_checkout = $_POST["is_list_checkout"] ?? '0'; // Flag to determine if list clearing is needed

        // 1. Decode the JSON array of book requests
        $bookRequestsJson = $_POST["book_requests_json"] ?? '';
        $book_requests = json_decode($bookRequestsJson, true);
        
        $total_success_copies = 0;
        $all_success = true;

        if (empty($userID)) {
            $errors['userID'] = "User ID is required for multiple checkout.";
            $all_success = false;
        }
        if (empty($book_requests)) {
             $errors['books'] = "No books submitted for checkout.";
             $all_success = false;
        }

        if ($all_success) {
            // Loop through each book request and insert a separate detail record
            foreach ($book_requests as $request) {
                $bookID_local = (int) ($request['bookID'] ?? 0);
                $copies_requested = (int) ($request['copies_requested'] ?? 1);

                if ($bookID_local > 0 && $copies_requested > 0) {
                    // Set model properties for this specific book
                    $borrowObj->userID = $userID;
                    $borrowObj->bookID = $bookID_local;
                    $borrowObj->no_of_copies = $copies_requested;
                    $borrowObj->request_date = date("Y-m-d");
                    $borrowObj->pickup_date = $pickup_date;
                    $borrowObj->expected_return_date = $expected_return_date;
                    $borrowObj->return_date = NULL;
                    $borrowObj->returned_condition = NULL;
                    $borrowObj->borrow_request_status = 'Pending'; // All new requests start as Pending
                    $borrowObj->fine_amount = 0.00;
                    $borrowObj->fine_reason = NULL;
                    $borrowObj->fine_status = NULL;

                    if ($borrowObj->addBorrowDetail()) {
                        $total_success_copies += $copies_requested;
                    } else {
                        // Log the failure but try to continue with other books
                        $errors["book_failure_" . $bookID_local] = "Failed to add bookID {$bookID_local} to the database.";
                        $all_success = false;
                    }
                }
            }

            if ($total_success_copies > 0) {
                // Success/Partial Success: Redirect to myList.php with flags
                $status = $all_success ? 'success_checkout' : 'partial_success';
                header("Location: ../../app/views/borrower/myList.php?status={$status}&total_copies={$total_success_copies}&clear_list={$is_list_checkout}");
                exit;
            } else {
                $errors["general"] = "Failed to process any loan request from the list.";
            }
        }
    }
    // --- Handling Single Book (Original Action) ---
    elseif ($action === 'add') {
        // 1. Collect and Sanitize Data
        $detail['userID'] = trim(htmlspecialchars($_POST["userID"] ?? ''));
        $detail['bookID'] = trim(htmlspecialchars($_POST["bookID"] ?? ''));
        $detail['pickup_date'] = trim(htmlspecialchars($_POST["pickup_date"] ?? ''));
        $detail['expected_return_date'] = trim(htmlspecialchars($_POST["expected_return_date"] ?? ''));
        $detail['no_of_copies'] = (int) trim(htmlspecialchars($_POST["copies"] ?? 1));
        
        $detail['request_date'] = date("Y-m-d");
        $detail['return_date'] = null;
        $detail['returned_condition'] = null;
        $detail['borrow_request_status'] = 'Pending';
        $detail['fine_amount'] = 0.00;
        $detail['fine_reason'] = null;
        $detail['fine_status'] = null;
        
        $bookID_post = $detail['bookID'];

        // 2. Validation
        if (empty($detail['userID'])) $errors['userID'] = "User ID is required.";
        if (empty($detail['bookID'])) $errors['bookID'] = "Book ID is required.";
        if (empty($detail['pickup_date'])) $errors['pickup_date'] = "Pickup Date is required.";
        if (empty($detail['expected_return_date'])) $errors['expected_return_date'] = "Expected Return Date is required.";
        if ($detail['no_of_copies'] < 1) $errors['no_of_copies'] = "At least one copy must be requested.";

        // 3. Database Interaction
        if (empty(array_filter($errors))) {

            // Set model properties for the transaction
            $borrowObj->userID = $detail['userID'];
            $borrowObj->bookID = $detail['bookID'];
            $borrowObj->no_of_copies = $detail['no_of_copies'];
            $borrowObj->request_date = $detail['request_date'];
            $borrowObj->pickup_date = $detail['pickup_date'];
            $borrowObj->expected_return_date = $detail['expected_return_date'];
            $borrowObj->return_date = $detail['return_date'];
            $borrowObj->returned_condition = $detail['returned_condition'];
            $borrowObj->borrow_request_status = $detail['borrow_request_status'];
            $borrowObj->fine_amount = $detail['fine_amount'];
            $borrowObj->fine_reason = $detail['fine_reason'];
            $borrowObj->fine_status = $detail['fine_status'];

            if ($borrowObj->addBorrowDetail()) {
                $success_count = $detail['no_of_copies'];
                header("Location: ../../app/views/borrower/catalogue.php?success_copies={$success_count}");
                exit;
            } else {
                $errors["general"] = "Failed to add the loan request to the database.";
            }
        }
    } 
    // --- Handling Edit and Status Changes (Original Logic) ---
    elseif ($action === 'edit' && $borrowID) {
        // Collect, sanitize, validate, and process edit for librarian view
        // ... (Original 'edit' logic for librarian view goes here) ...
         // 1. Collect and Sanitize Data
        $detail['userID'] = trim(htmlspecialchars($_POST["userID"] ?? ''));
        $detail['bookID'] = trim(htmlspecialchars($_POST["bookID"] ?? ''));
        $detail['pickup_date'] = trim(htmlspecialchars($_POST["pickup_date"] ?? ''));
        $detail['expected_return_date'] = trim(htmlspecialchars($_POST["expected_return_date"] ?? ''));
        $detail['no_of_copies'] = (int) trim(htmlspecialchars($_POST["no_of_copies"] ?? 1));
        $current_tab = trim(htmlspecialchars($_POST["current_tab"] ?? 'pending'));
        $detail['return_date'] = trim(htmlspecialchars($_POST["return_date"] ?? NULL));
        $detail['returned_condition'] = trim(htmlspecialchars($_POST["returned_condition"] ?? NULL));
        $detail['borrow_request_status'] = trim(htmlspecialchars($_POST["borrow_request_status"] ?? NULL));
        $detail['fine_amount'] = (float) trim(htmlspecialchars($_POST["fine_amount"] ?? 0.00));
        $detail['fine_reason'] = trim(htmlspecialchars($_POST["fine_reason"] ?? NULL));
        $detail['fine_status'] = trim(htmlspecialchars($_POST["fine_status"] ?? NULL));
        
        // 2. Validation (copied from original)
        if (empty($detail['userID'])) $errors['userID'] = "User ID is required.";
        if (empty($detail['bookID'])) $errors['bookID'] = "Book ID is required.";
        if (empty($detail['expected_return_date'])) $errors['expected_return_date'] = "Expected Return Date is required.";
        if ($detail['fine_amount'] < 0) $errors['fine_amount'] = "Fine amount cannot be negative.";
        if ($detail['no_of_copies'] < 1) $errors['no_of_copies'] = "At least one copy must be requested.";

        if (empty(array_filter($errors))) {
             // Set object properties (copied from original)
            $borrowObj->userID = $detail['userID'];
            $borrowObj->bookID = $detail['bookID'];
            $borrowObj->no_of_copies = $detail['no_of_copies'];
            $borrowObj->pickup_date = $detail['pickup_date'];
            $borrowObj->expected_return_date = $detail['expected_return_date'];
            $borrowObj->return_date = $detail['return_date'];
            $borrowObj->returned_condition = $detail['returned_condition'];
            $borrowObj->borrow_request_status = $detail['borrow_request_status'];
            $borrowObj->fine_amount = $detail['fine_amount'];
            $borrowObj->fine_reason = $detail['fine_reason'];
            $borrowObj->fine_status = $detail['fine_status'];
            
            // Assume you have a calculateLateFineController function available
            // Note: If you have fine calculation here, you need to ensure the helper function is included or defined.

            if ($borrowObj->editBorrowDetail($borrowID)) {
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}&success=edit");
                exit;
            } else {
                $errors["general"] = "Failed to edit detail due to a database error.";
            }
        }
    }


    // 4. Handle Redirection on Failure (POST)
    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        
        // Redirect logic to handle different failure cases
        if ($action === 'add_multiple') {
            // Redirect user back to the list on failure
             header("Location: ../../app/views/borrower/myList.php?status=error&message=checkout_failed");
             exit;
        } 
        
        // Original single book/edit failure logic
        $_SESSION["old"] = $detail ?? [];

        if ($action === 'edit') {
            $_SESSION['open_modal'] = 'editBorrowDetailModal';
            $_SESSION['edit_borrow_id'] = $borrowID;
            header("Location: ../../app/views/librarian/borrowDetailsSection.php");
            exit;
        } elseif ($action === 'add') {
            // Redirect back to confirmation page for single book failure
             header("Location: ../../app/views/borrower/confirmation.php?bookID={$bookID_post}&copies={$detail['no_of_copies']}");
             exit;
        }
    }

} else {
    // --- GET Logic for DELETE, ACCEPT, REJECT, PICKUP, RETURN (Status Updates) ---
    if (in_array($action, ['delete', 'accept', 'reject', 'pickup', 'return']) && $borrowID) {
        // Redirect to the appropriate controller for status updates
        header("Location: borrowDetailsController.php?action={$action}&id={$borrowID}");
        exit;
    }
}
?>