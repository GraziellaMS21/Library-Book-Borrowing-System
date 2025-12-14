<?php
session_start();
require_once(__DIR__ . "/../models/userRegister.php");

// PHPMailer Dependencies
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libraries/phpmailer/src/Exception.php';
require_once __DIR__ . '/../libraries/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../libraries/phpmailer/src/SMTP.php';

$registerObj = new Register();
$errors = [];
$register = [];
$action = $_GET['action'] ?? 'register'; // Default to register if no action

// Helper function to send email (Same format as forgotPassController)
function sendOtpEmail($email, $name, $otp)
{
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'graziellamssaavedra06@gmail.com';
        $mail->Password = 'cpybynwckiipsszp';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('graziellamssaavedra06@gmail.com', 'Library Administration');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = "Account Verification Code - WMSU Library";
        $mail->Body = <<<EOT
            <div style="font-family: sans-serif; padding: 20px; background-color: #f4f4f7;">
                <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; text-align: center;">
                    <h2>Welcome to WMSU Library!</h2>
                    <p>Hello {$name},</p>
                    <p>Thank you for registering. Please use the code below to verify your email address:</p>
                    <h1 style="letter-spacing: 5px; background: #eee; padding: 10px; border-radius: 5px; display: inline-block;">{$otp}</h1>
                    <p style="color: #cc0000;">This code expires in 10 minutes.</p>
                </div>
            </div>
EOT;
        $mail->send();
        return true;

    } catch (Exception $e) {
        return false;
    }
}

