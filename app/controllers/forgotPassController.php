<?php
session_start();
require_once(__DIR__ . '/../models/userLogin.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libraries/phpmailer/src/Exception.php';
require_once __DIR__ . '/../libraries/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../libraries/phpmailer/src/SMTP.php';

$loginObj = new Login();
$errors = [];
$action = $_GET['action'] ?? ''; 

// Helper function to send email
function sendOtpEmail($email, $userObj) {
    global $errors; 
    
    $user = $userObj->getUserByEmail($email);
    if (!$user) {
        return false;
    }

    try {
        $otp = rand(100000, 999999);
        $expiry = date("Y-m-d H:i:s", time() + (60 * 2)); // 2 Minutes Expiry

        $userObj->saveResetToken($email, $otp, $expiry);

        $fullName = $user["fName"] . ' ' . $user["lName"];
        
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'graziellamssaavedra06@gmail.com';
        $mail->Password = 'cpybynwckiipsszp'; 
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('graziellamssaavedra06@gmail.com', 'Library Administration');
        $mail->addAddress($email, $fullName);
        $mail->isHTML(true);
        $mail->Subject = "Password Reset Code - WMSU Library";
        $mail->Body = <<<EOT
            <div style="font-family: sans-serif; padding: 20px; background-color: #f4f4f7;">
                <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; text-align: center;">
                    <h2>Password Reset Code</h2>
                    <p>Hello {$fullName},</p>
                    <p>Use the code below to reset your password:</p>
                    <h1 style="letter-spacing: 5px; background: #eee; padding: 10px; border-radius: 5px; display: inline-block;">{$otp}</h1>
                    <p style="color: #cc0000;">This code expires in 2 minutes.</p>
                </div>
            </div>
EOT;
        $mail->send();
        return true;

    } catch (Exception $e) {
        $errors["invalid"] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

// ==========================================================
// ACTION 1: SEND OTP
// ==========================================================
if ($action === 'send_otp' && $_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = trim(htmlspecialchars($_POST["email"] ?? ""));

    if (empty($email)) {
        $errors["invalid"] = "Please input your email address.";
    } else {
        $user = $loginObj->getUserByEmail($email);
        if (!$user) {
            $errors["invalid"] = "Email address not found.";
        }
    }

    if (empty($errors)) {
        if(sendOtpEmail($email, $loginObj)) {
            $_SESSION['reset_email'] = $email;
            $_SESSION["success"] = "Code sent! It expires in 2 minutes.";
            header("Location: ../../app/views/borrower/login.php?view=verify");
            exit;
        }
    }

    $_SESSION["errors"] = $errors;
    header("Location: ../../app/views/borrower/login.php?view=forgot");
    exit;
}

// ==========================================================
// ACTION 2: RESEND OTP
// ==========================================================
else if ($action === 'resend_otp') {
    $email = $_SESSION['reset_email'] ?? '';

    if (empty($email)) {
        header("Location: ../../app/views/borrower/login.php?view=forgot");
        exit;
    }

    if(sendOtpEmail($email, $loginObj)) {
        $_SESSION["success"] = "A new code has been sent to your email.";
    } else {
        $_SESSION["errors"] = $errors;
    }

    header("Location: ../../app/views/borrower/login.php?view=verify");
    exit;
}

// ==========================================================
// ACTION 3: VERIFY OTP
// ==========================================================
else if ($action === 'verify_otp' && $_SERVER["REQUEST_METHOD"] == "POST") {
    
    $otp = implode("", $_POST['otp'] ?? []); 
    if(empty($otp)) $otp = trim($_POST['otp_code'] ?? ''); 

    $email = $_SESSION['reset_email'] ?? '';

    if (empty($email) || empty($otp)) {
        $errors['otp'] = "Invalid session or missing code.";
    } else {
        $user = $loginObj->verifyOtp($email, $otp);

        if ($user) {
            $_SESSION['otp_verified'] = true; 
            header("Location: ../../app/views/borrower/login.php?view=reset");
            exit;
        } else {
            $errors['otp'] = "Invalid or expired code.";
        }
    }

    $_SESSION['errors'] = $errors;
    header("Location: ../../app/views/borrower/login.php?view=verify");
    exit;
}

// ==========================================================
// ACTION 4: RESET PASSWORD
// ==========================================================
else if ($action === 'reset' && $_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (empty($_SESSION['otp_verified']) || empty($_SESSION['reset_email'])) {
        header("Location: ../../app/views/borrower/login.php");
        exit;
    }

    $password = trim($_POST["password"] ?? "");
    $confirmPassword = trim($_POST["confirm_password"] ?? "");
    $email = $_SESSION['reset_email'];

    if (empty($password)) {
        $errors["password"] = "Please enter a new password.";
    }
    if ($password !== $confirmPassword) {
        $errors["confirm_password"] = "Passwords do not match.";
    }

    // CHECK: Password must not be the same as old password
    // FIXED: Uses direct comparison (==) instead of password_verify
    if (empty($errors)) {
        $user = $loginObj->getUserByEmail($email);
        
        // Ensure we check the right column name (case-sensitive array key)
        $dbPassword = $user['password'] ?? $user['Password'] ?? '';

        if ($password == $dbPassword) {
            $errors["password"] = "New password cannot be the same as the old password.";
        }
    }

    if (empty($errors)) {
        // Pass plain text password to update function
        $isUpdated = $loginObj->updateUserPassword($email, $password);

        if ($isUpdated) {
            unset($_SESSION['reset_email']);
            unset($_SESSION['otp_verified']);
            
            $_SESSION["success"] = "Password successfully reset. Please login.";
            header("Location: ../../app/views/borrower/login.php?view=login"); 
            exit;
        } else {
            $errors["invalid"] = "Failed to update password.";
        }
    }

    $_SESSION["errors"] = $errors;
    header("Location: ../../app/views/borrower/login.php?view=reset");
    exit;
}
?>