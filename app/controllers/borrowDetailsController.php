<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once(__DIR__ . "/../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../models/manageBook.php");
require_once(__DIR__ . "/../models/manageUsers.php");
require_once(__DIR__ . "/../models/manageNotifications.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//required files
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

    // Fetch existing details for edit/return/paid actions to carry over bookID/userID if not posted
    if ($action === 'edit' || $action === 'return' || $action === 'paid') {
        $current_detail = $borrowObj->fetchBorrowDetail($borrowID);
        if ($current_detail) {
            $detail['userID'] = $detail['userID'] ?: $current_detail['userID'];
            $detail['bookID'] = $detail['bookID'] ?: $current_detail['bookID'];
        }
    }

    // Validation
    // Only require userID and bookID for non-return/paid/edit actions (like a new borrow request)
    if (empty($detail['userID']) && $action !== 'return' && $action !== 'paid' && $action !== 'edit') {
        $errors['userID'] = "User ID is required.";
    }
    if (empty($detail['bookID']) && $action !== 'return' && $action !== 'paid' && $action !== 'edit') {
        $errors['bookID'] = "Book ID is required.";
    }
    if (empty($detail['expected_return_date']) && $action !== 'return' && $action !== 'paid') {
        $errors['expected_return_date'] = "Expected Return Date is required.";
    }
    if ($detail['fine_amount'] < 0) {
        $errors['fine_amount'] = "Fine amount cannot be negative.";
    }
    if ($detail['no_of_copies'] < 1 && $action !== 'return' && $action !== 'paid') {
        $errors['no_of_copies'] = "At least one copy must be requested.";
    }
    // Only require returned_condition for return or paid actions
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

        if ($action === 'edit' && $borrowID) {

            // $current_detail was fetched earlier, ensure it's available
            if (!isset($current_detail) || !$current_detail) {
                $current_detail = $borrowObj->fetchBorrowDetail($borrowID);
            }

            $comparison_date = $borrowObj->return_date ?: date("Y-m-d");

            if (
                $borrowObj->fine_amount <= 0.01 &&
                $borrowObj->expected_return_date
            ) {

                // Use the dates from the posted detail for recalculation
                $fine_results = $borrowObj->calculateFinalFine(
                    $borrowObj->expected_return_date,
                    $comparison_date, // Use the determined comparison date
                    $bookObj,
                    $detail['bookID'] // Use the determined bookID
                );

                // Update detail object with calculated fine results
                $borrowObj->fine_amount = $fine_results['fine_amount'];
                $borrowObj->fine_reason = $fine_results['fine_reason'];
                // Ensure fine status is 'Unpaid' if a new late fine is calculated (only if fine > 0)
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

        } elseif ($action === 'return' && $borrowID) {
            // Logic for 'return' remains the same (always calculates fine)
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

                // $mail = new PHPMailer(true);
                // $user = $userObj->fetchUser($current_detail['userID']);

                // //Server settings
                // $mail->isSMTP();                              //Send using SMTP
                // $mail->Host = 'smtp.gmail.com';       //Set the SMTP server to send through
                // $mail->SMTPAuth = true;             //Enable SMTP authentication
                // $mail->Username = 'graziellamssaavedra06@gmail.com';   //SMTP write your email
                // $mail->Password = 'cpybynwckiipsszp';      //SMTP password
                // $mail->SMTPSecure = 'ssl';            //Enable implicit SSL encryption
                // $mail->Port = 465;

                // //Recipients
                // $mail->setFrom('graziellamssaavedra06@gmail.com');     //Add a recipient email  
                // $mail->addAddress($user["email"], $user["fName"] . ' ' . $user["lName"]); // Sender Email and name
                // $mail->addReplyTo('graziellamssaavedra06@gmail.com'); // reply to sender email

                // //Content
                // $mail->isHTML(true);               //Set email format to HTML
                // $mail->Subject = "Book Returned";   // email subject headings
                // $mail->Body = "Book Successfully Returned! "; //email message

                // // Success sent message alert
                // $mail->send();
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=returned&success=returned");
                exit;
            } else {
                $errors["general"] = $errors["general"] ?? "Failed to complete book return process.";
            }
        } elseif ($action === 'paid' && $borrowID) {
            // Logic for 'paid' remains the same
            if (!isset($current_detail) || !$current_detail) {
                $errors['general'] = "Cannot find loan detail to process payment.";
            } else {
                $borrowObj->userID = $current_detail['userID'];
                $borrowObj->bookID = $current_detail['bookID'];
                $borrowObj->no_of_copies = $current_detail['no_of_copies'];
                $borrowObj->pickup_date = $current_detail['pickup_date'];
                $borrowObj->expected_return_date = $current_detail['expected_return_date'];
                // Carry over the current fine amount/reason
                $borrowObj->fine_amount = $current_detail['fine_amount'];
                $borrowObj->fine_reason = $current_detail['fine_reason'];
            }

            if (empty($errors)) {
                $borrowObj->return_date = date("Y-m-d");
                $borrowObj->borrow_request_status = NULL;
                $borrowObj->borrow_status = 'Returned';

                // Fine status is explicitly set to Paid when using the 'paid' action
                $borrowObj->fine_status = 'Paid';
                $borrowObj->returned_condition = $detail['returned_condition']; // Use posted condition

                // Only increment copies if the book wasn't returned already
                if ($current_detail['borrow_status'] !== 'Returned') {
                    if (!$bookObj->incrementBookCopies($borrowObj->bookID, $borrowObj->no_of_copies)) {
                        $errors["general"] = "Failed to update book stock (increment).";
                    }
                }
            }

            if (empty($errors) && $borrowObj->editBorrowDetail($borrowID)) {

                // $mail = new PHPMailer(true);
                // $user = $userObj->fetchUser($current_detail['userID']);

                // //Server settings
                // $mail->isSMTP();                              //Send using SMTP
                // $mail->Host = 'smtp.gmail.com';       //Set the SMTP server to send through
                // $mail->SMTPAuth = true;             //Enable SMTP authentication
                // $mail->Username = 'graziellamssaavedra06@gmail.com';   //SMTP write your email
                // $mail->Password = 'cpybynwckiipsszp';      //SMTP password
                // $mail->SMTPSecure = 'ssl';            //Enable implicit SSL encryption
                // $mail->Port = 465;

                // //Recipients
                // $mail->setFrom('graziellamssaavedra06@gmail.com');     //Add a recipient email  
                // $mail->addAddress($user["email"], $user["fName"] . ' ' . $user["lName"]); // Sender Email and name
                // $mail->addReplyTo('graziellamssaavedra06@gmail.com'); // reply to sender email

                // //Content
                // $mail->isHTML(true);               //Set email format to HTML
                // $mail->Subject = "Book Fine Paid";   // email subject headings
                // $mail->Body = "Thank You! "; //email message

                // // Success sent message alert
                // $mail->send();
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
            'paid' => 'paid',
            default => '',
        };

        header("Location: ../../app/views/librarian/borrowDetailsSection.php?modal={$modal_param}&id={$borrowID}&tab={$current_tab}");
        exit;
    }

}

