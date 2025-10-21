<?php 
session_start();
require_once(__DIR__ . "/../models/managePenalty.php");
$penaltyObj = new Penalty();
$penalty = [];
$errors = [];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect_url = "../../app/views/librarian/penaltySection.php"; 

$penaltyID = $_POST["penaltyID"] ?? $_GET["id"] ?? null;

switch ($action) {
    case 'add':
        // UPDATED: Only handling borrowID, type, cost, status
        $penalty["borrowID"] = trim(htmlspecialchars($_POST["borrowID"] ?? ''));
        $penalty["type"] = trim(htmlspecialchars($_POST["type"] ?? ''));
        $penalty["cost"] = trim(htmlspecialchars($_POST["cost"] ?? ''));
        $penalty["status"] = trim(htmlspecialchars($_POST["status"] ?? ''));

        // --- Validation ---
        if (empty($penalty["borrowID"])) {
            $errors["borrowID"] = "Borrow ID is required.";
        }
        // Removed validation for userID and bookID
        if (empty($penalty["type"])) {
            $errors["type"] = "Penalty Type is required.";
        }
        if (empty($penalty["cost"]) || !is_numeric($penalty["cost"]) || $penalty["cost"] <= 0) {
            $errors["cost"] = "Valid Cost is required.";
        }
        if (empty($penalty["status"])) {
            $errors["status"] = "Status is required.";
        }
        
        // --- Process ---
        if (empty(array_filter($errors))) {
            $penaltyObj->borrowID = $penalty["borrowID"];
            // Removed assignment for userID and bookID
            $penaltyObj->type = $penalty["type"];
            $penaltyObj->cost = $penalty["cost"];
            $penaltyObj->status = $penalty["status"];
            
            if ($penaltyObj->addPenalty()) {
                header("Location: $redirect_url");
                exit;
            } else {
                $_SESSION["errors"] = ["general" => "Failed to add penalty due to a database error."];
                $_SESSION['open_modal'] = 'addPenaltyModal';
                $_SESSION["old"] = $penalty;
                header("Location: $redirect_url");
                exit;
            }
        } else {
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $penalty;
            $_SESSION['open_modal'] = 'addPenaltyModal';
            header("Location: $redirect_url"); 
            exit;
        }
        
    case 'edit':
        $penaltyID = $_POST["penaltyID"] ?? $_GET["id"];
        // UPDATED: Only handling borrowID, type, cost, status
        $penalty["borrowID"] = trim(htmlspecialchars($_POST["borrowID"] ?? ''));
        $penalty["type"] = trim(htmlspecialchars($_POST["type"] ?? ''));
        $penalty["cost"] = trim(htmlspecialchars($_POST["cost"] ?? ''));
        $penalty["status"] = trim(htmlspecialchars($_POST["status"] ?? ''));

        // --- Validation ---
        if (empty($penalty["borrowID"])) {
            $errors["borrowID"] = "Borrow ID is required.";
        }
        // Removed validation for userID and bookID
        if (empty($penalty["type"])) {
            $errors["type"] = "Penalty Type is required.";
        }
        if (empty($penalty["cost"]) || !is_numeric($penalty["cost"]) || $penalty["cost"] <= 0) {
            $errors["cost"] = "Valid Cost is required.";
        }
        if (empty($penalty["status"])) {
            $errors["status"] = "Status is required.";
        }
        
        // --- Process ---
        if (empty(array_filter($errors))) {
            $penaltyObj->borrowID = $penalty["borrowID"];
            // Removed assignment for userID and bookID
            $penaltyObj->type = $penalty["type"];
            $penaltyObj->cost = $penalty["cost"];
            $penaltyObj->status = $penalty["status"];
            
            if ($penaltyObj->editPenalty($penaltyID)) {
                header("Location: $redirect_url");
                exit;
            } else {
                $_SESSION["errors"] = ["general" => "Failed to update penalty due to a database error."];
                $_SESSION['open_modal'] = 'editPenaltyModal';
                $_SESSION['edit_penalty_id'] = $penaltyID;
                $_SESSION["old"] = $penalty;
                header("Location: $redirect_url");
                exit;
            }
        } else {
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $penalty;
            $_SESSION['open_modal'] = 'editPenaltyModal';
            $_SESSION['edit_penalty_id'] = $penaltyID; 
            header("Location: $redirect_url");
            exit;
        }
        
    case 'delete':
        if (isset($_GET['id'])) {
            $penaltyID = $_GET['id'];
            if ($penaltyObj->deletePenalty($penaltyID)) {
                header("Location: $redirect_url");
                exit;
            } else {
                $_SESSION["errors"] = ["general" => "Failed to delete penalty."];
                header("Location: $redirect_url");
                exit;
            }
        } else {
            $_SESSION["errors"] = ["general" => "No penalty ID provided for deletion."];
            header("Location: $redirect_url");
            exit;
        }
} 
?>
