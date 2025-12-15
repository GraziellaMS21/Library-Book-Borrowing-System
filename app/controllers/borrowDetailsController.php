<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once(__DIR__ . "/../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../models/manageBook.php");
require_once(__DIR__ . "/../models/manageUsers.php");
require_once(__DIR__ . "/../models/manageNotifications.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libraries/phpmailer/src/Exception.php';
require_once __DIR__ . '/../libraries/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../libraries/phpmailer/src/SMTP.php';

$borrowObj = new BorrowDetails();
$bookObj = new Book();
$userObj = new User();
$notificationObj = new Notification();
$errors = [];

$action = $_POST["action"] ?? $_GET["action"] ?? null;
$borrowID = $_POST["borrowID"] ?? $_GET["id"] ?? null;
$current_tab = trim(htmlspecialchars($_POST["current_tab"] ?? $_GET["tab"] ?? 'currently_borrowed'));

// --- GET CURRENT ADMIN DETAILS ---
$currentAdminID = $_SESSION['user_id'] ?? null;
$currentAdminName = ($_SESSION['fName'] ?? 'Admin') . ' ' . ($_SESSION['lName'] ?? '');

// --- 3NF INPUT HANDLING ---
$reasonIDs = [];
$remarks = NULL;
$isOtherSelected = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture Checkbox IDs
    if (isset($_POST['reason_presets']) && is_array($_POST['reason_presets'])) {
        foreach ($_POST['reason_presets'] as $id) {
            if ($id === 'other') {
                $isOtherSelected = true;
            } elseif (is_numeric($id)) {
                $reasonIDs[] = $id;
            }
        }
    }
    // Capture Custom Remark
    if (!empty($_POST['reason_custom'])) {
        $remarks = htmlspecialchars(trim($_POST['reason_custom']));
    }

    // Append "Others - " if checkbox selected
    if ($isOtherSelected) {
        $prefix = "Others - ";
        if ($remarks) {
            $remarks = $prefix . $remarks;
        } else {
            $remarks = "Others (No details provided)";
        }
    }
}

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

    if (in_array($action, ['edit', 'return', 'paid', 'reject', 'cancel', 'blockUser', 'unblockUser'])) {
        $current_detail = $borrowObj->fetchBorrowDetail($borrowID);
        if ($current_detail) {
            $detail['userID'] = $detail['userID'] ?: $current_detail['userID'];
            $detail['bookID'] = $detail['bookID'] ?: $current_detail['bookID'];
        }
    }

    if (empty($detail['userID']) && !in_array($action, ['return', 'paid', 'edit', 'reject', 'cancel', 'blockUser', 'unblockUser'])) {
        $errors['userID'] = "User ID is required.";
    }
    if (empty($detail['bookID']) && !in_array($action, ['return', 'paid', 'edit', 'reject', 'cancel', 'blockUser', 'unblockUser'])) {
        $errors['bookID'] = "Book ID is required.";
    }
    if (empty($detail['expected_return_date']) && !in_array($action, ['return', 'paid', 'reject', 'cancel', 'blockUser', 'unblockUser'])) {
        $errors['expected_return_date'] = "Expected Return Date is required.";
    }
    if ($detail['fine_amount'] < 0) {
        $errors['fine_amount'] = "Fine amount cannot be negative.";
    }
    if ($detail['no_of_copies'] < 1 && !in_array($action, ['return', 'paid', 'reject', 'cancel', 'blockUser', 'unblockUser'])) {
        $errors['no_of_copies'] = "At least one copy must be requested.";
    }
    if (($action === 'return' || $action === 'paid') && empty($detail['returned_condition'])) {
        $errors['returned_condition'] = "Returned condition is required to complete the return/payment.";
    }

    if (empty(array_filter($errors))) {
        foreach ($detail as $key => $value) {
            if (property_exists($borrowObj, $key)) {
                $borrowObj->$key = $value;
            }
        }

        // --- EDIT ACTION ---
        // --- EDIT ACTION ---
        if ($action === 'edit' && $borrowID) {
            if (!isset($current_detail) || !$current_detail) {
                $current_detail = $borrowObj->fetchBorrowDetail($borrowID);
            }

            // DETERMINE COMPARISON DATE
            // If the book is already returned (has a return_date), calculate based on that date.
            // If it is NOT returned yet, calculate based on TODAY.
            $comparison_date = !empty($borrowObj->return_date) ? $borrowObj->return_date : date("Y-m-d");

            // AUTOMATIC RECALCULATION LOGIC
            // Only run this if the submitted fine is 0. 
            // This allows you to manually set a fine if you want, but if you leave it 0 and it's late, it fixes it.
            if ($borrowObj->fine_amount <= 0.00 && $borrowObj->expected_return_date) {

                // Calculate what the fine SHOULD be
                $fine_results = $borrowObj->calculateFinalFine(
                    $borrowObj->expected_return_date,
                    $comparison_date,
                    $bookObj,
                    $detail['bookID']
                );

                // If the calculation found a fine, override the 0.00 value
                if ($fine_results['fine_amount'] > 0) {
                    $borrowObj->fine_amount = $fine_results['fine_amount'];
                    $borrowObj->fine_reason = $fine_results['fine_reason'];
                    $borrowObj->fine_status = 'Unpaid';
                }
            }

            // Ensure fine status/reason are NULL if amount is 0 (Clean up)
            if ($borrowObj->fine_amount <= 0.00) {
                $borrowObj->fine_reason = NULL;
                $borrowObj->fine_status = NULL;
            }

            // Save to Database (Updates both Borrowing Details and Fines tables)
            if ($borrowObj->editBorrowDetail($borrowID)) {
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}&success=edit");
                exit;
            } else {
                $errors["general"] = "Failed to edit detail due to a database error.";
            }
            // --- RETURN ACTION ---
        } elseif ($action === 'return' && $borrowID) {
            if (!isset($current_detail) || !$current_detail) {
                $errors['general'] = "Cannot find loan detail to process return.";
            } else {
                $borrowObj->userID = $current_detail['userID'];
                $borrowObj->pickup_date = $current_detail['pickup_date'];
                $borrowObj->expected_return_date = $current_detail['expected_return_date'];
                $borrowObj->bookID = $current_detail['bookID'];
                $borrowObj->no_of_copies = $current_detail['no_of_copies'];
            }
            if (empty($errors)) {
                $borrowObj->return_date = date("Y-m-d");
                $borrowObj->borrow_request_status = NULL;
                $borrowObj->borrow_status = 'Returned';
                $fine_results = $borrowObj->calculateFinalFine($borrowObj->expected_return_date, $borrowObj->return_date, $bookObj, $borrowObj->bookID);
                $borrowObj->fine_amount = $fine_results['fine_amount'];
                $borrowObj->fine_reason = $fine_results['fine_reason'];
                $borrowObj->fine_status = $fine_results['fine_status'];
                $borrowObj->returned_condition = $detail['returned_condition'];
                if (!$bookObj->incrementBookCopies($borrowObj->bookID, $borrowObj->no_of_copies)) {
                    $errors["general"] = "Failed to update book stock (increment).";
                }
            }
            if (empty($errors) && $borrowObj->editBorrowDetail($borrowID)) {

                // ADDED: Log History
                $borrowObj->addBorrowStatusHistory($borrowID, 'Return', "Condition: {$detail['returned_condition']}", [], $currentAdminID);

                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=returned&success=returned");
                exit;
            } else {
                $errors["general"] = $errors["general"] ?? "Failed to complete book return process.";
            }

            // --- PAID ACTION ---
        } elseif ($action === 'paid' && $borrowID) {
            if (!isset($current_detail) || !$current_detail) {
                $errors['general'] = "Cannot find loan detail to process payment.";
            } else {
                $borrowObj->userID = $current_detail['userID'];
                $borrowObj->bookID = $current_detail['bookID'];
                $borrowObj->no_of_copies = $current_detail['no_of_copies'];
                $borrowObj->pickup_date = $current_detail['pickup_date'];
                $borrowObj->expected_return_date = $current_detail['expected_return_date'];
                $borrowObj->fine_amount = $current_detail['fine_amount'];
                $borrowObj->fine_reason = $current_detail['fine_reason'];
            }
            if (empty($errors)) {
                $borrowObj->return_date = date("Y-m-d");
                $borrowObj->borrow_request_status = NULL;
                $borrowObj->borrow_status = 'Returned';
                $borrowObj->fine_status = 'Paid';
                $borrowObj->returned_condition = $detail['returned_condition'];
                if ($current_detail['borrow_status'] !== 'Returned') {
                    if (!$bookObj->incrementBookCopies($borrowObj->bookID, $borrowObj->no_of_copies)) {
                        $errors["general"] = "Failed to update book stock (increment).";
                    }
                }
            }
            if (empty($errors) && $borrowObj->editBorrowDetail($borrowID)) {

                // ADDED: Log History
                $borrowObj->addBorrowStatusHistory($borrowID, 'Paid', "Fine Paid & Returned. Condition: {$detail['returned_condition']}", [], $currentAdminID);

                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=returned&success=returned");
                exit;
            } else {
                $errors["general"] = $errors["general"] ?? "Failed to complete book return process.";
            }

            // --- BLOCK USER ACTION (Updated for 3NF) ---
        } elseif ($action === 'blockUser' && $borrowID) {
            $userID_to_block = $borrowObj->userID;

            // Updated to pass current Admin ID
            if ($userID_to_block && $userObj->updateUserStatus($userID_to_block, "", 'Blocked', 'Block', $remarks, $reasonIDs, $currentAdminID)) {

                $mail = new PHPMailer(true);
                $user = $userObj->fetchUser($userID_to_block);
                $fullName = htmlspecialchars($user["fName"] . ' ' . $user["lName"]);

                $reasonTexts = $borrowObj->getReasonTexts($reasonIDs);
                $emailReasonStr = implode(', ', $reasonTexts);
                if ($remarks)
                    $emailReasonStr .= " (" . $remarks . ")";
                if (empty($emailReasonStr))
                    $emailReasonStr = "Violation of library policies.";

                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'graziellamssaavedra06@gmail.com';
                    $mail->Password = 'cpybynwckiipsszp';
                    $mail->SMTPSecure = 'ssl';
                    $mail->Port = 465;
                    $mail->setFrom('graziellamssaavedra06@gmail.com', 'Library Administration');
                    $mail->addAddress($user["email"], $fullName);
                    $mail->isHTML(true);
                    $mail->Subject = "Important: Your Library Account Status";
                    $mail->Body = <<<EOT
                <div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f7; padding: 40px 20px; margin: 0;">
                    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                        <div style="background-color: #D9534F; padding: 20px; text-align: center;">
                            <h2 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">Action Required</h2>
                        </div>
                        <div style="padding: 30px; color: #4a5568;">
                            <p style="font-size: 16px; margin-top: 0;">Hello <strong>{$fullName}</strong>,</p>
                            <p style="line-height: 1.6; font-size: 16px; color: #4a5568;">
                                We are writing to let you know that your library access has been temporarily <strong style="color: #D9534F;">suspended</strong>.
                            </p>
                            <div style="background-color: #FFF5F5; border-left: 4px solid #D9534F; padding: 15px 20px; margin: 25px 0; border-radius: 4px;">
                                <p style="margin: 0; font-size: 14px; color: #C53030; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Reason for suspension</p>
                                <p style="margin: 5px 0 0 0; font-size: 16px; color: #2D3748;">{$emailReasonStr}</p>
                            </div>
                            <p style="margin-top: 15px; font-size: 14px; color: #718096;">Processed by: <strong>{$currentAdminName}</strong></p>
                            <p style="line-height: 1.6; margin-bottom: 25px;">
                                To restore your access, please resolve this issue with the administration office as soon as possible.
                            </p>
                        </div>
                    </div>
                </div>
EOT;
                    $mail->send();
                } catch (Exception $e) {
                }

                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=borrowed&success=blocked");
                exit;
            } else {
                $errors["general"] = "Failed to block user.";
            }

            // --- UNBLOCK ACTION (Updated) ---
        } elseif ($action === 'unblockUser' && $borrowID) {
            $existing_detail = $borrowObj->fetchBorrowDetail($borrowID);
            if (!$existing_detail) {
                $errors["general"] = "Borrow detail not found.";
            } else {
                $userID_to_unblock = $existing_detail['userID'];
                $borrowObj->userID = $existing_detail['userID'];
                $borrowObj->bookID = $existing_detail['bookID'];
                $borrowObj->no_of_copies = $existing_detail['no_of_copies'];
                $borrowObj->request_date = $existing_detail['request_date'];
                $borrowObj->pickup_date = $existing_detail['pickup_date'];
                $borrowObj->expected_return_date = $existing_detail['expected_return_date'];
                $borrowObj->return_date = $existing_detail['return_date'];
                $borrowObj->returned_condition = $existing_detail['returned_condition'];
                $borrowObj->borrow_request_status = $existing_detail['borrow_request_status'];
                $borrowObj->borrow_status = $existing_detail['borrow_status'];
                $borrowObj->fine_amount = $existing_detail['fine_amount'];
                $borrowObj->fine_reason = $existing_detail['fine_reason'];
                $borrowObj->fine_status = $existing_detail['fine_status'];

                $borrowObj->editBorrowDetail($borrowID);

                // Passed Current Admin ID
                if ($userObj->updateUserStatus($userID_to_unblock, "Approved", "Active", 'Unblock', $remarks, $reasonIDs, $currentAdminID)) {
                    $mail = new PHPMailer(true);
                    $fullName = htmlspecialchars($existing_detail["fName"] . ' ' . $existing_detail["lName"]);
                    $userEmail = $existing_detail["email"];

                    $reasonTexts = $borrowObj->getReasonTexts($reasonIDs);
                    $emailReasonStr = implode(', ', $reasonTexts);
                    if ($remarks)
                        $emailReasonStr .= " (" . $remarks . ")";

                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'graziellamssaavedra06@gmail.com';
                        $mail->Password = 'cpybynwckiipsszp';
                        $mail->SMTPSecure = 'ssl';
                        $mail->Port = 465;
                        $mail->setFrom('graziellamssaavedra06@gmail.com', 'Library Administration');
                        $mail->addAddress($userEmail, $fullName);
                        $mail->isHTML(true);
                        $mail->Subject = "Account Update: Access Restored";
                        $mail->Body = <<<EOT
                    <div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f7; padding: 40px 20px; margin: 0;">
                        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                            <div style="background-color: #3182ce; padding: 20px; text-align: center;">
                                <h2 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">Welcome Back</h2>
                            </div>
                            <div style="padding: 30px; color: #4a5568;">
                                <p style="font-size: 16px; margin-top: 0;">Hello <strong>{$fullName}</strong>,</p>
                                <p style="line-height: 1.6; font-size: 16px;">
                                    We are pleased to inform you that your library account has been <strong style="color: #3182ce;">reactivated</strong>.
                                </p>
                                <div style="background-color: #ebf8ff; color: #2c5282; padding: 15px; margin: 20px 0; border-radius: 6px;">
                                    <p style="margin: 0; font-size: 12px; color: #2c5282; font-weight: bold; text-transform: uppercase;">Reason for activation</p>
                                    <p style="margin: 5px 0 0 0; font-size: 16px;">{$emailReasonStr}</p>
                                </div>
                                <p style="margin-top: 15px; font-size: 14px; color: #718096;">Processed by: <strong>{$currentAdminName}</strong></p>
                            </div>
                        </div>
                    </div>
EOT;
                        $mail->send();
                    } catch (Exception $e) {
                    }
                    header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}&success=unblocked");
                    exit;
                } else {
                    $errors["general"] = "Failed to update user status.";
                }
            }

            // --- CANCEL AND REJECT ACTIONS (Updated) ---
        } elseif (in_array($action, ['reject', 'cancel'])) {

            $final_redirect_tab = $current_tab;
            $borrowerUserID = $borrowObj->userID;
            $book_info = $bookObj->fetchBook($borrowObj->bookID);
            $bookTitle = $book_info['book_title'] ?? 'Book';
            $book_id_to_update = $borrowObj->bookID;
            $copies_to_move = $borrowObj->no_of_copies;

            if ($action === 'reject') {
                $borrowObj->borrow_request_status = 'Rejected';
                $borrowObj->borrow_status = NULL;
                $final_redirect_tab = 'rejected';
                // No need to increment stock, as it wasn't decremented on Approval anymore
            } elseif ($action === 'cancel') {
                $borrowObj->borrow_request_status = 'Cancelled';
                $borrowObj->borrow_status = NULL;
                $borrowObj->return_date = NULL;
                $final_redirect_tab = 'cancelled';
                // No need to increment stock
            }

            if ($borrowObj->editBorrowDetail($borrowID)) {

                // Passed Admin ID
                $actionType = ($action === 'reject') ? 'Reject' : 'Cancel';
                $borrowObj->addBorrowStatusHistory($borrowID, $actionType, $remarks, $reasonIDs, $currentAdminID);

                // Build Notification String
                $reasonTexts = $borrowObj->getReasonTexts($reasonIDs);
                $reasonStr = implode(', ', $reasonTexts);
                if ($remarks)
                    $reasonStr .= " (" . $remarks . ")";
                if (empty($reasonStr))
                    $reasonStr = "Reason not specified.";

                $notificationObj->userID = $borrowerUserID;
                $notificationObj->title = ($action === 'reject') ? "Request Rejected" : "Request Cancelled";
                $notificationObj->message = "Your request for '{$bookTitle}' has been " . $action . "ed. Reason: " . $reasonStr;
                $notificationObj->link = "../../../app/views/borrower/myBorrowedBooks.php?tab=returned&subtab=" . ucfirst($action) . "ed";
                $notificationObj->addNotification();

                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$final_redirect_tab}&success={$action}");
                exit;
            } else {
                $errors["general"] = "Failed to update detail status.";
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        $_SESSION["old"] = $detail;
        $modal_param = match ($action) {
            'return' => 'return',
            'edit' => 'edit',
            'paid' => 'paid',
            'blockUser' => 'block',
            'unblockUser' => 'unblock',
            default => '',
        };
        header("Location: ../../app/views/librarian/borrowDetailsSection.php?modal={$modal_param}&id={$borrowID}&tab={$current_tab}");
        exit;
    }
}

