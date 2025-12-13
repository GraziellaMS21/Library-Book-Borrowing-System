<?php
session_start();
require_once(__DIR__ . "/../models/manageUsers.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libraries/phpmailer/src/Exception.php';
require_once __DIR__ . '/../libraries/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../libraries/phpmailer/src/SMTP.php';

$userObj = new User();
$user = [];
$errors = [];
$userTypes = $userObj->fetchUserTypes();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userID = $_POST["userID"] ?? $_GET["id"] ?? null;
$currentTab = $_POST['tab'] ?? $_GET['tab'] ?? 'approved';

$upload_dir = __DIR__ . "/../../public/uploads/id_images/";

$status_reason_str = NULL;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reasons = [];
    if (isset($_POST['reason_presets']) && is_array($_POST['reason_presets'])) {
        foreach ($_POST['reason_presets'] as $preset) {
            $reasons[] = htmlspecialchars($preset);
        }
    }
    if (!empty($_POST['reason_custom'])) {
        $reasons[] = htmlspecialchars(trim($_POST['reason_custom']));
    }
    if (!empty($reasons)) {
        $status_reason_str = implode("; ", $reasons);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if ($action === 'edit') {
        $user["lName"] = trim(htmlspecialchars($_POST["lName"] ?? ''));
        $user["fName"] = trim(htmlspecialchars($_POST["fName"] ?? ''));
        $user["middleIn"] = trim(htmlspecialchars($_POST["middleIn"] ?? ''));
        $user["contact_no"] = trim(htmlspecialchars($_POST["contact_no"] ?? ''));
        // CHANGED: Accept departmentID instead of string
        $user["departmentID"] = trim(htmlspecialchars($_POST["departmentID"] ?? ''));
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

        if ($userID) {
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
                // CHANGED: Set departmentID property
                $userObj->departmentID = $user["departmentID"];
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
                    header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}&modal=edit&id={$userID}");
                    exit;
                }
            } else {
                $user['existing_image_dir'] = $new_image_dir;
                $user['existing_image_name'] = $new_image_name;
                $_SESSION["errors"] = $errors;
                $_SESSION["old"] = $user;
                header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}&modal=edit&id={$userID}");
                exit;
            }
        }
    }

    // REJECT ACTION
    if ($action === 'reject' && $userID) {
        $status_reason = $status_reason_str ?: "Application incomplete or does not meet criteria.";

        if ($userObj->updateUserStatus($userID, "Rejected", "Inactive", $status_reason)) {
            $mail = new PHPMailer(true);
            $userData = $userObj->fetchUser($userID);
            $fullName = $userData["fName"] . ' ' . $userData["lName"];

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'graziellamssaavedra06@gmail.com';
                $mail->Password = 'cpybynwckiipsszp';
                $mail->SMTPSecure = 'ssl';
                $mail->Port = 465;

                $mail->setFrom('graziellamssaavedra06@gmail.com', 'Library Administration');
                $mail->addAddress($userData["email"], $fullName);

                $mail->isHTML(true);
                $mail->Subject = "Registration Update: Application Rejected";

                $mail->Body = <<<EOT
                <div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f7; padding: 40px 20px; margin: 0;">
                    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                        <div style="background-color: #718096; padding: 20px; text-align: center;">
                            <h2 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">Request Update</h2>
                        </div>
                        <div style="padding: 30px; color: #4a5568;">
                            <p style="font-size: 16px; margin-top: 0;">Dear <strong>{$fullName}</strong>,</p>
                            <p style="line-height: 1.6; font-size: 16px;">
                                We are writing to inform you that we could not process your <strong>account registration</strong> at this time.
                            </p>
                            <div style="background-color: #FFF5F5; border-left: 4px solid #D9534F; padding: 15px 20px; margin: 25px 0; border-radius: 4px;">
                                <p style="margin: 0; font-size: 12px; color: #C53030; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Reason for rejection</p>
                                <p style="margin: 5px 0 0 0; font-size: 16px; color: #2D3748;">{$status_reason}</p>
                            </div>
                            <p style="line-height: 1.6; margin-bottom: 25px;">
                                You may contact the librarian for more details regarding your application.
                            </p>
                            <div style="text-align: center; margin-bottom: 10px;">
                                <a href="#" style="background-color: #edf2f7; color: #2d3748; border: 1px solid #cbd5e0; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; display: inline-block;">Contact Support</a>
                            </div>
                        </div>
                    </div>
                </div>
EOT;
                $mail->send();
            } catch (Exception $e) {
            }

            header("Location: ../../app/views/librarian/usersSection.php?tab=rejected");
            exit;
        } else {
            $_SESSION["errors"] = ["db_error" => "Failed to reject user due to a database error."];
            header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
            exit;
        }
    }

    // BLOCK ACTION
    if ($action === 'block' && $userID) {
        $status_reason = $status_reason_str ?: "Violation of library policies.";

        if ($userObj->updateUserStatus($userID, "", "Blocked", $status_reason)) {
            $mail = new PHPMailer(true);
            $userData = $userObj->fetchUser($userID);
            $fullName = $userData["fName"] . ' ' . $userData["lName"];

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'graziellamssaavedra06@gmail.com';
                $mail->Password = 'cpybynwckiipsszp';
                $mail->SMTPSecure = 'ssl';
                $mail->Port = 465;

                $mail->setFrom('graziellamssaavedra06@gmail.com', 'Library Administration');
                $mail->addAddress($userData["email"], $fullName);

                $mail->isHTML(true);
                $mail->Subject = "Important: Account Blocked";

                $mail->Body = <<<EOT
                <div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f7; padding: 40px 20px; margin: 0;">
                    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                        <div style="background-color: #D9534F; padding: 20px; text-align: center;">
                            <h2 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">Action Required</h2>
                        </div>
                        <div style="padding: 30px; color: #4a5568;">
                            <p style="font-size: 16px; margin-top: 0;">Hello <strong>{$fullName}</strong>,</p>
                            <p style="line-height: 1.6; font-size: 16px; color: #4a5568;">
                                We are writing to let you know that your library access has been temporarily <strong style="color: #D9534F;">suspended</strong>.
                            </p>
                            <div style="background-color: #FFF5F5; border-left: 4px solid #D9534F; padding: 15px 20px; margin: 25px 0; border-radius: 4px;">
                                <p style="margin: 0; font-size: 14px; color: #C53030; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">Reason for suspension</p>
                                <p style="margin: 5px 0 0 0; font-size: 16px; color: #2D3748;">{$status_reason}</p>
                            </div>
                            <p style="line-height: 1.6; margin-bottom: 25px;">
                                To restore your access, please resolve this issue with the administration office as soon as possible.
                            </p>
                            <div style="text-align: center; margin-bottom: 20px;">
                                <a href="#" style="background-color: #2D3748; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; display: inline-block;">Contact Support</a>
                            </div>
                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;">
                            <p style="font-size: 12px; color: #a0aec0; text-align: center;">
                                This is an automated message from the Library System.
                            </p>
                        </div>
                    </div>
                </div>
EOT;
                $mail->send();
            } catch (Exception $e) {
            }

            header("Location: ../../app/views/librarian/usersSection.php?tab=blocked");
            exit;
        } else {
            $_SESSION["errors"] = ["db_error" => "Failed to block user due to a database error."];
            header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
            exit;
        }
    }
}

