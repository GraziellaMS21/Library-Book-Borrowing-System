<?php
session_start();
require_once(__DIR__ . '/../models/userLogin.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//required files
require_once __DIR__ . '/../libraries/phpmailer/src/Exception.php';
require_once __DIR__ . '/../libraries/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../libraries/phpmailer/src/SMTP.php';

$loginObj = new Login();

$login = [];
$errors = [];
$action = $_GET["action"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login["email"] = trim(htmlspecialchars($_POST["email"] ?? ""));
    $login["password"] = trim(htmlspecialchars($_POST["password"] ?? ""));

    if (empty($login["email"])) {
        $errors["email"] = "Please input your email address.";
    }

    if (empty($login["password"])) {
        $errors["password"] = "Please input your password.";
    }

    if (empty(array_filter($errors))) {
        $result = $loginObj->userLogIn($login["email"], $login["password"]);

        if (is_array($result)) {
            // User found and password correct. Now check status.
            if ($result["registration_status"] === "Pending") {
                header("Location: ../../app/views/borrower/login.php?status=pending");
                exit;
            } elseif ($result["registration_status"] === "Rejected") {
                header("Location: ../../app/views/borrower/login.php?status=rejected");
                exit;
            } elseif ($result["account_status"] === "Blocked") {
                // PASS THE USER ID VIA URL FOR THE LOGIN PAGE TO LOOK UP THE FINE STATUS
                header("Location: ../../app/views/borrower/login.php?status=blocked&userID=" . $result['userID']);
                exit;
            } elseif ($result["account_status"] === "Blocked") {
                // PASS THE USER ID VIA URL FOR THE LOGIN PAGE TO LOOK UP THE FINE STATUS
                header("Location: ../../app/views/borrower/login.php?status=blocked&userID=" . $result['userID']);
                exit;
            } elseif ($result["registration_status"] === "Approved") {
                $_SESSION["user_id"] = $result["userID"];
                $_SESSION["email"] = $result["email"];
                $_SESSION["lName"] = $result["lName"];
                $_SESSION["fName"] = $result["fName"];
                $_SESSION["userTypeID"] = $result["userTypeID"];

                if ($result["role"] === "Borrower") {
                    header("Location: ../../app/views/borrower/catalogue.php");
                    exit;
                } elseif ($result["role"] === "Admin") {
                    header("Location: ../../app/views/librarian/dashboard.php");
                    exit;
                }
            } else {
                $errors["invalid"] = "Invalid User Status. Please contact support.";
            }

        } else {
            // Error from logIn (Password invalid, Email not found, or DB error)
            $errors["invalid"] = $result;
        }
    }

    if (!empty($errors)) {
        $_SESSION["errors"] = $errors;
        header("Location: ../../app/views/borrower/login.php");
        exit;
    }
}
?>