<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once(__DIR__ . "/../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../models/manageBook.php");
require_once(__DIR__ . "/../models/manageUsers.php");
require_once(__DIR__ . "/../models/manageNotifications.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Required files for PHPMailer
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

// ---------------------------------------------------------
//  1. PROCESS STATUS REASON (Checkboxes + Textarea)
// ---------------------------------------------------------
$status_reason_str = NULL;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reasons = [];
    
    // Capture Checkboxes
    if (isset($_POST['reason_presets']) && is_array($_POST['reason_presets'])) {
        foreach($_POST['reason_presets'] as $preset) {
            $reasons[] = htmlspecialchars($preset);
        }
    }
    
    // Capture Custom Textarea
    if (!empty($_POST['reason_custom'])) {
        $reasons[] = htmlspecialchars(trim($_POST['reason_custom']));
    }

    // Combine into one string (e.g., "Unpaid Fines; Other notes...")
    if (!empty($reasons)) {
        $status_reason_str = implode("; ", $reasons);
    }
}

// ---------------------------------------------------------
//  POST REQUEST HANDLING
// ---------------------------------------------------------
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
    
    // Assign the processed reason string to the data array
    $detail['status_reason'] = $status_reason_str;

    // Fetch existing details for actions that might not submit all fields (to prevent overwriting with blanks)
    if (in_array($action, ['edit', 'return', 'paid', 'reject', 'cancel', 'blockUser'])) {
        $current_detail = $borrowObj->fetchBorrowDetail($borrowID);
        if ($current_detail) {
            $detail['userID'] = $detail['userID'] ?: $current_detail['userID'];
            $detail['bookID'] = $detail['bookID'] ?: $current_detail['bookID'];
            // If no new reason was provided in this POST, keep the old one
            $detail['status_reason'] = $detail['status_reason'] ?: $current_detail['status_reason'];
        }
    }

    // Validations
    if (empty($detail['userID']) && !in_array($action, ['return', 'paid', 'edit', 'reject', 'cancel', 'blockUser'])) {
        $errors['userID'] = "User ID is required.";
    }
    if (empty($detail['bookID']) && !in_array($action, ['return', 'paid', 'edit', 'reject', 'cancel', 'blockUser'])) {
        $errors['bookID'] = "Book ID is required.";
    }
    if (empty($detail['expected_return_date']) && !in_array($action, ['return', 'paid', 'reject', 'cancel', 'blockUser'])) {
        $errors['expected_return_date'] = "Expected Return Date is required.";
    }
    if ($detail['fine_amount'] < 0) {
        $errors['fine_amount'] = "Fine amount cannot be negative.";
    }
    if ($detail['no_of_copies'] < 1 && !in_array($action, ['return', 'paid', 'reject', 'cancel', 'blockUser'])) {
        $errors['no_of_copies'] = "At least one copy must be requested.";
    }
    if (($action === 'return' || $action === 'paid') && empty($detail['returned_condition'])) {
        $errors['returned_condition'] = "Returned condition is required to complete the return/payment.";
    }

    if (empty(array_filter($errors))) {
        // Map form data to object properties
        foreach ($detail as $key => $value) {
            if (property_exists($borrowObj, $key)) {
                $borrowObj->$key = $value;
            }
        }

        // --- EDIT ACTION ---
        if ($action === 'edit' && $borrowID) {
            if (!isset($current_detail) || !$current_detail) {
                $current_detail = $borrowObj->fetchBorrowDetail($borrowID);
            }

            $comparison_date = $borrowObj->return_date ?: date("Y-m-d");

            // Auto-calculate fines if editing dates
            if ($borrowObj->fine_amount <= 0.01 && $borrowObj->expected_return_date) {
                $fine_results = $borrowObj->calculateFinalFine(
                    $borrowObj->expected_return_date,
                    $comparison_date,
                    $bookObj,
                    $detail['bookID']
                );
                $borrowObj->fine_amount = $fine_results['fine_amount'];
                $borrowObj->fine_reason = $fine_results['fine_reason'];
                $borrowObj->fine_status = ($fine_results['fine_amount'] > 0) ? 'Unpaid' : ($borrowObj->fine_status ?: NULL);
            }

            if ($borrowObj->fine_amount <= 0.00) {
                $borrowObj->fine_reason = NULL;
                $borrowObj->fine_status = NULL;
            }

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

                $fine_results = $borrowObj->calculateFinalFine(
                    $borrowObj->expected_return_date,
                    $borrowObj->return_date,
                    $bookObj,
                    $borrowObj->bookID
                );

                $borrowObj->fine_amount = $fine_results['fine_amount'];
                $borrowObj->fine_reason = $fine_results['fine_reason'];
                $borrowObj->fine_status = $fine_results['fine_status'];
                $borrowObj->returned_condition = $detail['returned_condition'];

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
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=returned&success=returned");
                exit;
            } else {
                $errors["general"] = $errors["general"] ?? "Failed to complete book return process.";
            }
        
        // --- BLOCK USER ACTION ---
        } elseif ($action === 'blockUser' && $borrowID) {
            $userID_to_block = $borrowObj->userID; 

            // 1. Save the status reason to the borrow detail first
            $borrowObj->status_reason = $status_reason_str; 
            $borrowObj->editBorrowDetail($borrowID); 

            if ($userID_to_block && $userObj->updateUserStatus($userID_to_block, "", 'Blocked')) {

                // 2. Prepare Email with Reason
                $mail = new PHPMailer(true);
                $user = $userObj->fetchUser($userID_to_block);
                $fullName = htmlspecialchars($user["fName"] . ' ' . $user["lName"]);
                
                // Use the processed reason or a default
                $reasonForBlock = $status_reason_str ?: "Violation of library policies, overdue books, or unpaid fines.";

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

                    // Inject reason into Email Body
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
                                <p style="margin: 5px 0 0 0; font-size: 16px; color: #2D3748;">{$status_reason}</p>
                            </div>
                            <p style="line-height: 1.6; margin-bottom: 25px;">
                                To restore your access, please resolve this issue with the administration office as soon as possible.
                            </p>
                            <div style="text-align: center; margin-bottom: 20px;">
                                <a href="#" style="background-color: #2D3748; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; display: inline-block;">Contact Support</a>
                            </div>
                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;">
                            <p style="font-size: 12px; color: #a0aec0; text-align: center;">
                                This is an automated message from the Library System.
                            </p>
                        </div>
                    </div>
                </div>
EOT;
                    $mail->AltBody = "Dear {$fullName},\n\nYour account has been BLOCKED.\nReason: {$reasonForBlock}\n\nPlease contact the library administration.";

                    $mail->send();
                } catch (Exception $e) { 
                    // Log mail error if needed
                }

                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=borrowed&success=blocked");
                exit;
            } else {
                $errors["general"] = "Failed to block user.";
            }

        // --- CANCEL AND REJECT ACTIONS ---
        } elseif (in_array($action, ['reject', 'cancel'])) {
            
            $final_redirect_tab = $current_tab;
            $borrowerUserID = $borrowObj->userID;
            $book_info = $bookObj->fetchBook($borrowObj->bookID);
            $bookTitle = $book_info['book_title'] ?? 'Book';
            $book_id_to_update = $borrowObj->bookID;
            $copies_to_move = $borrowObj->no_of_copies;

            // 1. Ensure reason is saved to object
            $borrowObj->status_reason = $status_reason_str; 

            if ($action === 'reject') {
                $borrowObj->borrow_request_status = 'Rejected';
                $borrowObj->borrow_status = NULL;
                $final_redirect_tab = 'rejected';

                // Return stock if it was previously reserved
                if ($current_detail['borrow_request_status'] === 'Approved') {
                     $bookObj->incrementBookCopies($book_id_to_update, $copies_to_move);
                }

                // 2. Reject Notification with Reason
                $notificationObj->userID = $borrowerUserID;
                $notificationObj->title = "Request Rejected";
                $notificationObj->message = "Your request for '{$bookTitle}' has been rejected. Reason: " . ($status_reason_str ?: "Reason not specified");
                $notificationObj->link = "../../../app/views/borrower/myBorrowedBooks.php?tab=returned&subtab=Rejected";
                $notificationObj->addNotification();

            } elseif ($action === 'cancel') {
                $borrowObj->borrow_request_status = 'Cancelled';
                $borrowObj->borrow_status = NULL;
                $borrowObj->return_date = NULL;
                $final_redirect_tab = 'cancelled';

                // Restock
                if ($current_detail['borrow_request_status'] === 'Approved' && $current_detail['borrow_status'] !== 'Borrowed') {
                    $bookObj->incrementBookCopies($book_id_to_update, $copies_to_move);
                }

                // 3. Cancel Notification with Reason
                $notificationObj->userID = $borrowerUserID;
                $notificationObj->title = "Request Cancelled";
                $notificationObj->message = "Your request for '{$bookTitle}' has been cancelled by the librarian. Reason: " . ($status_reason_str ?: "Reason not specified");
                $notificationObj->link = "../../../app/views/borrower/myBorrowedBooks.php?tab=returned&subtab=Cancelled";
                $notificationObj->addNotification();
            }

            if ($borrowObj->editBorrowDetail($borrowID)) {
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
            default => '', 
        };

        header("Location: ../../app/views/librarian/borrowDetailsSection.php?modal={$modal_param}&id={$borrowID}&tab={$current_tab}");
        exit;
    }
}