// APPROVE ACTION
if ($action === 'approve' && $userID) {
    if ($userObj->updateUserStatus($userID, "Approved", "Active")) {
        $mail = new PHPMailer(true);
        // Fixed: Use $userData to be consistent with other blocks
        $userData = $userObj->fetchUser($userID);
        $fullName = $userData["fName"] . ' ' . $userData["lName"];

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'graziellamssaavedra06@gmail.com';
            $mail->Password = 'cpybynwckiipsszp';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('graziellamssaavedra06@gmail.com', 'Library Administration');
            $mail->addAddress($userData["email"], $fullName);

            $mail->isHTML(true);
            $mail->Subject = "Registration Update: Application Accepted";
            $mail->Body = <<<EOT
            <div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f7; padding: 40px 20px; margin: 0;">
                <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    <div style="background-color: #28a745; padding: 20px; text-align: center;">
                        <h2 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">Application Approved!</h2>
                    </div>
                    <div style="padding: 30px; color: #4a5568;">
                        <p style="font-size: 16px; margin-top: 0;">Hi <strong>{$fullName}</strong>,</p>
                        <p style="line-height: 1.6; font-size: 16px;">
                            Good news! Your account registration has been accepted. You are now a member of the library system.
                        </p>
                        <div style="background-color: #f0fff4; border: 1px solid #c6f6d5; border-radius: 6px; padding: 20px; margin: 20px 0;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="padding-bottom: 10px; color: #2f855a; font-weight: bold; font-size: 14px; width: 30%;">Status:</td>
                                    <td style="padding-bottom: 10px; color: #2d3748; font-weight: 600;">Active</td>
                                </tr>
                                <tr>
                                    <td style="color: #2f855a; font-weight: bold; font-size: 14px;">Role:</td>
                                    <td style="color: #2d3748;">{$userData['role']}</td>
                                </tr>
                            </table>
                        </div>
                        <p style="line-height: 1.6; margin-bottom: 25px;">
                            You can now log in to browse books and manage your reservations.
                        </p>
                        <div style="text-align: center; margin-bottom: 10px;">
                            <a href="#" style="background-color: #28a745; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; display: inline-block;">Login to Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
EOT;
            $mail->send();
        } catch (Exception $e) {
        }

        header("Location: ../../app/views/librarian/usersSection.php?tab=approved");
        exit;
    } else {
        $_SESSION["errors"] = ["db_error" => "Failed to approve user due to a database error."];
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
        exit;
    }
}

