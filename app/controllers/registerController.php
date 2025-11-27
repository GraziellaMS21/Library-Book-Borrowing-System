<?php
session_start();
require_once(__DIR__ . "/../models/userRegister.php");
$registerObj = new Register();

$register = [];
$errors = [];
$userTypes = $registerObj->fetchUserType();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $register["userTypeID"] = trim(htmlspecialchars($_POST["userTypeID"] ?? ''));
    $register["lName"] = trim(htmlspecialchars($_POST["lName"] ?? ''));
    $register["fName"] = trim(htmlspecialchars($_POST["fName"] ?? ''));
    $register["middleIn"] = trim(htmlspecialchars($_POST["middleIn"] ?? ''));
    $register["id_number"] = trim(htmlspecialchars($_POST["id_number"] ?? ''));
    $register["departmentID"] = trim(htmlspecialchars($_POST["departmentID"] ?? ''));
    $register["contact_no"] = trim(htmlspecialchars($_POST["contact_no"] ?? ''));
    $register["email"] = trim(htmlspecialchars($_POST["email"] ?? ''));
    $register["password"] = trim(htmlspecialchars($_POST["password"] ?? ''));
    $register["conPass"] = trim(htmlspecialchars($_POST["conPass"] ?? ''));
    $register["agreement"] = isset($_POST["agreement"]) ? trim(htmlspecialchars($_POST["agreement"])) : "";

    $register["imageID_name"] = $_FILES["imageID"]["name"] ?? '';
    $upload_dir = __DIR__ . "/../../public/uploads/id_images/";

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

    if ($register["userTypeID"] == 1) {
        if (empty($register["id_number"])) {
            $errors["id_number"] = "ID Number is required";
        } elseif (!is_numeric($register["id_number"])) {
            $errors["id_number"] = "ID Number Format is Invalid";
        }

        if (empty($register["departmentID"])) {
            $errors["departmentID"] = "College/Department is required";
        }
    }

    if ($register["userTypeID"] == 2) {
        if (empty($register["id_number"])) {
            $errors["id_number"] = "ID Number is required";
        } elseif (!is_numeric($register["id_number"])) {
            $errors["id_number"] = "ID Number Format is Invalid";
        }

        if (empty($register["departmentID"])) {
            $errors["departmentID"] = "College/Department is required";
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
            $registerObj->id_number = empty($register["id_number"]) ? NULL : $register["id_number"];
            $registerObj->departmentID = empty($register["departmentID"]) ? NULL : $register["departmentID"];

            $registerObj->imageID_name = $register["imageID_name"];
            $registerObj->imageID_dir = "public/uploads/id_images/" . basename($register["imageID_name"]);

            $registerObj->contact_no = $register["contact_no"];
            $registerObj->email = $register["email"];
            $registerObj->password = $register["password"];
            $registerObj->date_registered = date("Y-m-d");

            if ($registerObj->addUser()) {
                header("Location: ../../app/views/borrower/register.php?success=pending");
                exit;
            } else {
                if (file_exists($register["imageID_dir"])) {
                    unlink($register["imageID_dir"]);
                }
                $_SESSION["errors"] = ["general" => "Registration failed due to a database error."];
                $_SESSION["old"] = $register;
                header("Location: ../../app/views/borrower/register.php");
                exit;
            }
        } else {
            $errors["imageID"] = "Failed to save the uploaded image.";
        }
    }

    $_SESSION["errors"] = $errors;
    $_SESSION["old"] = $register;
    header("Location: ../../app/views/borrower/register.php");
    exit;
}
?>