// ---------------------------------------------------------
//  GET REQUEST HANDLING (Accept, Pickup, Delete)
// ---------------------------------------------------------
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
        $copies_to_move = (int)$borrowObj->no_of_copies;

        if ($action === 'accept') {
            $borrowObj->borrow_request_status = 'Approved';
            $borrowObj->borrow_status = NULL;

            $available_physical_copies = (int) ($book_info['book_copies'] ?? 0);

            if ($available_physical_copies < $copies_to_move) {
                $_SESSION["errors"] = ["general" => "Error: Cannot fulfill claim (BorrowID {$borrowID}). Only {$available_physical_copies} copies remaining, but {$copies_to_move} are requested. Reject this request manually."];
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
                exit;
            }

            if (!$bookObj->decrementBookCopies($borrowObj->bookID, $copies_to_move)) {
                $_SESSION["errors"] = ["general" => "Failed to update book stock (decrement)."];
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
                exit;
            }

            $notificationObj->userID = $borrowerUserID;
            $notificationObj->title = "Request Approved";
            $notificationObj->message = "Your request for '{$bookTitle}' is approved and ready for pickup.";
            $notificationObj->link = "../../../app/views/borrower/myBorrowedBooks.php?tab=pending";
            $notificationObj->addNotification();

        } elseif ($action === 'pickup') {
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