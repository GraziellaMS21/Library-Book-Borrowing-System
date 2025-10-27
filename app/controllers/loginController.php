<?php
session_start();
require_once(__DIR__ . '/../models/userLogin.php');

$loginObj = new Login($email, $password);

$login = [];
$errors = [];

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
        $result = $loginObj->logIn($login["email"], $login["password"]);

        if (is_array($result)) {
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
            } elseif ($result["user_status"] === "Blocked") {
                header("Location: ../../app/views/borrower/catalogue.php");
            } else {
                $errors["invalid"] = "Invalid User.";
            }
        } else {
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