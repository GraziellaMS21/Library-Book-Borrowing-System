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

        $book_requests = $_POST["book_requests"] ?? [];

        $total_success_copies = 0;
        $all_success = true;
        $successful_list_IDs = [];

        if (empty($userID)) {
            $errors['userID'] = "User ID is required for multiple checkout.";
            $all_success = false;
        }
        if (empty($book_requests)) {
            $errors['books'] = "No books submitted for checkout.";
            $all_success = false;
        }

        if ($all_success) {
            foreach ($book_requests as $request) {
                $bookID_local = (int) ($request['bookID'] ?? 0);
                $copies_requested = (int) ($request['copies_requested'] ?? 1);
                $listID = (int) ($request['listID'] ?? 0);

                if ($bookID_local > 0 && $copies_requested > 0) {
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
                    $borrowObj->user_notified = NULL;

                    if ($borrowObj->addBorrowDetail()) {
                        $total_success_copies += $copies_requested;
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

                if ($is_list_checkout === '1') {
                    foreach ($successful_list_IDs as $id_to_delete) {
                        $borrowListObj->deleteBorrrowList($id_to_delete);
                    }
                }

                $status = $all_success ? 'success_checkout' : 'partial_success';
                header("Location: ../../app/views/borrower/myList.php?status={$status}&total_copies={$total_success_copies}&clear_list={$is_list_checkout}");
                exit;
            } else {
                $errors["general"] = "Failed to process any loan request from the list.";
            }
        }
    } elseif ($action === 'add') {
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

        if (empty(array_filter($errors))) {
            $borrowObj->userID = $detail['userID'];
            $borrowObj->bookID = $detail['bookID'];
            $borrowObj->no_of_copies = $detail['no_of_copies'];
            $borrowObj->request_date = $detail['request_date'];
            $borrowObj->pickup_date = $detail['pickup_date'];
            $borrowObj->expected_return_date = $detail['expected_return_date'];
            $borrowObj->return_date = $detail['return_date'];
            $borrowObj->returned_condition = $detail['returned_condition'];
            $borrowObj->borrow_request_status = $detail['borrow_request_status'];
            $borrowObj->borrow_status = $detail['borrow_status'];
            $borrowObj->fine_amount = $detail['fine_amount'];
            $borrowObj->fine_reason = $detail['fine_reason'];
            $borrowObj->fine_status = $detail['fine_status'];
            $borrowObj->user_notified = null;

            if ($borrowObj->addBorrowDetail()) {
                $success_count = $detail['no_of_copies'];
                header("Location: ../../app/views/borrower/catalogue.php?success_copies={$success_count}");
                exit;
            } else {
                $errors["general"] = "Failed to add the loan request to the database.";
            }
        }
    } elseif ($action === 'edit' && $borrowID) {
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

        $detail['user_notified'] = 1;
        if (in_array($detail['borrow_request_status'], ['Rejected', 'Cancelled'])) {
            $detail['user_notified'] = 0;
        }

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
            $borrowObj->user_notified = $detail['user_notified'];

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
        }
    }

    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;

        // Redirect logic to handle different failure cases
        if ($action === 'add_multiple') {
            // Redirect user back to the list on failure
            header("Location: ../../app/views/borrower/myList.php?status=error&message=checkout_failed");
            exit;
        }

        $_SESSION["old"] = $detail ?? [];

        if ($action === 'edit') {
            $_SESSION['open_modal'] = 'editBorrowDetailModal';
            $_SESSION['edit_borrow_id'] = $borrowID;
            header("Location: ../../app/views/librarian/borrowDetailsSection.php");
            exit;
        } elseif ($action === 'add') {
            $bookID_post = $_POST['bookID'] ?? '';
            $no_of_copies = $_POST['no_of_copies'] ?? 1;
            header("Location: ../../app/views/borrower/confirmation.php?bookID={$bookID_post}&copies={$no_of_copies}");
            exit;
        }
    }

} else {
    if ($action === 'mark_as_read' && $borrowID) {
        $current_tab = $_GET['tab'] ?? 'pending';
        if ($borrowObj->updateBorrowerNotifiedStatus($borrowID, 1)) {
            header("Location: ../../app/views/borrower/myBorrowedBooks.php?tab={$current_tab}&success=read");
            exit;
        } else {
            $_SESSION["errors"] = ["general" => "Failed to mark as read."];
            header("Location: ../../app/views/borrower/myBorrowedBooks.php?tab={$current_tab}");
            exit;
        }
    }

    if ($action === 'cancel' && $borrowID) {

        $detail = $borrowObj->fetchBorrowDetail($borrowID);
        $current_tab = $_GET['tab'] ?? 'pending';

        if ($detail && ($detail['borrow_request_status'] === 'Pending' || $detail['borrow_request_status'] === 'Approved')) {

            $borrow_request_status = "Cancelled";
            $borrow_status = NULL;
            $return_date = NULL;
            $user_notified = 0;

            if ($borrowObj->updateBorrowDetails($borrowID, $borrow_status, $borrow_request_status, $return_date, $user_notified)) {
                if ($detail['borrow_request_status'] === 'Approved') {
                    $book_id_to_update = $detail['bookID'];
                    $copies_to_move = $detail['no_of_copies'];

                    $bookObj->incrementBookCopies($book_id_to_update, $copies_to_move);
                }

                header("Location: ../../app/views/borrower/myBorrowedBooks.php?tab=pending&success=cancelled");
                exit;
            } else {
                $_SESSION["errors"] = ["general" => "Failed to update loan status to Cancelled."];
                header("Location: ../../app/views/borrower/myBorrowedBooks.php?tab={$current_tab}");
                exit;
            }
        } else {
            $_SESSION["errors"] = ["general" => "Loan request not found or cannot be cancelled at this status."];
            header("Location: ../../app/views/borrower/myBorrowedBooks.php?tab={$current_tab}");
            exit;
        }
    }
    if (in_array($action, ['delete', 'accept', 'reject', 'pickup', 'return']) && $borrowID) {
        header("Location: borrowDetailsController.php?action={$action}&id={$borrowID}");
        exit;
    }
}
?>