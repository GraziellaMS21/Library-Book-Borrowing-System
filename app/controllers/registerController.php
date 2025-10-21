<?php
session_start();
require_once(__DIR__ . "/../models/userRegister.php");
$registerObj = new Register();

$register = [];
$errors = [];
$userTypes = $registerObj->fetchUserType();

// FIX 6: Enclosed all data collection within the POST block
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // FIX 7: Collected form data (non-file)
    $register["userTypeID"] = trim(htmlspecialchars($_POST["userTypeID"] ?? ''));
    $register["lName"] = trim(htmlspecialchars($_POST["lName"] ?? ''));
    $register["fName"] = trim(htmlspecialchars($_POST["fName"] ?? ''));
    $register["middleIn"] = trim(htmlspecialchars($_POST["middleIn"] ?? ''));
    $register["id_number"] = trim(htmlspecialchars($_POST["id_number"] ?? ''));
    $register["college_department"] = trim(htmlspecialchars($_POST["college_department"] ?? ''));
    $register["contact_no"] = trim(htmlspecialchars($_POST["contact_no"] ?? ''));
    $register["email"] = trim(htmlspecialchars($_POST["email"] ?? ''));
    $register["password"] = trim(htmlspecialchars($_POST["password"] ?? ''));
    $register["conPass"] = trim(htmlspecialchars($_POST["conPass"] ?? ''));
    $register["agreement"] = isset($_POST["agreement"]) ? trim(htmlspecialchars($_POST["agreement"])) : "";

    // FIX 8: Handled file input correctly
    $register["imageID_name"] = $_FILES["imageID"]["name"] ?? '';
    $upload_dir = __DIR__ . "/../../public/uploads/id_images/";
    
    // FIX 9: Correctly defined image directory using proper path concatenation
    $register["imageID_dir"] = $upload_dir . basename($register["imageID_name"]);

    
    if (empty($register["userTypeID"])) {
        $errors["userTypeID"] = "Please Choose From the Following";
    }


    if (empty($register["lName"])) {
        $errors["lName"] = "Last Name is required";
    }

    if (empty($register["fName"])) {
        $errors["fName"] = "First Name is required";
    }

    if (empty($register["id_number"])) {
        $errors["id_number"] = "ID Number is required";
    } elseif (!is_numeric($register["id_number"])) {
        $errors["id_number"] = "ID Number Format is Invalid";
    }

    if ($register["userTypeID"] == 1 || $register["userTypeID"] == 2) {
        if (empty($register["college_department"])) {
            $errors["college_department"] = "College/Department is required";
        }
    }

    if (empty($register["contact_no"])) {
        $errors["contact_no"] = "Contact Number is required";
    } elseif (!is_numeric($register["contact_no"]) || strlen($register["contact_no"]) != 11) {
        $errors["contact_no"] = "Contact Number Format is Invalid";
    }

    if (empty($register["email"])) {
        $errors["email"] = "Email is required";
    } else if ($registerObj->isEmailExist($register["email"])) {
        $errors["email"] = "Email already exist";
    }

    if (empty($register["password"])) {
        $errors["password"] = "Password is required";
    }

    if (empty($register["conPass"])) {
        $errors["conPass"] = "Please Confirm Your Password";
    } else if ($register["password"] !== $register["conPass"]) {
        $errors["conPass"] = "Passwords do not match";
    }

    if (empty($register["agreement"])) {
        $errors["agreement"] = "You must Agree to the Terms and Conditions";
    }
    
    // FIX 10: Added file upload validation
    if (empty($register["imageID_name"]) || $_FILES["imageID"]["error"] == UPLOAD_ERR_NO_FILE) {
        $errors["imageID"] = "Upload ID Image is required";
    } elseif ($_FILES["imageID"]["error"] !== UPLOAD_ERR_OK) {
        $errors["imageID"] = "File upload failed (Code: " . $_FILES["imageID"]["error"] . ")";
    }


    if (empty(array_filter($errors))) {
        
        if (move_uploaded_file($_FILES["imageID"]["tmp_name"], $register["imageID_dir"])) {
            
            $registerObj->userTypeID = $register["userTypeID"];
            $registerObj->lName = $register["lName"];
            $registerObj->fName = $register["fName"];
            
            $registerObj->middleIn = empty($register["middleIn"]) ? NULL : $register["middleIn"];
            $registerObj->id_number = $register["id_number"];
            
            // FIX 11: Correctly set college_department from $register array
            $registerObj->college_department = empty($register["college_department"]) ? NULL : $register["college_department"];
            
            $registerObj->imageID_name = $register["imageID_name"];
            // FIX 12: Use the relative path for DB storage
            $registerObj->imageID_dir = "public/uploads/id_images/" . basename($register["imageID_name"]);
            
            $registerObj->contact_no = $register["contact_no"];
            $registerObj->email = $register["email"];
            $registerObj->password = $register["password"];
            $registerObj->date_registered = date("Y-m-d");

            if ($registerObj->addUser()) {
                header("Location: ../../app/views/borrower/register.php?success=1");
                exit;
            } else {
                // DB insert failed: clean up the uploaded file
                if (file_exists($register["imageID_dir"])) {
                    unlink($register["imageID_dir"]);
                }
                // FIX 13: Change echo "FAILED" to redirection
                $_SESSION["errors"] = ["general" => "Registration failed due to a database error."];
                $_SESSION["old"] = $register;
                header("Location: ../../app/views/borrower/register.php");
                exit;
            }
        } else {
            // File move failed
            $errors["imageID"] = "Failed to save the uploaded image.";
        }
    }
    
    // Fallback if there are errors (or file move failed above)
    $_SESSION["errors"] = $errors;
    $_SESSION["old"] = $register;
    header("Location: ../../app/views/borrower/register.php");
    exit;
}
?>