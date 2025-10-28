<?php
session_start();
require_once(__DIR__ . "/../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../models/manageBook.php");
require_once(__DIR__ . "/../models/manageList.php");

$borrowObj = new BorrowDetails();
$bookObj = new Book();
$borrowListObj = new BorrowLists();
$errors = [];

$action = $_GET["action"] ?? null;
$borrowID = $_POST["borrowID"] ?? $_GET["id"] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if ($action === 'add_multiple') {
        $userID = trim(htmlspecialchars($_POST["userID"] ?? ''));
        $pickup_date = trim(htmlspecialchars($_POST["pickup_date"] ?? ''));
        $expected_return_date = trim(htmlspecialchars($_POST["expected_return_date"] ?? ''));
        $is_list_checkout = $_POST["is_list_checkout"] ?? '0';

        // 1. Read the standard PHP array of book requests
        $book_requests = $_POST["book_requests"] ?? [];

        $total_success_copies = 0;
        $all_success = true;
        $successful_list_IDs = []; // To track successful inserts for database cleaning

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
                $listID = (int) ($request['listID'] ?? 0); // Get the listID from the submitted data

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
                    $borrowObj->borrow_request_status = 'Pending';
                    $borrowObj->borrow_status = NULL;
                    $borrowObj->fine_amount = 0.00;
                    $borrowObj->fine_reason = NULL;
                    $borrowObj->fine_status = NULL;

                    if ($borrowObj->addBorrowDetail()) {
                        $total_success_copies += $copies_requested;
                        // Track the listID for removal from the borrowing_lists table later
                        if ($listID > 0) {
                            $successful_list_IDs[] = $listID;
                        }
                    } else {
                        $errors["book_failure_" . $bookID_local] = "Failed to add bookID {$bookID_local} to the database.";
                        $all_success = false;
                    }
                }
            }

            if ($total_success_copies > 0) {

                // --- Database List Clearing Logic ---
                if ($is_list_checkout === '1') {
                    // Delete only the successful items from the list table
                    foreach ($successful_list_IDs as $id_to_delete) {
                        $borrowListObj->deleteBorrrowList($id_to_delete);
                    }
                }

                // Success/Partial Success: Redirect to myList.php with flags
                $status = $all_success ? 'success_checkout' : 'partial_success';
                header("Location: ../../app/views/borrower/myList.php?status={$status}&total_copies={$total_success_copies}&clear_list={$is_list_checkout}");
                exit;
            } else {
                $errors["general"] = "Failed to process any loan request from the list.";
            }
        }
    } elseif ($action === 'add') {
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
        if (empty($detail['userID']))
            $errors['userID'] = "User ID is required.";
        if (empty($detail['bookID']))
            $errors['bookID'] = "Book ID is required.";
        if (empty($detail['pickup_date']))
            $errors['pickup_date'] = "Pickup Date is required.";
        if (empty($detail['expected_return_date']))
            $errors['expected_return_date'] = "Expected Return Date is required.";
        if ($detail['no_of_copies'] < 1)
            $errors['no_of_copies'] = "At least one copy must be requested.";

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
        $detail['borrow_status'] = trim(htmlspecialchars($_POST["borrow_status"] ?? NULL));
        $detail['fine_amount'] = (float) trim(htmlspecialchars($_POST["fine_amount"] ?? 0.00));
        $detail['fine_reason'] = trim(htmlspecialchars($_POST["fine_reason"] ?? NULL));
        $detail['fine_status'] = trim(htmlspecialchars($_POST["fine_status"] ?? NULL));

        // 2. Validation
        if (empty($detail['userID']))
            $errors['userID'] = "User ID is required.";
        if (empty($detail['bookID']))
            $errors['bookID'] = "Book ID is required.";
        if (empty($detail['expected_return_date']))
            $errors['expected_return_date'] = "Expected Return Date is required.";
        if ($detail['fine_amount'] < 0)
            $errors['fine_amount'] = "Fine amount cannot be negative.";
        if ($detail['no_of_copies'] < 1)
            $errors['no_of_copies'] = "At least one copy must be requested.";

        if (empty(array_filter($errors))) {
            // Set object properties
            $borrowObj->userID = $detail['userID'];
            $borrowObj->bookID = $detail['bookID'];
            $borrowObj->no_of_copies = $detail['no_of_copies'];
            $borrowObj->pickup_date = $detail['pickup_date'];
            $borrowObj->expected_return_date = $detail['expected_return_date'];
            $borrowObj->return_date = $detail['return_date'];
            $borrowObj->returned_condition = $detail['returned_condition'];
            $borrowObj->borrow_request_status = $detail['borrow_request_status'];
            $borrowObj->borrow_status = $detail['borrow_status'];
            $borrowObj->fine_amount = $detail['fine_amount'];
            $borrowObj->fine_reason = $detail['fine_reason'];
            $borrowObj->fine_status = $detail['fine_status'];

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
            $bookID_post = $_POST['bookID'] ?? '';
            $no_of_copies = $_POST['no_of_copies'] ?? 1; // Assuming copies is in POST for single checkout failure
            header("Location: ../../app/views/borrower/confirmation.php?bookID={$bookID_post}&copies={$no_of_copies}");
            exit;
        }

        // Removed the unnecessary `elseif ($action === 'cancel')` block here since cancellation is a GET request
        // The original block contained incomplete/misplaced librarian logic.

    }

} else {
    // --- GET Logic for DELETE, ACCEPT, REJECT, PICKUP, RETURN, and CANCEL (Status Updates) ---

    // --- NEW BORROWER CANCEL LOGIC ---
    if ($action === 'cancel' && $borrowID) {

        // 1. Fetch current detail to check status (optional but good for validation)
        $detail = $borrowObj->fetchBorrowDetail($borrowID);
        $current_tab = $_GET['tab'] ?? 'pending'; // Get the tab to return to

        // The borrower can only cancel a request that is 'Pending' or 'Approved' (before pickup)
        if ($detail && ($detail['borrow_request_status'] === 'Pending' || $detail['borrow_request_status'] === 'Approved')) {

            // 2. Set status to 'Cancelled'
            $borrow_request_status = "Cancelled";
            $borrow_status = NULL;
            $return_date = NULL;
            // 3. Perform update
            if ($borrowObj->updateBorrowDetails($borrowID, $borrow_status, $borrow_request_status, $return_date)) {
                if ($detail['borrow_request_status'] === 'Approved') {
                    $book_id_to_update = $detail['bookID'];
                    $copies_to_move = $detail['no_of_copies'];

                    $bookObj->incrementBookCopies($book_id_to_update, $copies_to_move);
                }

                // 6. Redirect back to the user's borrowed books page with a success flag
                header("Location: ../../app/views/borrower/myBorrowedBooks.php?tab=pending&success=cancelled");
                exit;
            } else {
                $_SESSION["errors"] = ["general" => "Failed to update loan status to Cancelled."];
                header("Location: ../../app/views/borrower/myBorrowedBooks.php?tab={$current_tab}");
                exit;
            }
        } elseif ($detail && $detail['borrow_request_status'] === 'Cancelled') {
            // For the 'Done' button on an already cancelled item (for cleanup/hiding)
            // You can implement logic to soft-delete or hide it from the 'Pending' view here if needed.
            // For now, let's just send them back.
            header("Location: ../../app/views/borrower/myBorrowedBooks.php?tab=pending");
            exit;

        } else {
            $_SESSION["errors"] = ["general" => "Loan request not found or cannot be cancelled at this status."];
            header("Location: ../../app/views/borrower/myBorrowedBooks.php?tab={$current_tab}");
            exit;
        }
    }
    // --- END NEW BORROWER CANCEL LOGIC ---

    // Redirect to the appropriate controller for status updates (Librarian actions)
    if (in_array($action, ['delete', 'accept', 'reject', 'pickup', 'return']) && $borrowID) {
        header("Location: borrowDetailsController.php?action={$action}&id={$borrowID}");
        exit;
    }
}
?>