// ==========================================================
// ACTION 1: REGISTER (Submit Form)
// ==========================================================
if ($action === 'register' && $_SERVER["REQUEST_METHOD"] == "POST") {

    $register["borrowerTypeID"] = trim(htmlspecialchars($_POST["borrowerTypeID"] ?? ''));
    $register["lName"] = trim(htmlspecialchars($_POST["lName"] ?? ''));
    $register["fName"] = trim(htmlspecialchars($_POST["fName"] ?? ''));
    $register["middleIn"] = trim(htmlspecialchars($_POST["middleIn"] ?? ''));
    $register["id_number"] = trim(htmlspecialchars($_POST["id_number"] ?? ''));
    $register["departmentID"] = trim(htmlspecialchars($_POST["departmentID"] ?? ''));
    $register["contact_no"] = trim(htmlspecialchars($_POST["contact_no"] ?? ''));
    $register["email"] = trim(htmlspecialchars($_POST["email"] ?? ''));
    $register["password"] = trim(htmlspecialchars($_POST["password"] ?? ''));
    $register["conPass"] = trim(htmlspecialchars($_POST["conPass"] ?? ''));
    $register["agreement"] = isset($_POST["agreement"]) ? trim(htmlspecialchars($_POST["agreement"])) : "";

    $register["imageID_name"] = $_FILES["imageID"]["name"] ?? '';
    $upload_dir = __DIR__ . "/../../public/uploads/id_images/";
    $register["imageID_dir"] = $upload_dir . basename($register["imageID_name"]);

    // --- VALIDATION START ---
    if (empty($register["borrowerTypeID"]))
        $errors["borrowerTypeID"] = "Please Choose From the Following";
    if (empty($register["lName"]))
        $errors["lName"] = "Last Name is required";
    if (empty($register["fName"]))
        $errors["fName"] = "First Name is required";

    if ($register["borrowerTypeID"] == 1 || $register["borrowerTypeID"] == 2) {
        if (empty($register["id_number"]))
            $errors["id_number"] = "ID Number is required";
        elseif (!is_numeric($register["id_number"]))
            $errors["id_number"] = "ID Number Format is Invalid";
        if (empty($register["departmentID"]))
            $errors["departmentID"] = "College/Department is required";
    }

    if (empty($register["contact_no"]))
        $errors["contact_no"] = "Contact Number is required";
    elseif (!is_numeric($register["contact_no"]) || strlen($register["contact_no"]) != 11)
        $errors["contact_no"] = "Contact Number Format is Invalid";

    if (empty($register["email"]))
        $errors["email"] = "Email is required";
    else if ($registerObj->isEmailExist($register["email"]))
        $errors["email"] = "Email already exist";

    if (empty($register["password"]))
        $errors["password"] = "Password is required";
    if (empty($register["conPass"]))
        $errors["conPass"] = "Please Confirm Your Password";
    else if ($register["password"] !== $register["conPass"])
        $errors["conPass"] = "Passwords do not match";

    if (empty($register["agreement"]))
        $errors["agreement"] = "You must Agree to the Terms and Conditions";

    if (empty($register["imageID_name"]) || $_FILES["imageID"]["error"] == UPLOAD_ERR_NO_FILE) {
        $errors["imageID"] = "Upload ID Image is required";
    } elseif ($_FILES["imageID"]["error"] !== UPLOAD_ERR_OK) {
        $errors["imageID"] = "File upload failed (Code: " . $_FILES["imageID"]["error"] . ")";
    }
    // --- VALIDATION END ---

    if (empty(array_filter($errors))) {
        if (move_uploaded_file($_FILES["imageID"]["tmp_name"], $register["imageID_dir"])) {

            $registerObj->borrowerTypeID = $register["borrowerTypeID"];
            $registerObj->lName = $register["lName"];
            $registerObj->fName = $register["fName"];
            $registerObj->middleIn = empty($register["middleIn"]) ? NULL : $register["middleIn"];
            $registerObj->id_number = empty($register["id_number"]) ? NULL : $register["id_number"];
            $registerObj->departmentID = empty($register["departmentID"]) ? NULL : $register["departmentID"];
            $registerObj->imageID_name = $register["imageID_name"];
            $registerObj->imageID_dir = "public/uploads/id_images/" . basename($register["imageID_name"]);
            $registerObj->contact_no = $register["contact_no"];
            $registerObj->email = $register["email"];
            $registerObj->password = $register["password"];
            $registerObj->date_registered = date("Y-m-d");

            // GENERATE OTP
            $otp = rand(100000, 999999);
            $expiry = date("Y-m-d H:i:s", time() + (60 * 10)); // 10 Minutes Expiry

            $registerObj->verification_code = $otp;
            $registerObj->verification_expiry = $expiry;

            if ($registerObj->addUser()) {
                // Send Email
                $fullName = $register["fName"] . ' ' . $register["lName"];
                sendOtpEmail($register["email"], $fullName, $otp);

                // Set session for verification step
                $_SESSION['verify_email'] = $register["email"];

                // Redirect to Verify View
                header("Location: ../../app/views/borrower/register.php?view=verify");
                exit;
            } else {
                if (file_exists($register["imageID_dir"]))
                    unlink($register["imageID_dir"]);
                $_SESSION["errors"] = ["general" => "Registration failed due to a database error."];
                $_SESSION["old"] = $register;
                header("Location: ../../app/views/borrower/register.php");
                exit;
            }
        } else {
            $errors["imageID"] = "Failed to save the uploaded image.";
        }
    }

    $_SESSION["errors"] = $errors;
    $_SESSION["old"] = $register;
    header("Location: ../../app/views/borrower/register.php");
    exit;
}

// ==========================================================
// ACTION 2: VERIFY OTP
// ==========================================================
else if ($action === 'verify_otp' && $_SERVER["REQUEST_METHOD"] == "POST") {

    $otp = implode("", $_POST['otp'] ?? []);
    if (empty($otp))
        $otp = trim($_POST['otp_code'] ?? '');

    $email = $_SESSION['verify_email'] ?? '';

    if (empty($email) || empty($otp)) {
        $_SESSION['otp_error'] = "Invalid session or missing code.";
        header("Location: ../../app/views/borrower/register.php?view=verify");
        exit;
    }

    // Verify against DB
    $user = $registerObj->verifyEmailOtp($email, $otp);

    if ($user) {
        // Success: Change status from 'Unverified' to 'Pending' (for Admin Review)
        $registerObj->markEmailVerified($email);

        unset($_SESSION['verify_email']);

        // Redirect to Final Success Page
        header("Location: ../../app/views/borrower/register.php?success=pending");
        exit;
    } else {
        $_SESSION['otp_error'] = "Invalid or expired code.";
        header("Location: ../../app/views/borrower/register.php?view=verify");
        exit;
    }
}
?>