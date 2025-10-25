<?php
session_start();
require_once(__DIR__ . "/../models/manageUsers.php");
$userObj = new User();
$user = [];
$errors = [];
$userTypes = $userObj->fetchUserTypes();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$userID = $_POST["userID"] ?? $_GET["id"] ?? null;

$currentTab = $_POST['tab'] ?? $_GET['tab'] ?? 'approved';

$upload_dir = __DIR__ . "/../../public/uploads/id_images/";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user["lName"] = trim(htmlspecialchars($_POST["lName"] ?? ''));
    $user["fName"] = trim(htmlspecialchars($_POST["fName"] ?? ''));
    $user["middleIn"] = trim(htmlspecialchars($_POST["middleIn"] ?? ''));
    $user["contact_no"] = trim(htmlspecialchars($_POST["contact_no"] ?? ''));
    $user["college_department"] = trim(htmlspecialchars($_POST["college_department"] ?? ''));
    $user["email"] = trim(htmlspecialchars($_POST["email"] ?? ''));
    $user["userTypeID"] = trim(htmlspecialchars($_POST["userTypeID"] ?? ''));
    $user["role"] = trim(htmlspecialchars($_POST["role"] ?? ''));

    if (empty($user["lName"])) {
        $errors["lName"] = "Last Name is required.";
    }
    if (empty($user["fName"])) {
        $errors["fName"] = "First Name is required.";
    }
    if (empty($user["userTypeID"])) {
        $errors["userTypeID"] = "User Type is required.";
    }
    if (empty($user["role"])) {
        $errors["role"] = "Role is required.";
    }

    if ($action === 'edit' && $userID) {
        if (empty($user["email"])) {
            $errors["email"] = "Email is required.";
        } elseif (!filter_var($user["email"], FILTER_VALIDATE_EMAIL)) {
            $errors["email"] = "Invalid email format.";
        } elseif ($userObj->isEmailExist($user["email"], $userID)) {
            $errors["email"] = "Email address already exists for another user.";
        }

        $existing_image_name = trim(htmlspecialchars($_POST["existing_image_name"] ?? ''));
        $existing_image_dir = trim(htmlspecialchars($_POST["existing_image_dir"] ?? ''));

        $new_image_name = $existing_image_name;
        $new_image_dir = $existing_image_dir;

        if (isset($_FILES['new_imageID']) && $_FILES['new_imageID']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['new_imageID'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($ext, $allowed)) {
                $errors['new_imageID'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
            } else {
                $new_name = uniqid('id_', true) . "." . $ext;
                $target_path = $upload_dir . $new_name;

                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $new_image_name = $new_name;
                    $new_image_dir = "public/uploads/id_images/" . $new_name;

                    if ($existing_image_name && file_exists(__DIR__ . "/../../" . $existing_image_dir)) {
                        unlink(__DIR__ . "/../../" . $existing_image_dir);
                    }
                } else {
                    $errors['new_imageID'] = "Failed to move uploaded file.";
                }
            }
        } elseif (isset($_FILES['new_imageID']) && $_FILES['new_imageID']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors['new_imageID'] = "Upload error (Code: " . $_FILES['new_imageID']['error'] . ").";
        }

        if (empty(array_filter($errors))) {
            $userObj->lName = $user["lName"];
            $userObj->fName = $user["fName"];
            $userObj->middleIn = $user["middleIn"];
            $userObj->contact_no = $user["contact_no"];
            $userObj->college_department = $user["college_department"];
            $userObj->email = $user["email"];
            $userObj->userTypeID = $user["userTypeID"];
            $userObj->role = $user["role"];

            $userObj->imageID_name = $new_image_name;
            $userObj->imageID_dir = $new_image_dir;

            if ($userObj->editUser($userID)) {
                header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
                exit;
            } else {
                $_SESSION["errors"] = ["db_error" => "Failed to update user due to a database error."];
                $user['existing_image_dir'] = $new_image_dir;
                $user['existing_image_name'] = $new_image_name;
                $_SESSION["old"] = $user;
                header("Location: ../../app/views/librarian/usersSection.php?modal=edit&id={$userID}&tab={$currentTab}");
                exit;
            }
        } else {
            $user['existing_image_dir'] = $new_image_dir;
            $user['existing_image_name'] = $new_image_name;
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $user;
            header("Location: ../../app/views/librarian/usersSection.php?modal=edit&id={$userID}&tab={$currentTab}");
            exit;
        }
    }
}

// --- GET Request Handling (Separate IF blocks, consistent with bookController) ---

if ($action === 'approveReject' && $userID && isset($_GET['status'])) {
    $newStatus = trim($_GET['status']);

    if (!in_array($newStatus, ['Approved', 'Rejected'])) {
        $_SESSION["errors"] = ["general" => "Invalid status action."];
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
        exit;
    }

    if ($userObj->approveRejectUser($userID, $newStatus)) {
        header("Location: ../../app/views/librarian/usersSection.php?" . "tab={$currentTab}");
        exit;
    } else {
        $_SESSION["errors"] = ["db_error" => "Failed to update user status due to a database error."];
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
        exit;
    }
}

if ($action === 'block' && $userID) {
    if ($userObj->approveRejectUser($userID, "Blocked")) {
        header("Location: ../../app/views/librarian/usersSection.php?tab=blocked");
        exit;
    } else {
        $_SESSION["errors"] = ["db_error" => "Failed to block user due to a database error."];
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
        exit;
    }
}

if ($action === 'unblock' && $userID) {
    if ($userObj->approveRejectUser($userID, "Approved")) {
        header("Location: ../../app/views/librarian/usersSection.php?tab=approved");
        exit;
    } else {
        $_SESSION["errors"] = ["db_error" => "Failed to unblock user due to a database error."];
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
        exit;
    }
}

if ($action === 'delete' && $userID) {
    $userData = $userObj->fetchUser($userID);
    $imagePath = $userData['imageID_dir'] ?? null;

    if ($userObj->deleteUser($userID)) {
        if ($imagePath && file_exists(__DIR__ . "/../../" . $imagePath)) {
            unlink(__DIR__ . "/../../" . $imagePath);
        }
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
        exit;
    } else {
        $_SESSION["errors"] = ["db_error" => "Failed to delete user."];
        header("Location: ../../app/views/librarian/usersSection.php?modal=delete&id={$userID}&tab={$currentTab}");
        exit;
    }
}

// If no action was performed or POST/GET was malformed, redirect.
if (!isset($_GET['action']) && !isset($_POST['action']) && $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../../app/views/librarian/usersSection.php");
    exit;
}
?>