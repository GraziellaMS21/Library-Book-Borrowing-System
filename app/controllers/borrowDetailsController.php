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
            $total_replacement_fine = $replacement_cost;
            $results['fine_amount'] = $total_replacement_fine;
            $results['fine_reason'] = 'Lost'; 
            $results['fine_status'] = 'Unpaid';
            $results['fine_amount'] = $late_fine_amount + $replacement_cost;
            $results['fine_reason'] = 'Lost (Overdue)'; 

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

    $detail['userID'] = trim(htmlspecialchars($_POST["userID"] ?? ''));
    $detail['bookID'] = trim(htmlspecialchars($_POST["bookID"] ?? ''));
    $detail['pickup_date'] = trim(htmlspecialchars($_POST["pickup_date"] ?? ''));
    $detail['expected_return_date'] = trim(htmlspecialchars($_POST["expected_return_date"] ?? ''));
    $detail['no_of_copies'] = (int) trim(htmlspecialchars($_POST["no_of_copies"] ?? $_POST["copies"] ?? 1));
    $current_tab = trim(htmlspecialchars($_POST["current_tab"] ?? 'pending'));
    $detail['request_date'] = trim(htmlspecialchars($_POST["request_date"] ?? date("Y-m-d")));
    $detail['return_date'] = trim(htmlspecialchars($_POST["return_date"] ?? NULL));
    $detail['returned_condition'] = trim(htmlspecialchars($_POST["returned_condition"] ?? NULL));
    $detail['borrow_request_status'] = trim(htmlspecialchars($_POST["borrow_request_status"] ?? NULL));
    $detail['fine_amount'] = (float) trim(htmlspecialchars($_POST["fine_amount"] ?? 0.00));
    $detail['fine_reason'] = trim(htmlspecialchars($_POST["fine_reason"] ?? NULL));
    $detail['fine_status'] = trim(htmlspecialchars($_POST["fine_status"] ?? NULL));


    // 2. Validation
    if (empty($detail['userID'])) {
        $errors['userID'] = "User ID is required.";
    }
    if (empty($detail['bookID'])) {
        $errors['bookID'] = "Book ID is required.";
    }
    if (empty($detail['expected_return_date'])) {
         $errors['expected_return_date'] = "Expected Return Date is required.";
    }
    if ($detail['fine_amount'] < 0) {
        $errors['fine_amount'] = "Fine amount cannot be negative.";
    }
    if ($detail['no_of_copies'] < 1) {
        $errors['no_of_copies'] = "At least one copy must be requested.";
    }


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
        $borrowObj->fine_amount = $detail['fine_amount'];
        $borrowObj->fine_reason = $detail['fine_reason'];
        $borrowObj->fine_status = $detail['fine_status'];

        if ($action === 'add') {
            if ($borrowObj->addBorrowDetail()) {
                $success_count = $detail['no_of_copies'];
                header("Location: ../../app/views/borrower/catalogue.php?success_copies={$success_count}");
                exit;
            } else {
                $errors["general"] = "Failed to add the loan request to the database.";
            }
        } elseif ($action === 'edit' && $borrowID) {

            // When editing, if the status is set to 'Returned' and a return date exists, recalculate fine/lost status
            if ($detail['borrow_request_status'] === 'Returned' && $detail['return_date']) {
                
                $fine_results = calculateLateFineController(
                    $detail['expected_return_date'], 
                    $detail['return_date'], 
                    $bookObj, 
                    $detail['bookID']
                );

                if ($fine_results['fine_amount'] > 0) {
                    
                    // Only update fine fields if no other manual reason was set
                    if (empty($detail['fine_reason']) || $detail['fine_reason'] === 'Late') {
                        $borrowObj->fine_amount = $fine_results['fine_amount'];
                        $borrowObj->fine_reason = $fine_results['fine_reason'];
                    }

                    // Only set status to 'Unpaid' if the librarian hasn't manually set it to 'Paid'
                    if (empty($detail['fine_status']) || $detail['fine_status'] === 'Unpaid') {
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

    // 4. Handle Redirection on Failure (POST)
    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        $_SESSION["old"] = $detail;

        // Redirect back to the librarian view, triggering the modal
        $_SESSION['open_modal'] = 'editBorrowDetailModal';
        $_SESSION['edit_borrow_id'] = $borrowID;
        header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
        exit;
    }

} else {
    // Special case for blockUser as the ID passed is actually the userID
    if ($action === 'blockUser' && $borrowID) {
        $userID_to_block = $borrowID; // ID passed is actually userID for this action
        if ($userObj->updateUserStatus($userID_to_block, 'Blocked')) {
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=finedUsers&success=blocked");
            exit;
        } else {
            $_SESSION["errors"] = ["general" => "Failed to block user. Check if User model is correctly linked."];
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=finedUsers");
            exit;
        }
    }

    // Fetch current detail data for all other GET actions (requires borrowID)
    $current_detail = $borrowObj->fetchBorrowDetail($borrowID);
    if (!$current_detail) {
         $_SESSION["errors"] = ["general" => "Borrow detail not found."];
         header("Location: ../../app/views/librarian/borrowDetailsSection.php");
         exit;
    }

    $book_id_to_update = $current_detail['bookID'];
    $copies_to_move = (int) ($current_detail['no_of_copies'] ?? 1);

    // --- DELETE Action ---
    if ($action === 'delete' && $borrowID) {
        if ($borrowObj->deleteBorrowDetail($borrowID)) {
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?success=delete");
            exit;
        } else {
            $_SESSION["errors"] = ["general" => "Failed to delete detail."];
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?error=delete");
            exit;
        }
    }

    // --- PAID Action ---
    if ($action === 'paid' && $borrowID) {
        // Set properties from current detail
        foreach ($current_detail as $key => $value) {
            if (property_exists($borrowObj, $key)) {
                $borrowObj->$key = $value;
            }
        }

        // Recalculate late/lost fine before marking paid to get the exact amount
        $fine_results = calculateLateFineController(
            $borrowObj->expected_return_date, 
            $borrowObj->return_date, 
            $bookObj, 
            $borrowObj->bookID
        );
        $borrowObj->fine_amount = $fine_results['fine_amount'];
        $borrowObj->fine_status = 'Paid'; // Key change

        if ($borrowObj->editBorrowDetail($borrowID)) {
             header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=finedUsers&success=paid");
             exit;
        } else {
             $_SESSION["errors"] = ["general" => "Failed to mark fine as paid."];
             header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=finedUsers");
             exit;
        }
    }

    // --- Status Update Actions (ACCEPT, REJECT, PICKUP, RETURN) ---
    if (in_array($action, ['accept', 'reject', 'pickup', 'return']) && $borrowID) {
        $status_map = [
            'accept' => 'Approved',
            'reject' => 'Rejected',
            'pickup' => 'Borrowed',
            'return' => 'Returned',
        ];
        $new_status = $status_map[$action];

        // Set model properties from current detail
        foreach ($current_detail as $key => $value) {
            if (property_exists($borrowObj, $key)) {
                $borrowObj->$key = $value;
            }
        }
        $borrowObj->borrow_request_status = $new_status; // Update status

        // Special handling for Pickup and Return (STOCK UPDATE LOGIC & FINE CALCULATION)
        if ($action === 'pickup') {
            $borrowObj->pickup_date = date("Y-m-d"); // Log pickup date as today

            // 1. DECREMENT available book copies upon pickup
            if (!$bookObj->decrementBookCopies($book_id_to_update, $copies_to_move)) {
                $_SESSION["errors"] = ["general" => "Failed to update book stock (decrement)."];
                header("Location: ../../app/views/librarian/borrowDetailsSection.php");
                exit;
            }
        } elseif ($action === 'return') {
            $borrowObj->return_date = date("Y-m-d"); // Log actual return date

            // 1. CALCULATE LATE/LOST FINE
            $fine_results = calculateLateFineController(
                $current_detail['expected_return_date'], 
                $borrowObj->return_date, 
                $bookObj, 
                $borrowObj->bookID
            );

            if ($fine_results['fine_amount'] > 0) {
                $borrowObj->fine_amount = $fine_results['fine_amount'];
                $borrowObj->fine_reason = $fine_results['fine_reason'];
                $borrowObj->fine_status = 'Unpaid'; 
            } else {
                $borrowObj->fine_amount = 0.00;
                $borrowObj->fine_reason = null;
                $borrowObj->fine_status = null;
            }

            // 2. INCREMENT available book copies upon return
            if (!$bookObj->incrementBookCopies($book_id_to_update, $copies_to_move)) {
                $_SESSION["errors"] = ["general" => "Failed to update book stock (increment)."];
                header("Location: ../../app/views/librarian/borrowDetailsSection.php");
                exit;
            }
        }

        // 3. Execute update on the loan detail record
        if ($borrowObj->editBorrowDetail($borrowID)) {
            $redirect_tab = match ($new_status) {
                'Approved', 'Rejected' => 'pending',
                'Borrowed' => 'currently_borrowed',
                'Returned' => 'returned',
                default => 'pending',
            };
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$redirect_tab}&success={$action}");
            exit;
        } else {
            $_SESSION["errors"] = ["general" => "Failed to update detail status."];
            header("Location: ../../app/views/librarian/borrowDetailsSection.php");
            exit;
        }
    }
}
?>
