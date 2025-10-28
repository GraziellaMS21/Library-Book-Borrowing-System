<?php
session_start();
require_once(__DIR__ . "/../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../models/manageBook.php");
require_once(__DIR__ . "/../models/manageUsers.php");

$borrowObj = new BorrowDetails();
$bookObj = new Book();
$userObj = new User();
$errors = [];

$action = $_GET["action"] ?? null;
$borrowID = $_POST["borrowID"] ?? $_GET["id"] ?? null;
$current_tab = trim(htmlspecialchars($_POST["current_tab"] ?? $_GET["tab"] ?? 'currently_borrowed'));

// --- Fine Calculation Helper Function ---
function calculateLateFineController($expected_return_date, $comparison_date_string, $bookObj, $bookID)
{
    // If comparison date is null or invalid, use today
    $comparison_date_string = $comparison_date_string ?: date("Y-m-d");

    $comparison = new DateTime($comparison_date_string);
    $expected = new DateTime($expected_return_date);

    $results = [
        'is_lost' => false,
        'fine_amount' => 0.00,
        'fine_reason' => null,
        'fine_status' => null,
    ];

    // Only calculate fine if the comparison date is after the expected return date
    if ($comparison > $expected) {
        $interval = $expected->diff($comparison);
        $days_late = $interval->days;

        $weeks_late = ceil($days_late / 7);
        $late_fine_amount = $weeks_late * 20.00;

        // ---Check for Lost Status (15 weeks = 105 days) ---
        if ($days_late >= 105) {
            $results['is_lost'] = true;
            $replacement_cost = $bookObj->fetchBookReplacementCost($bookID);

            // Total Fine = Accumulated Late Fee + Replacement Cost (Base ₱400)
            $results['fine_amount'] = $late_fine_amount + $replacement_cost;
            $results['fine_reason'] = 'Lost (Overdue)';
            $results['fine_status'] = 'Unpaid';

        } else {
            // Standard Late Fine
            $results['fine_amount'] = $late_fine_amount;
            $results['fine_reason'] = 'Late';
            $results['fine_status'] = 'Unpaid';
        }
    }
    return $results;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Input Processing
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

    // 2. Validation
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

    // Additional validation for the new Return Modal action
    if ($action === 'return' && empty($detail['returned_condition'])) {
        $errors['returned_condition'] = "Returned condition is required to complete the return.";
    }


    if (empty(array_filter($errors))) {

        // Centralized object assignment for POST
        foreach ($detail as $key => $value) {
            if (property_exists($borrowObj, $key)) {
                $borrowObj->$key = $value;
            }
        }

        if ($action === 'edit' && $borrowID) {

            // When editing, if the status is set to 'Returned' and a return date exists, recalculate fine/lost status
            if ($borrowObj->borrow_status === 'Returned' && $borrowObj->return_date) {

                $fine_results = calculateLateFineController(
                    $borrowObj->expected_return_date,
                    $borrowObj->return_date,
                    $bookObj,
                    $borrowObj->bookID
                );

                if ($fine_results['fine_amount'] > 0) {
                    // Only update fine fields if no other manual reason was set
                    if (empty($borrowObj->fine_reason) || $borrowObj->fine_reason === 'Late') {
                        $borrowObj->fine_amount = $fine_results['fine_amount'];
                        $borrowObj->fine_reason = $fine_results['fine_reason'];
                    }
                    // Only set status to 'Unpaid' if the librarian hasn't manually set it to 'Paid'
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
                // Populate required fields for fine calculation and inventory update
                $borrowObj->userID = $current_detail['userID'];
                $borrowObj->pickup_date = $current_detail['pickup_date'];
                $borrowObj->expected_return_date = $current_detail['expected_return_date'];
                $borrowObj->bookID = $current_detail['bookID'];
                $borrowObj->no_of_copies = $current_detail['no_of_copies'];
            }

            // 2. Set all required return fields (as requested)
            $borrowObj->return_date = date("Y-m-d");
            $borrowObj->borrow_request_status = NULL;
            $borrowObj->borrow_status = 'Returned';

            // 3. Calculate Fine (This updates fine_amount, fine_reason, fine_status on $borrowObj)
            $fine_results = calculateLateFineController(
                $borrowObj->expected_return_date,
                $borrowObj->return_date,
                $bookObj,
                $borrowObj->bookID
            );

            // Update fine details on the object
            $borrowObj->fine_amount = $fine_results['fine_amount'];
            $borrowObj->fine_reason = $fine_results['fine_reason'];
            $borrowObj->fine_status = $fine_results['fine_status'];

            // 4. Update inventory (Increment copies)
            if (empty($errors) && !$bookObj->incrementBookCopies($borrowObj->bookID, $borrowObj->no_of_copies)) {
                $errors["general"] = "Failed to update book stock (increment).";
            }

            // 5. Save all changes (including the updated returned_condition and status)
            if (empty($errors) && $borrowObj->editBorrowDetail($borrowID)) {
                // Success: Redirect to the 'returned' tab for Admin view
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=returned&success=returned");
                exit;
            } else {
                $errors["general"] = $errors["general"] ?? "Failed to complete book return process.";
            }
        }
    }

    // --- UPDATED ERROR HANDLING TO USE MODAL PARAMETERS ---
    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        $_SESSION["old"] = $detail;

        $modal_param = '';

        if ($action === 'return' && $borrowID) {
            $modal_param = 'return';
        } elseif ($action === 'edit' && $borrowID) {
            $modal_param = 'edit';
        }

        // Redirect back using modal and id parameters, consistent with userController.php
        header("Location: ../../app/views/librarian/borrowDetailsSection.php?modal={$modal_param}&id={$borrowID}&tab={$current_tab}");
        exit;
    }
    // --- END UPDATED ERROR HANDLING ---

} else {
    // --- START REFACTORED GET ACTIONS (ADMIN ACTIONS ONLY) ---

    // Special case: Block user (requires only userID, not borrow details)
    if ($action === 'blockUser' && $borrowID) {
        if ($userObj->updateUserStatus($borrowID, 'Blocked')) {
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=currently_borrowed&success=blocked");
            exit;
        } else {
            $_SESSION["errors"] = ["general" => "Failed to block user. Check if User model is correctly linked."];
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=currently_borrowed");
            exit;
        }
    }

    // Actions that require fetching borrow details
    if ($borrowID && in_array($action, ['delete', 'paid', 'accept', 'reject', 'pickup', 'cancel'])) { // ADD 'cancel' here
        $current_detail = $borrowObj->fetchBorrowDetail($borrowID);
        if (!$current_detail) {
            $_SESSION["errors"] = ["general" => "Borrow detail not found."];
            header("Location: ../../app/views/librarian/borrowDetailsSection.php");
            exit;
        }

        // Centralized object assignment for GET: **FIXED to populate all fields first**
        foreach ($current_detail as $key => $value) {
            if (property_exists($borrowObj, $key)) {
                // Check if the value is coming from the DB as null and set the object property to PHP NULL, not string 'NULL'
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
                // Redirect back to the modal on failure
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?modal=delete&id={$borrowID}&tab={$current_tab}");
                exit;
            }
        }
        if ($action === 'paid') {

            // 1. Fetch the original loan details to get the correct userID and bookID
            $current_detail = $borrowObj->fetchBorrowDetail($borrowID);

            if (!$current_detail) {
                $_SESSION["errors"] = ["general" => "Borrow detail not found for fine payment."];
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=fined");
                exit;
            }

            // --- Set BorrowDetails Object Properties for Update ---
            // These properties are required by editBorrowDetail() (which uses all object properties)
            $borrowObj->userID = $current_detail['userID']; // MUST be set for edit!
            $borrowObj->bookID = $current_detail['bookID']; // MUST be set for edit!
            $borrowObj->no_of_copies = $current_detail['no_of_copies'];
            $borrowObj->request_date = $current_detail['request_date'];
            $borrowObj->pickup_date = $current_detail['pickup_date'];
            $borrowObj->expected_return_date = $current_detail['expected_return_date'];
            $borrowObj->return_date = date("Y-m-d"); // Set the actual return date to today

            // Fine Details (assuming they were previously set on the object properties or fetched elsewhere)
            $fine_amount = $borrowObj->fine_amount; // Assuming this was set by the previous flow
            $fine_reason = $borrowObj->fine_reason; // Assuming this was set by the previous flow

            // 2. Set the final status fields
            $borrowObj->fine_status = 'Paid'; // FINE IS PAID
            $borrowObj->fine_amount = $fine_amount;
            $borrowObj->fine_reason = $fine_reason;
            $borrowObj->returned_condition = 'Good'; // Assuming 'Good' when fine is settled
            $borrowObj->borrow_request_status = 'NULL';
            $borrowObj->borrow_status = 'Returned'; // LOAN IS RETURNED


            // 3. Increment Book Stock
            if (empty($errors) && !$bookObj->incrementBookCopies($borrowObj->bookID, $borrowObj->no_of_copies)) {
                $errors["general"] = "Failed to update book stock (increment).";
            }

            // 4. Update Borrow Record (This marks the borrow_status = 'Returned', which frees up the user's limit.)
            if ($borrowObj->editBorrowDetail($borrowID)) {
                // The borrower's limit is automatically freed because the record's borrow_status is now 'Returned', 
                // which is excluded from the total borrowed count calculated by fetchTotalBorrowedBooks().
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=returned&success=paid");
                exit;
            } else {
                $_SESSION["errors"] = ["general" => "Failed to mark fine as paid. Database error."];
                // Redirect back to the fine list if the update fails
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=fined"); // Change to 'fined' tab
                exit;
            }
        }
        // Consolidated status update logic for Accept, Reject, Pickup, Cancel
        if ($action === 'accept') {
            $borrowObj->borrow_request_status = 'Approved';
            $borrowObj->borrow_status = NULL; // Must remain NULL until picked up

        } elseif ($action === 'reject') {
            $borrowObj->borrow_request_status = 'Rejected';
            $borrowObj->borrow_status = NULL;
            // Also decrement the available copies if the status was 'Approved'/'Pickup' (admin only)
            if ($current_detail['borrow_request_status'] === 'Approved') {
                if (!$bookObj->incrementBookCopies($book_id_to_update, $copies_to_move)) {
                    $_SESSION["errors"] = ["general" => "Failed to update book stock (increment) on admin reject."];
                    header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
                    exit;
                }
            }
        } elseif ($action === 'pickup') {
            // **FIXED LOGIC FOR PICKUP**
            $borrowObj->borrow_request_status = 'Approved'; // Remains Approved (or set to Approved if Pending)
            $borrowObj->borrow_status = 'Borrowed'; // **Set to Borrowed for active loan tracking**
            $borrowObj->pickup_date = date("Y-m-d"); // Set the pickup date

            // --- START INVENTORY CHECK (CRITICAL SAFEGUARD) ---
            $book_info = $bookObj->fetchBook($book_id_to_update);
            $available_physical_copies = (int) ($book_info['book_copies'] ?? 0);

            if ($available_physical_copies < $copies_to_move) {
                // Log and report the error to the librarian
                $_SESSION["errors"] = ["general" => "Error: Cannot fulfill claim (BorrowID {$borrowID}). Only {$available_physical_copies} copies remaining, but {$copies_to_move} are requested. Reject this request manually."];
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=pickup");
                exit;
            }
            // --- END INVENTORY CHECK ---

            if (!$bookObj->decrementBookCopies($book_id_to_update, $copies_to_move)) {
                $_SESSION["errors"] = ["general" => "Failed to update book stock (decrement)."];
                header("Location: ../../app/views/librarian/borrowDetailsSection.php");
                exit;
            }
        } elseif ($action === 'cancel') {
            // --- NEW ADMIN CANCEL LOGIC ---

            // 1. Update status
            $borrowObj->borrow_request_status = 'Cancelled';
            $borrowObj->borrow_status = NULL;
            $borrowObj->return_date = date("Y-m-d"); // Log the cancellation date

            // 2. Check current status and return copies if necessary
            // Only increment if the status was Approved (copies were reserved)
            if ($current_detail['borrow_request_status'] === 'Approved' && $current_detail['borrow_status'] !== 'Borrowed') {
                if (!$bookObj->incrementBookCopies($book_id_to_update, $copies_to_move)) {
                    $_SESSION["errors"] = ["general" => "Failed to update book stock (increment) on admin cancel."];
                    header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
                    exit;
                }
            }
            // --- END NEW ADMIN CANCEL LOGIC ---
        }

        // Final save for status updates (Accept/Reject/Pickup/Cancel)
        if (in_array($action, ['accept', 'reject', 'pickup', 'cancel'])) {
            if ($borrowObj->editBorrowDetail($borrowID)) {

                // Consolidated librarian redirect (always back to the admin page)
                $redirect_tab = match ($action) {
                    'accept', 'reject' => 'pending',
                    'pickup' => 'currently_borrowed',
                    'cancel' => 'pickup', // Redirect to pickup tab after cancelling
                    default => 'pending',
                };

                // Use the original tab from the GET request if available, otherwise use the calculated one
                $final_redirect_tab = $_GET['tab'] ?? $redirect_tab;

                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$final_redirect_tab}&success={$action}");
                exit;
            } else {
                $_SESSION["errors"] = ["general" => "Failed to update detail status."];
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
                exit;
            }
        }
    }
}
?>