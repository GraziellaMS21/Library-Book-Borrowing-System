<?php
session_start();
require_once(__DIR__ . "/../models/manageDetails.php");

$errors = [];
$detail = [];

$detailObj = new Details();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $detailID = isset($_GET["id"]) ? trim(htmlspecialchars($_GET["id"])) : null;


    $detail["bookID"] = trim(htmlspecialchars($_POST["bookID"]));
    $detail["userID"] = trim(htmlspecialchars($_POST["userID"]));
    $detail["pickup_date"] = trim(htmlspecialchars($_POST["pickup_date"]));
    $detail["return_date"] = trim(htmlspecialchars($_POST["return_date"]));
    $detail["request"] = trim(htmlspecialchars($_POST["request"]));
    $detail["returned_condition"] = trim(htmlspecialchars($_POST["returned_condition"]));
    $detail["status"] = trim(htmlspecialchars($_POST["status"]));


    if (empty($detail["bookID"])) {
        $errors["bookID"] = "Book ID is required.";
    }

    if (empty($detail["userID"])) {
        $errors["userID"] = "User ID date is required.";
    }

    if (empty($detail["pickup_date"])) {
        $errors["pickup_date"] = "Pickup date is required.";
    }

    if (empty($detail["return_date"])) {
        $errors["return_date"] = "Return date is required.";
    }

    if (empty($detail["request"])) {
        $errors["request"] = "Request is required.";
    }

    if (empty($errors)) {
        $detailObj->bookID = $detail["bookID"];
        $detailObj->userID = $detail["userID"];
        $detailObj->pickup_date = $detail["pickup_date"];
        $detailObj->return_date = $detail["return_date"];
        if (empty($detail["request"])) {
            $detailObj->request = NULL;
        } else {
            $detailObj->request = $detail["request"];
        }

        if (empty($detail["returned_condition"])) {
            $detailObj->returned_condition = NULL;
        } else {
            $detailObj->returned_condition = $detail["returned_condition"];
        }

        if (empty($detail["status"])) {
            $detailObj->status = NULL;
        } else {
            $detailObj->status = $detail["status"];
        }

        if ($detailObj->editDetail($detailID)) {
            header("Location: ../../app/views/librarian/detailsSection.php");
            exit;
        } else {
            echo "Failed to update borrowing detail.";
        }
    } else {
        $_SESSION["errors"] = $errors;
        $_SESSION["old"] = $detail;

        header("Location: ../../app/views/librarian/editDetail.php?id=$detailID");
        exit;
    }
}
