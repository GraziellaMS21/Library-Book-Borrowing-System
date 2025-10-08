<?php
session_start();
require_once(__DIR__ . '/../models/userLogin.php');

$loginObj = new Login();
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim(htmlspecialchars($_POST["email"] ?? ""));
    $password = trim(htmlspecialchars($_POST["password"] ?? ""));

    if (empty($email)) $errors["email"] = "Please input your email address.";
    if (empty($password)) $errors["password"] = "Please input your password.";

    if (empty($errors)) {
        $result = $loginObj->logIn($email, $password);

        if (is_array($result)) {
            $_SESSION["user_id"] = $result["userID"];
            $_SESSION["email"] = $result["email"];
            $_SESSION["lName"] = $result["lName"];
            $_SESSION["fName"] = $result["fName"];
            $_SESSION["borrowerTypeID"] = $result["borrowerTypeID"];

            // 🔍 Optional check
            // var_dump($result["borrowerTypeID"]); exit;

            if ($result["borrowerTypeID"] == 2) {
                header("Location: ../../app/views/librarian/dashboard.php");
                exit;
            } elseif ($result["borrowerTypeID"] == 1) {
                header("Location: ../../app/views/borrower/catalogue.php");
                exit;
            } else {
                $errors["invalid"] = "Invalid borrower type.";
            }
        } else {
            $errors["invalid"] = $result;
        }
    }

    $_SESSION["errors"] = $errors;
    header("Location: ../../app/views/borrower/login.php");
    exit;
}
?>
