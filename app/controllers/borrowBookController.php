<?php
session_start();

require_once(__DIR__ . "/../models/manageDetails.php");
$detailsObj = new Details();

$details = [];
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $details["bookID"] = trim(htmlspecialchars($_POST["bookID"]));
    $details["userID"] = trim(htmlspecialchars($_POST["userID"]));
    $details["pickup_date"] = trim(htmlspecialchars($_POST["pickup_date"]));
    $details["return_date"] = trim(htmlspecialchars($_POST["return_date"]));

    if (empty($details["bookID"])) {
        $errors["bookID"] = "Book ID is required.";
    }
    if (empty($details["userID"])) {
        $errors["userID"] = "User ID is required.";
    }
    if (empty($details["pickup_date"])) {
        $errors["pickup_date"] = "Pickup date is required.";
    }
    if (empty($details["return_date"])) {
        $errors["return_date"] = "Return date is required.";
    }
    if (!empty($details["pickup_date"]) && !empty($details["return_date"])) {
        if ($details["pickup_date"] > !empty($details["return_date"])) {
            $errors["return_date"] = "Return date cannot be earlier than pickup date.";
        }
    }

    if(empty(array_filter($errors))){
            $detailsObj->bookID = $details["bookID"];
            $detailsObj->userID = $details["userID"];
            $detailsObj->borrow_date = date("Y-m-d");
            $detailsObj->return_date = $details["return_date"];
            $detailsObj->pickup_date = $details["pickup_date"];
            $detailsObj->returned_condition = NULL;
            $detailsObj->request = "Pending";
            $detailsObj->status = NULL;
            if($detailsObj->addDetail()){
                 header("Location: ../../app/views/borrower/catalogue.php");
                exit;
            }else {
                echo "FAILED";
            }
        }
        $_SESSION["errors"] = $errors;
        $_SESSION["old"] = $details;
        header("Location: ../../app/views/borrower/borrow.php");
        exit;
    }