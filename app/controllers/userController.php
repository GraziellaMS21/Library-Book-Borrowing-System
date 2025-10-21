<?php
session_start();
require_once(__DIR__ . "/../models/manageUsers.php");
$userObj = new User();
$user = [];
$errors = [];
$userTypes = $userObj->fetchUserTypes();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Initialize userID for existence checks and redirects
$userID = $_POST["userID"] ?? $_GET["id"] ?? null;

// Get the current tab for redirection continuity
$currentTab = $_POST['tab'] ?? $_GET['tab'] ?? 'approved';

// Upload configuration
$upload_dir = __DIR__ . "/../../public/uploads/id_images/";

switch ($action) {
    case 'edit':
        // Data sanitization and retrieval
        $user["lName"] = trim(htmlspecialchars($_POST["lName"] ?? ''));
        $user["fName"] = trim(htmlspecialchars($_POST["fName"] ?? ''));
        $user["middleIn"] = trim(htmlspecialchars($_POST["middleIn"] ?? ''));
        $user["contact_no"] = trim(htmlspecialchars($_POST["contact_no"] ?? ''));
        // Combined field for college/department
        $user["college_department"] = trim(htmlspecialchars($_POST["college_department"] ?? ''));
        $user["email"] = trim(htmlspecialchars($_POST["email"] ?? ''));
        $user["userTypeID"] = trim(htmlspecialchars($_POST["userTypeID"] ?? ''));
        $user["role"] = trim(htmlspecialchars($_POST["role"] ?? ''));

        // --- Image Handling Variables ---
        $existing_image_name = trim(htmlspecialchars($_POST["existing_image_name"] ?? ''));
        $existing_image_dir = trim(htmlspecialchars($_POST["existing_image_dir"] ?? ''));

        $new_image_name = $existing_image_name;
        $new_image_dir = $existing_image_dir;

        // Validation logic
        if (empty($user["lName"])) {
            $errors["lName"] = "Last Name is required.";
        }
        if (empty($user["fName"])) {
            $errors["fName"] = "First Name is required.";
        }
        if (empty($user["email"])) {
            $errors["email"] = "Email is required.";
        } elseif (!filter_var($user["email"], FILTER_VALIDATE_EMAIL)) {
            $errors["email"] = "Invalid email format.";
        } elseif ($userObj->isEmailExist($user["email"], $userID)) {
            $errors["email"] = "Email address already exists for another user.";
        }
        if (empty($user["userTypeID"])) {
            $errors["userTypeID"] = "User Type is required.";
        }
        if (empty($user["role"])) {
            $errors["role"] = "Role is required.";
        }

        // --- Image Upload Processing ---
        if (isset($_FILES['new_imageID']) && $_FILES['new_imageID']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['new_imageID'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($ext, $allowed)) {
                $errors['new_imageID'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
            } else {
                // Generate unique name
                $new_name = uniqid('id_', true) . "." . $ext;
                $target_path = $upload_dir . $new_name;

                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Success: Update paths
                    $new_image_name = $new_name;
                    $new_image_dir = "public/uploads/id_images/" . $new_name;

                    // Delete old file if a new one was uploaded successfully
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
            // Success: Set object properties and edit user
            $userObj->lName = $user["lName"];
            $userObj->fName = $user["fName"];
            $userObj->middleIn = $user["middleIn"];
            $userObj->contact_no = $user["contact_no"];
            $userObj->college_department = $user["college_department"];
            $userObj->email = $user["email"];
            $userObj->userTypeID = $user["userTypeID"];
            $userObj->role = $user["role"];

            // Set new image properties
            $userObj->imageID_name = $new_image_name;
            $userObj->imageID_dir = $new_image_dir;

            if ($userObj->editUser($userID)) {
                // Success: Redirect to base page, preserving the current tab
                header("Location: ../../app/views/librarian/usersSection.php?success=edit&tab={$currentTab}");
                exit;
            } else {
                // Database failure
                $_SESSION["errors"] = ["db_error" => "Failed to update user due to a database error."];
                // Keep the potentially newly uploaded file if DB failed
                $user['existing_image_dir'] = $new_image_dir;
                $user['existing_image_name'] = $new_image_name;
                $_SESSION["old"] = $user;
                // Redirect to open modal, preserving the current tab
                header("Location: ../../app/views/librarian/usersSection.php?modal=edit&id={$userID}&tab={$currentTab}");
                exit;
            }
        } else {
            // Validation failure: Store errors and old data, redirect to open modal, preserving the current tab
            // Ensure image paths are preserved in $old
            $user['existing_image_dir'] = $new_image_dir;
            $user['existing_image_name'] = $new_image_name;
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $user;
            header("Location: ../../app/views/librarian/usersSection.php?modal=edit&id={$userID}&tab={$currentTab}");
            exit;
        }

    case 'approveReject': // Handles Pending -> Approved/Rejected
        if (isset($_GET['id']) && isset($_GET['status'])) {
            $userID = (int) $_GET['id'];
            $newStatus = trim($_GET['status']); // Expects 'Approved' or 'Rejected'
            $currentTab = $_GET['tab'] ?? 'pending';

            // Status validation
            if (!in_array($newStatus, ['Approved', 'Rejected'])) {
                $_SESSION["errors"] = ["general" => "Invalid status action."];
                header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
                exit;
            }

            if ($userObj->approveRejectUser($userID, $newStatus)) {
                // Success: Redirect back to the tab with a success message
                header("Location: ../../app/views/librarian/usersSection.php?success=" . strtolower($newStatus) . "&tab={$currentTab}");
                exit;
            } else {
                // Database failure
                $_SESSION["errors"] = ["db_error" => "Failed to update user status due to a database error."];
                header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
                exit;
            }
        } else {
            header("Location: ../../app/views/librarian/usersSection.php");
            exit;
        }

    case 'block': // Handles Approved -> Blocked (and moves to blocked tab)
        if (isset($_GET['id'])) {
            $userID = (int) $_GET['id'];
            $newStatus = 'Blocked'; // Target status
            $currentTab = $_GET['tab'] ?? 'approved';

            if ($userObj->approveRejectUser($userID, $newStatus)) {
                // MODIFIED: Redirect to the 'blocked' tab after blocking
                header("Location: ../../app/views/librarian/usersSection.php?success=blocked&tab=blocked");
                exit;
            } else {
                // Database failure
                $_SESSION["errors"] = ["db_error" => "Failed to block user due to a database error."];
                header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
                exit;
            }
        } else {
            header("Location: ../../app/views/librarian/usersSection.php");
            exit;
        }

    case 'unblock': // Handles Blocked -> Approved
        if (isset($_GET['id'])) {
            $userID = (int) $_GET['id'];
            $newStatus = 'Approved'; // Target status
            // MODIFIED: Current tab is 'blocked'
            $currentTab = $_GET['tab'] ?? 'blocked';

            if ($userObj->approveRejectUser($userID, $newStatus)) {
                // Success: Redirect to the 'approved' tab after unblocking
                header("Location: ../../app/views/librarian/usersSection.php?success=unblocked&tab=approved");
                exit;
            } else {
                // Database failure
                $_SESSION["errors"] = ["db_error" => "Failed to unblock user due to a database error."];
                header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
                exit;
            }
        } else {
            header("Location: ../../app/views/librarian/usersSection.php");
            exit;
        }

    case 'delete':
        // Deletion logic (permanently removes user)
        if (isset($_GET['id'])) {
            $userID = $_GET['id'];
            // MODIFIED: Default tab is 'approved'
            $currentTab = $_GET['tab'] ?? 'approved';

            // Fetch image path to delete file from server
            $userData = $userObj->fetchUser($userID);
            $imagePath = $userData['imageID_dir'] ?? null;

            if ($userObj->deleteUser($userID)) {
                // Attempt to delete file from server
                if ($imagePath && file_exists(__DIR__ . "/../../" . $imagePath)) {
                    unlink(__DIR__ . "/../../" . $imagePath);
                }
                // Redirect, preserving the current tab
                header("Location: ../../app/views/librarian/usersSection.php?success=delete&tab={$currentTab}");
                exit;
            } else {
                // Database failure
                $_SESSION["errors"] = ["db_error" => "Failed to delete user."];
                // Redirect, preserving the current tab
                header("Location: ../../app/views/librarian/usersSection.php?modal=delete&id={$userID}&tab={$currentTab}");
                exit;
            }
        } else {
            header("Location: ../../app/views/librarian/usersSection.php");
            exit;
        }

    default:
        // Handle unexpected action or direct access
        header("Location: ../../app/views/librarian/usersSection.php");
        exit;
}