// UNBLOCK ACTION
if ($action === 'unblock' && $userID) {
    if ($userObj->updateUserStatus($userID, "Approved", "Active")) {
        $mail = new PHPMailer(true);
        // Fixed: Use $userData to be consistent
        $userData = $userObj->fetchUser($userID);
        $fullName = $userData["fName"] . ' ' . $userData["lName"];

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'graziellamssaavedra06@gmail.com';
            $mail->Password = 'cpybynwckiipsszp';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('graziellamssaavedra06@gmail.com', 'Library Administration');
            $mail->addAddress($userData["email"], $fullName);

            $mail->isHTML(true);
            $mail->Subject = "Account Update: Access Restored";
            $mail->Body = <<<EOT
            <div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f7; padding: 40px 20px; margin: 0;">
                <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    <div style="background-color: #3182ce; padding: 20px; text-align: center;">
                        <h2 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">Welcome Back</h2>
                    </div>
                    <div style="padding: 30px; color: #4a5568;">
                        <p style="font-size: 16px; margin-top: 0;">Hello <strong>{$fullName}</strong>,</p>
                        <p style="line-height: 1.6; font-size: 16px;">
                            We are pleased to inform you that your library account has been <strong style="color: #3182ce;">reactivated</strong>.
                        </p>
                        <div style="background-color: #ebf8ff; color: #2c5282; padding: 15px; margin: 20px 0; border-radius: 6px; text-align: center;">
                            <span style="font-size: 24px; vertical-align: middle;">🔓</span>
                            <span style="font-weight: bold; margin-left: 10px; vertical-align: middle;">Account Status: Active</span>
                        </div>
                        <p style="line-height: 1.6; margin-bottom: 25px;">
                            You now have full access to borrow books, reserve titles, and view your history.
                        </p>
                        <div style="text-align: center; margin-bottom: 10px;">
                            <a href="#" style="background-color: #3182ce; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; display: inline-block;">Login to Account</a>
                        </div>
                        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;">
                        <p style="font-size: 12px; color: #a0aec0; text-align: center;">
                            If you have any questions about previous account issues, please reply to this email.
                        </p>
                    </div>
                </div>
            </div>
EOT;
            $mail->send();
        } catch (Exception $e) {
        }

        header("Location: ../../app/views/librarian/usersSection.php?tab=approved");
        exit;
    } else {
        $_SESSION["errors"] = ["db_error" => "Failed to unblock user due to a database error."];
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
        exit;
    }
}

// In app/controllers/userController.php

// DELETE ACTION
if ($action === 'delete' && $userID) {

    // 1. Get the Current Logged-in User's Role
    $currentUserRole = $_SESSION['role'] ?? 'Borrower';

    // 2. Get the Target User's Role (The person being deleted)
    $targetUserData = $userObj->fetchUser($userID);
    $targetUserRole = $targetUserData['role'];
    $imagePath = $targetUserData['imageID_dir'] ?? null;

    // 3. PERMISSION CHECK:

    // Rule A: Nobody can delete a Super Admin (not even another Super Admin, to be safe)
    if ($targetUserRole === 'Super Admin') {
        $_SESSION["errors"] = ["permission" => "System Error: Cannot delete the Main Super Admin account."];
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
        exit;
    }

    // Rule B: Standard Admins cannot delete other Admins
    if ($currentUserRole !== 'Super Admin' && $targetUserRole === 'Admin') {
        $_SESSION["errors"] = ["permission" => "Permission Denied: Only a Super Admin can delete other Librarians."];
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
        exit;
    }

    // 4. Proceed to Delete
    if ($userObj->deleteUser($userID)) {
        if ($imagePath && file_exists(__DIR__ . "/../../" . $imagePath)) {
            unlink(__DIR__ . "/../../" . $imagePath);
        }
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
        exit;
    } else {
        $_SESSION["errors"] = ["db_error" => "Failed to delete user."];
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}&modal=delete&id={$userID}");
        exit;
    }
}

if (!isset($_GET['action']) && !isset($_POST['action']) && $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../../app/views/librarian/usersSection.php");
    exit;
}
?>