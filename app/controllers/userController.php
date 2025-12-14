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
$userTypes = $userObj->fetchUserTypes(); // This method is now effectively fetching borrower types

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userID = $_POST["userID"] ?? $_GET["id"] ?? null;
$currentTab = $_POST['tab'] ?? $_GET['tab'] ?? 'approved';

$upload_dir = __DIR__ . "/../../public/uploads/id_images/";

// --- GET CURRENT ADMIN DETAILS ---
$currentAdminID = $_SESSION['user_id'] ?? null;
$currentAdminName = ($_SESSION['fName'] ?? 'Librarian') . ' ' . ($_SESSION['lName'] ?? '');

// --- 3NF LOGIC: Capture Reason IDs and Custom Remarks ---
$remarks = NULL;
$reasonIDs = [];
$isOtherSelected = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture the checkbox IDs (Array of Integers)
    if (isset($_POST['reason_presets']) && is_array($_POST['reason_presets'])) {
        foreach ($_POST['reason_presets'] as $id) {
            if ($id === 'other') {
                $isOtherSelected = true;
            } elseif (is_numeric($id)) {
                $reasonIDs[] = $id;
            }
        }
    }

    // Capture the typed custom note
    if (!empty($_POST['reason_custom'])) {
        $remarks = htmlspecialchars(trim($_POST['reason_custom']));
    }

    // Append "Others - " if checkbox selected
    if ($isOtherSelected) {
        $prefix = "Others - ";
        if ($remarks) {
            $remarks = $prefix . $remarks;
        } else {
            $remarks = "Others (No details provided)";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ==========================================================
    // NEW: ACTION - CHANGE PASSWORD
    // ==========================================================
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $c_password = $_POST['c_password'] ?? '';

        // Basic Validation
        if (empty($new_password) || empty($c_password)) {
            $_SESSION['errors']['password'] = "All password fields are required.";
        } elseif ($new_password !== $c_password) {
            $_SESSION['errors']['password'] = "New password and confirmation do not match.";
        }

        // elseif (strlen($new_password) < 6) { 
        //     $_SESSION['errors']['password'] = "Password must be at least 6 characters long.";
        // }

        // Verify Old Password (Optional: Verify against DB if needed)
        // If you want to force check old password, you'd call $userObj->verifyPassword($userID, $current_password) here.

        if (empty($_SESSION['errors'])) {
            // Ensure you have a 'changePassword' method in your manageUsers.php model
            if ($userObj->changePassword($userID, $new_password)) {
                $_SESSION['success_msg'] = "Password updated successfully.";
                header("Location: ../../app/views/borrower/profile.php?success=password");
                exit;
            } else {
                $_SESSION['errors']['db'] = "Failed to update password. Please try again.";
            }
        }

        // Redirect back on error
        header("Location: ../../app/views/borrower/profile.php?error=password");
        exit;
    }

    // ==========================================================
    // NEW: ACTION - EDIT PROFILE (BORROWER)
    // ==========================================================
    if ($action === 'edit_profile_borrower') {
        // Collect Data
        $user["lName"] = trim(htmlspecialchars($_POST["lName"] ?? ''));
        $user["fName"] = trim(htmlspecialchars($_POST["fName"] ?? ''));
        $user["middleIn"] = trim(htmlspecialchars($_POST["middleIn"] ?? ''));
        $user["contact_no"] = trim(htmlspecialchars($_POST["contact_no"] ?? ''));
        $user["email"] = trim(htmlspecialchars($_POST["email"] ?? ''));

        // Hidden fields usually present in form or session for updates
        $user["departmentID"] = $_POST["departmentID"] ?? $_SESSION['departmentID'] ?? '';
        $user["borrowerTypeID"] = $_POST["borrowerTypeID"] ?? $_SESSION['borrowerTypeID'] ?? '';
        // $user["role"] = $_POST["role"] ?? $_SESSION['role'] ?? 'Borrower'; // Handled by UserType

        // Validations
        if (empty($user["lName"]))
            $errors["lName"] = "Last Name is required.";
        if (empty($user["fName"]))
            $errors["fName"] = "First Name is required.";

        if (empty($user["email"])) {
            $errors["email"] = "Email is required.";
        } elseif (!filter_var($user["email"], FILTER_VALIDATE_EMAIL)) {
            $errors["email"] = "Invalid email format.";
        } elseif ($userObj->isEmailExist($user["email"], $userID)) {
            $errors["email"] = "Email address already taken.";
        }

        // Handle Image Upload
        $existing_image_name = trim(htmlspecialchars($_POST["existing_image_name"] ?? ''));
        $existing_image_dir = trim(htmlspecialchars($_POST["existing_image_dir"] ?? ''));
        $new_image_name = $existing_image_name;
        $new_image_dir = $existing_image_dir;

        if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_img'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($ext, $allowed)) {
                $errors['profile_img'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF allowed.";
            } else {
                $new_name = uniqid('id_', true) . "." . $ext;
                $target_path = $upload_dir . $new_name;

                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $new_image_name = $new_name;
                    $new_image_dir = "public/uploads/id_images/" . $new_name;

                    // Remove old image if it exists and isn't default
                    if ($existing_image_name && file_exists(__DIR__ . "/../../" . $existing_image_dir)) {
                        unlink(__DIR__ . "/../../" . $existing_image_dir);
                    }
                } else {
                    $errors['profile_img'] = "Failed to upload image.";
                }
            }
        }

        if (empty(array_filter($errors))) {
            // Set Object Properties
            $userObj->lName = $user["lName"];
            $userObj->fName = $user["fName"];
            $userObj->middleIn = $user["middleIn"];
            $userObj->contact_no = $user["contact_no"];
            $userObj->departmentID = $user["departmentID"];
            $userObj->email = $user["email"];
            $userObj->borrowerTypeID = $user["borrowerTypeID"];
            // $userObj->role = $user["role"]; // Removed in 3NF
            $userObj->imageID_name = $new_image_name;
            $userObj->imageID_dir = $new_image_dir;

            if ($userObj->editUser($userID)) {
                // UPDATE SESSION VARIABLES IMMEDIATELY
                $_SESSION['fName'] = $user["fName"];
                $_SESSION['lName'] = $user["lName"];
                $_SESSION['email'] = $user["email"];
                $_SESSION['imageID_dir'] = $new_image_dir;

                $_SESSION['success_msg'] = "Profile updated successfully.";
                header("Location: ../../app/views/borrower/settings.php?success=edit");
                exit;
            } else {
                $_SESSION["errors"] = ["db_error" => "Database update failed."];
                $_SESSION["old"] = $user; // Repopulate form
                header("Location: ../../app/views/borrower/profile.php?error=db");
                exit;
            }
        } else {
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $user;
            header("Location: ../../app/views/borrower/profile.php?error=validation");
            exit;
        }
    }


    // --- EDIT USER (Existing Admin Edit Logic) ---
    if ($action === 'edit') {
        $user["lName"] = trim(htmlspecialchars($_POST["lName"] ?? ''));
        $user["fName"] = trim(htmlspecialchars($_POST["fName"] ?? ''));
        $user["middleIn"] = trim(htmlspecialchars($_POST["middleIn"] ?? ''));
        $user["contact_no"] = trim(htmlspecialchars($_POST["contact_no"] ?? ''));
        $user["departmentID"] = trim(htmlspecialchars($_POST["departmentID"] ?? ''));
        $user["email"] = trim(htmlspecialchars($_POST["email"] ?? ''));
        $user["borrowerTypeID"] = trim(htmlspecialchars($_POST["borrowerTypeID"] ?? ''));
        // $user["role"] = trim(htmlspecialchars($_POST["role"] ?? '')); // Removed in 3NF

        if (empty($user["lName"])) {
            $errors["lName"] = "Last Name is required.";
        }
        if (empty($user["fName"])) {
            $errors["fName"] = "First Name is required.";
        }
        if (empty($user["borrowerTypeID"])) {
            $errors["borrowerTypeID"] = "User Type is required.";
        }
        if (empty($user["borrowerTypeID"])) {
            $errors["borrowerTypeID"] = "User Type is required.";
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
                $userObj->departmentID = $user["departmentID"];
                $userObj->email = $user["email"];
                $userObj->borrowerTypeID = $user["borrowerTypeID"];
                // $userObj->role = $user["role"]; // Removed in 3NF

                $userObj->imageID_name = $new_image_name;
                $userObj->imageID_dir = $new_image_dir;

                if ($userObj->editUser($userID)) {
                    header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}&success=edit");
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

    // --- REJECT ACTION (Updated) ---
    if ($action === 'reject' && $userID) {

        // Pass $currentAdminID to log who rejected it
        if ($userObj->updateUserStatus($userID, "Rejected", "Inactive", "Reject", $remarks, $reasonIDs, $currentAdminID)) {

            $mail = new PHPMailer(true);
            $userData = $userObj->fetchUser($userID);
            $fullName = $userData["fName"] . ' ' . $userData["lName"];

            // Fetch reasons for email
            $statusDetails = $userObj->fetchLatestUserReasons($userID);
            $emailReasonList = "<ul>";
            foreach ($statusDetails['reasons'] as $rText) {
                $emailReasonList .= "<li>" . htmlspecialchars($rText) . "</li>";
            }
            $emailReasonList .= "</ul>";
            if (!empty($statusDetails['remarks'])) {
                $emailReasonList .= "<p><em>Note: " . htmlspecialchars($statusDetails['remarks']) . "</em></p>";
            }

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
                                <div style="margin: 5px 0 0 0; font-size: 16px; color: #2D3748;">{$emailReasonList}</div>
                            </div>
                            <p style="margin-top: 15px; font-size: 14px; color: #718096;">Processed by: <strong>{$currentAdminName}</strong></p>
                            <p style="line-height: 1.6; margin-bottom: 25px;">
                                You may contact the librarian for more details regarding your application.
                            </p>
                            <div style="text-align: center; margin-bottom: 10px;">
                                <a href="http://localhost/Library-Book-Borrowing-System/app/views/borrower/contact.php" style="background-color: #edf2f7; color: #2d3748; border: 1px solid #cbd5e0; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; display: inline-block;">Contact Support</a>
                            </div>
                        </div>
                    </div>
                </div>
EOT;
                $mail->send();
            } catch (Exception $e) {
            }

            header("Location: ../../app/views/librarian/usersSection.php?tab=rejected&success=reject");
            exit;
        } else {
            $_SESSION["errors"] = ["db_error" => "Failed to reject user due to a database error."];
            header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
            exit;
        }
    }

    // --- BLOCK ACTION (Updated) ---
    if ($action === 'block' && $userID) {

        // Pass $currentAdminID
        if ($userObj->updateUserStatus($userID, "", "Blocked", "Block", $remarks, $reasonIDs, $currentAdminID)) {

            $mail = new PHPMailer(true);
            $userData = $userObj->fetchUser($userID);
            $fullName = $userData["fName"] . ' ' . $userData["lName"];

            // Fetch reasons for email
            $statusDetails = $userObj->fetchLatestUserReasons($userID);
            $emailReasonList = "<ul>";
            foreach ($statusDetails['reasons'] as $rText) {
                $emailReasonList .= "<li>" . htmlspecialchars($rText) . "</li>";
            }
            $emailReasonList .= "</ul>";
            if (!empty($statusDetails['remarks'])) {
                $emailReasonList .= "<p><em>Note: " . htmlspecialchars($statusDetails['remarks']) . "</em></p>";
            }

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
                                <div style="margin: 5px 0 0 0; font-size: 16px; color: #2D3748;">{$emailReasonList}</div>
                            </div>
                            <p style="margin-top: 15px; font-size: 14px; color: #718096;">Processed by: <strong>{$currentAdminName}</strong></p>
                            <p style="line-height: 1.6; margin-bottom: 25px;">
                                To restore your access, please resolve this issue with the administration office as soon as possible.
                            </p>
                            <div style="text-align: center; margin-bottom: 20px;">
                                <a href="http://localhost/Library-Book-Borrowing-System/app/views/borrower/contact.php" style="background-color: #2D3748; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; display: inline-block;">Contact Support</a>
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

            header("Location: ../../app/views/librarian/usersSection.php?tab=blocked&success=block");
            exit;
        } else {
            $_SESSION["errors"] = ["db_error" => "Failed to block user due to a database error."];
            header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
            exit;
        }
    }
}