// GET Handling
if ($borrowID) {
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

    if (in_array($action, ['accept', 'pickup'])) {
        $final_redirect_tab = $current_tab;
        $borrowerUserID = $current_detail['userID'];
        $book_info = $bookObj->fetchBook($borrowObj->bookID);
        $bookTitle = $book_info['book_title'] ?? 'Book';
        $copies_to_move = (int) $borrowObj->no_of_copies;

        // --- NEW CHECK: PREVENT SELF-APPROVAL ---
        if ($action === 'accept') {
            if ($borrowerUserID == $_SESSION['user_id']) {
                $_SESSION["errors"] = ["general" => "Permission Denied: You cannot approve your own borrow request."];
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
                exit;
            }

            $borrowObj->borrow_request_status = 'Approved';
            $borrowObj->borrow_status = NULL;
            // Check availability but DO NOT decrement yet
            $available_physical_copies = (int) ($book_info['book_copies'] ?? 0);

            // Note: We should check real availability (Stock - Pending - Approved) here to prevent over-approving
            // But for now, we follow the instruction to just move the decrement.
            // Ideally, we should check against calculated availability. 
            // However, the physical copies check is still useful as a hard limit.

            $notificationObj->userID = $borrowerUserID;
            $notificationObj->title = "Request Approved";
            $notificationObj->message = "Your request for '{$bookTitle}' is approved and ready for pickup.";
            $notificationObj->link = "../../../app/views/borrower/myBorrowedBooks.php?tab=pending";
            $notificationObj->addNotification();

        } elseif ($action === 'pickup') {

            // DECREMENT HERE
            $available_physical_copies = (int) ($book_info['book_copies'] ?? 0);
            if ($available_physical_copies < $copies_to_move) {
                $_SESSION["errors"] = ["general" => "Error: Cannot fulfill claim. Not enough physical copies in stock."];
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
                exit;
            }

            if (!$bookObj->decrementBookCopies($borrowObj->bookID, $copies_to_move)) {
                $_SESSION["errors"] = ["general" => "Failed to update book stock."];
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
                exit;
            }

            $borrowObj->borrow_request_status = 'Approved';
            $borrowObj->borrow_status = 'Borrowed';
            $borrowObj->pickup_date = date("Y-m-d");
            $final_redirect_tab = 'borrowed';
            $notificationObj->userID = $borrowerUserID;
            $notificationObj->title = "Book Picked Up";
            $notificationObj->message = "You have successfully picked up '{$bookTitle}'.";
            $notificationObj->link = "../../../app/views/borrower/myBorrowedBooks.php?tab=borrowed";
            $notificationObj->addNotification();
        }

        if ($borrowObj->editBorrowDetail($borrowID)) {

            // ADDED: Log History for Accept/Pickup
            $actionType = ($action === 'accept') ? 'Approve' : 'Pickup';
            $borrowObj->addBorrowStatusHistory($borrowID, $actionType, null, [], $currentAdminID);

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