if ($borrowID) {
    if ($action === 'blockUser') {
        // Fetch the loan details to get the correct User ID
        $current_detail = $borrowObj->fetchBorrowDetail($borrowID);
        $userID_to_block = $current_detail['userID'] ?? null;

        if ($userID_to_block && $userObj->updateUserStatus($userID_to_block, "", 'Blocked')) {

            $mail = new PHPMailer(true);
            $user = $userObj->fetchUser($current_detail['userID']);

            //Server settings
            $mail->isSMTP();                              //Send using SMTP
            $mail->Host = 'smtp.gmail.com';       //Set the SMTP server to send through
            $mail->SMTPAuth = true;             //Enable SMTP authentication
            $mail->Username = 'graziellamssaavedra06@gmail.com';   //SMTP write your email
            $mail->Password = 'cpybynwckiipsszp';      //SMTP password
            $mail->SMTPSecure = 'ssl';            //Enable implicit SSL encryption
            $mail->Port = 465;

            //Recipients
            $mail->setFrom('graziellamssaavedra06@gmail.com');     //Add a recipient email  
            $mail->addAddress($user["email"], $user["fName"] . ' ' . $user["lName"]); // Sender Email and name
            $mail->addReplyTo('graziellamssaavedra06@gmail.com'); // reply to sender email

            //Content
            $mail->isHTML(true);               //Set email format to HTML
            $mail->Subject = "Your Account Was Blocked";   // email subject headings
            $mail->Body = "Blocked! "; //email message

            // Success sent message alert
            $mail->send();
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=borrowed&success=blocked");
            exit;
        } else {
            $_SESSION["errors"] = ["general" => "Failed to block user. User ID not found or database error."];
            header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab=currently_borrowed");
            exit;
        }
    }

    // Fetch detail again for other actions if 'blockUser' didn't exit
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

    if (in_array($action, ['accept', 'reject', 'pickup', 'cancel'])) {
        $final_redirect_tab = $current_tab;

        if ($action === 'accept') {
            $borrowObj->borrow_request_status = 'Approved';
            $borrowObj->borrow_status = NULL;

            $book_info = $bookObj->fetchBook($book_id_to_update);
            $available_physical_copies = (int) ($book_info['book_copies'] ?? 0);

            if ($available_physical_copies < $copies_to_move) {
                $_SESSION["errors"] = ["general" => "Error: Cannot fulfill claim (BorrowID {$borrowID}). Only {$available_physical_copies} copies remaining, but {$copies_to_move} are requested. Reject this request manually."];
                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
                exit;
            }
            if (!$bookObj->decrementBookCopies($book_id_to_update, $copies_to_move)) {
                $_SESSION["errors"] = ["general" => "Failed to update book stock (decrement)."];
                $notificationObj->userID = $borrowerUserID;
                $notificationObj->title = "Request Approved";
                $notificationObj->message = "Your request for '{$bookTitle}' is approved and ready for pickup.";
                $notificationObj->link = "../../app/views/borrower/myBorrowedBooks.php?tab=pending";
                $notificationObj->addNotification();

                header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
                exit;
            }


        } elseif ($action === 'reject') {
            $borrowObj->borrow_request_status = 'Rejected';
            $borrowObj->borrow_status = NULL;
            $final_redirect_tab = 'rejected';

            if ($current_detail['borrow_request_status'] === 'Approved' || $current_detail['borrow_request_status'] === 'Pending') {
                if (!$bookObj->incrementBookCopies($book_id_to_update, $copies_to_move)) {
                    $_SESSION["errors"] = ["general" => "Failed to update book stock (increment) on admin reject."];

                    $notificationObj->userID = $borrowerUserID;
                    $notificationObj->title = "Request Rejected";
                    $notificationObj->message = "Your request for '{$bookTitle}' has been rejected.";
                    $notificationObj->link = "../../app/views/borrower/myBorrowedBooks.php?tab=returned&subtab=Rejected";
                    $notificationObj->addNotification();

                    header("Location: ../../app/views/librarian/borrowDetailsSection.php?tab={$current_tab}");
                    exit;
                }
            }

        } elseif ($action === 'pickup') {
            $borrowObj->borrow_request_status = 'Approved';
            $borrowObj->borrow_status = 'Borrowed';
            $borrowObj->pickup_date = date("Y-m-d");
            $final_redirect_tab = 'borrowed';


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