// --- UNBLOCK ACTION (Updated) ---
if ($action === 'unblock' && $userID) {
    // Pass $currentAdminID
    if ($userObj->updateUserStatus($userID, "Approved", "Active", "Unblock", $remarks, $reasonIDs, $currentAdminID)) {

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
                        <p style="margin-top: 15px; font-size: 14px; color: #718096;">Processed by: <strong>{$currentAdminName}</strong></p>
                        <p style="line-height: 1.6; margin-bottom: 25px;">
                            You now have full access to borrow books, reserve titles, and view your history.
                        </p>
                        <div style="text-align: center; margin-bottom: 10px;">
                            <a href="http://localhost/Library-Book-Borrowing-System/app/views/borrower/login.php" style="background-color: #3182ce; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; display: inline-block;">Login to Account</a>
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

        header("Location: ../../app/views/librarian/usersSection.php?tab=approved&success=unblock");
        exit;
    } else {
        $_SESSION["errors"] = ["db_error" => "Failed to unblock user due to a database error."];
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
        exit;
    }
}

// --- APPROVE ACTION (Updated) ---
if ($action === 'approve' && $userID) {
    // Pass $currentAdminID (even if empty remarks/reasons)
    if ($userObj->updateUserStatus($userID, "Approved", "Active", "Approve", "", [], $currentAdminID)) {
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
                        <p style="margin-top: 15px; font-size: 14px; color: #718096;">Processed by: <strong>{$currentAdminName}</strong></p>
                        <p style="line-height: 1.6; margin-bottom: 25px;">
                            You can now log in to browse books and manage your reservations.
                        </p>
                        <div style="text-align: center; margin-bottom: 10px;">
                            <a href="http://localhost/Library-Book-Borrowing-System/app/views/borrower/login.php" style="background-color: #28a745; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; display: inline-block;">Login to Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
EOT;
            $mail->send();
        } catch (Exception $e) {
        }

        header("Location: ../../app/views/librarian/usersSection.php?tab=approved&success=approve");
        exit;
    } else {
        $_SESSION["errors"] = ["db_error" => "Failed to approve user due to a database error."];
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
        exit;
    }
}

// --- DELETE ACTION (Unchanged) ---
if ($action === 'delete' && $userID) {
    $currentUserRole = $_SESSION['role'] ?? 'Borrower';
    $targetUserData = $userObj->fetchUser($userID);
    $targetUserRole = $targetUserData['role'];
    $imagePath = $targetUserData['imageID_dir'] ?? null;

    if ($targetUserRole === 'Admin') {
        $_SESSION["errors"] = ["permission" => "System Error: Cannot delete the Main Admin account."];
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
        exit;
    }

    if ($currentUserRole !== 'Admin' && $targetUserRole === 'Librarian') {
        $_SESSION["errors"] = ["permission" => "Permission Denied: Only an Admin can delete other Librarians."];
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}");
        exit;
    }

    if ($userObj->deleteUser($userID)) {
        if ($imagePath && file_exists(__DIR__ . "/../../" . $imagePath)) {
            unlink(__DIR__ . "/../../" . $imagePath);
        }
        header("Location: ../../app/views/librarian/usersSection.php?tab={$currentTab}&success=delete");
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