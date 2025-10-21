<?php 
session_start();
require_once(__DIR__ . "/../models/manageBorrowDetails.php");
$detailObj = new BorrowDetails();
$detail = [];
$errors = [];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect_url = "../../app/views/librarian/borrowDetailsSection.php"; 

$borrowID = $_POST["borrowID"] ?? $_GET["id"] ?? null;

// Helper function for quick field validation
function validateField($key, &$detail, &$errors, $required = true, $numeric = false) {
    if (isset($_POST[$key])) {
        $detail[$key] = trim(htmlspecialchars($_POST[$key]));
    } else {
        $detail[$key] = '';
    }

    if ($required && empty($detail[$key]) && $key !== 'penaltyID' && $key !== 'return_date' && $key !== 'returned_condition' && $key !== 'pickup_date') {
        $errors[$key] = ucfirst(str_replace('_', ' ', $key)) . " is required.";
    } elseif ($numeric && !is_numeric($detail[$key]) && !empty($detail[$key])) {
         $errors[$key] = "Invalid format for " . str_replace('_', ' ', $key) . ".";
    }
}


switch ($action) {
    case 'add':
        // 1. Data Collection and Sanitization
        validateField('userID', $detail, $errors, true, true);
        validateField('bookID', $detail, $errors, true, true);
        validateField('borrow_date', $detail, $errors, true);
        validateField('pickup_date', $detail, $errors, false);
        validateField('return_date', $detail, $errors, false);
        validateField('returned_condition', $detail, $errors, false);
        validateField('borrow_request_status', $detail, $errors, true);
        validateField('penaltyID', $detail, $errors, false, true); // penaltyID can be empty/null
        validateField('book_status', $detail, $errors, true); 

        // 2. Custom Logic/Validation (e.g., date formats, status checks)
        // (Assuming simple presence checks are sufficient for IDs and status)

        // 3. Process
        if (empty(array_filter($errors))) {
            $detailObj->userID = $detail["userID"];
            $detailObj->bookID = $detail["bookID"];
            $detailObj->borrow_date = $detail["borrow_date"];
            $detailObj->pickup_date = $detail["pickup_date"];
            $detailObj->return_date = $detail["return_date"];
            $detailObj->returned_condition = $detail["returned_condition"];
            $detailObj->borrow_request_status = $detail["borrow_request_status"];
            $detailObj->penaltyID = !empty($detail["penaltyID"]) ? $detail["penaltyID"] : null; // Handle optional null
            $detailObj->book_status = $detail["book_status"];
            
            if ($detailObj->addBorrowDetail()) {
                header("Location: $redirect_url");
                exit;
            } else {
                $_SESSION["errors"] = ["general" => "Failed to add borrow detail due to a database error."];
                $_SESSION['open_modal'] = 'addBorrowDetailModal';
                $_SESSION["old"] = $detail;
                header("Location: $redirect_url");
                exit;
            }
        } else {
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $detail;
            $_SESSION['open_modal'] = 'addBorrowDetailModal';
            header("Location: $redirect_url"); 
            exit;
        }
        
    case 'edit':
        $borrowID = $_POST["borrowID"] ?? $_GET["id"];
        // 1. Data Collection and Sanitization
        validateField('userID', $detail, $errors, true, true);
        validateField('bookID', $detail, $errors, true, true);
        validateField('borrow_date', $detail, $errors, true);
        validateField('pickup_date', $detail, $errors, false);
        validateField('return_date', $detail, $errors, false);
        validateField('returned_condition', $detail, $errors, false);
        validateField('borrow_request_status', $detail, $errors, true);
        validateField('penaltyID', $detail, $errors, false, true);
        validateField('book_status', $detail, $errors, true); 
        
        // 2. Custom Logic/Validation

        // 3. Process
        if (empty(array_filter($errors))) {
            $detailObj->userID = $detail["userID"];
            $detailObj->bookID = $detail["bookID"];
            $detailObj->borrow_date = $detail["borrow_date"];
            $detailObj->pickup_date = $detail["pickup_date"];
            $detailObj->return_date = $detail["return_date"];
            $detailObj->returned_condition = $detail["returned_condition"];
            $detailObj->borrow_request_status = $detail["borrow_request_status"];
            $detailObj->penaltyID = !empty($detail["penaltyID"]) ? $detail["penaltyID"] : null;
            $detailObj->book_status = $detail["book_status"];
            
            if ($detailObj->editBorrowDetail($borrowID)) {
                header("Location: $redirect_url");
                exit;
            } else {
                $_SESSION["errors"] = ["general" => "Failed to update borrow detail due to a database error."];
                $_SESSION['open_modal'] = 'editBorrowDetailModal';
                $_SESSION['edit_borrow_id'] = $borrowID;
                $_SESSION["old"] = $detail;
                header("Location: $redirect_url");
                exit;
            }
        } else {
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $detail;
            $_SESSION['open_modal'] = 'editBorrowDetailModal';
            $_SESSION['edit_borrow_id'] = $borrowID; 
            header("Location: $redirect_url");
            exit;
        }
        
    case 'delete':
        if (isset($_GET['id'])) {
            $borrowID = $_GET['id'];
            if ($detailObj->deleteBorrowDetail($borrowID)) {
                header("Location: $redirect_url");
                exit;
            } else {
                $_SESSION["errors"] = ["general" => "Failed to delete borrow detail."];
                header("Location: $redirect_url");
                exit;
            }
        } else {
            $_SESSION["errors"] = ["general" => "No Borrow ID provided for deletion."];
            header("Location: $redirect_url");
            exit;
        }
} 
?>
