<?php
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/catalogue.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../../models/manageUsers.php");

$userObj = new User();
$borrowObj = new BorrowDetails();

$userID = null;
$user = null;
$borrow_detail = null;

$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["errors"]);

$success = $_SESSION["success"] ?? "";
unset($_SESSION["success"]);

$reset_email = $_SESSION["reset_email"] ?? "";

$current_view = $_GET['view'] ?? 'login';

if ($current_view === 'verify' && empty($reset_email)) {
    $current_view = 'forgot';
}
if ($current_view === 'reset' && (empty($_SESSION['otp_verified']) || empty($reset_email))) {
    $current_view = 'forgot'; 
}

$status_message = $_GET['status'] ?? '';
$redirect_userID = $_GET['userID'] ?? null;
$open_modal = '';

if ($status_message === 'pending') {
    $open_modal = 'pendingModal';
} else if ($status_message === 'rejected') {
    $open_modal = 'rejectedModal';
} else if ($status_message === 'blocked') {
    $open_modal = 'blockedModal';
    if ($redirect_userID) {
        $userID = $redirect_userID;
        $user = $userObj->fetchUser($userID);
        $borrow_detail = $borrowObj->fetchUserBorrowDetails($userID, 'unpaid');
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php 
            if($current_view == 'login') echo 'Log In';
            elseif($current_view == 'forgot') echo 'Forgot Password';
            elseif($current_view == 'verify') echo 'Verify OTP';
            elseif($current_view == 'reset') echo 'Reset Password';
        ?>
    </title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/login.css">
    <link rel="stylesheet" href="../../../public/assets/css/header_footer2.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
        }

        .modal.open {
            display: flex;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .hidden-section {
            display: none !important;
        }
    </style>
</head>

<body>
    <div class="color-layer"></div>

    <?php require_once(__DIR__ . '/../shared/header.php'); ?>

    <main id="loginSection" class="flex justify-center items-center <?= $current_view === 'login' ? '' : 'hidden-section' ?>">
        <div class="form-container flex justify-center">
            <div class="info-section w-1/2 flex flex-col justify-center items-center">
                <div class="image">
                    <img src="../../../public/assets/images/bg.png" alt="Background Image">
                </div>
            </div>

            <div class="form-section w-1/2 flex flex-col justify-center items-center">
                <h1 class="font-extrabold">LOG IN</h1>
                <form action="../../../app/controllers/loginController.php" method="POST">
                    
                    <?php if (!empty($success)): ?>
                        <div class="p-3 mb-4 text-sm text-green-700 bg-green-100 rounded-lg text-center w-full">
                            <?= $success ?>
                        </div>
                    <?php endif; ?>

                    <p class="errors" name="invalid"><?= $errors["invalid"] ?? "" ?></p>

                    <div class="input">
                        <label for="email">Email:</label>
                        <input type="text" class="input-field" name="email">
                        <p class="errors"><?= $errors["email"] ?? "" ?></p>
                    </div>
                    <div class="input">
                        <label for="password">Password:</label>
                        <input type="password" class="input-field" name="password">
                        <p class="errors"><?= $errors["password"] ?? "" ?></p>
                        <a href="#" id="triggerForgot" class="text-blue-800 underline">Forgot Password?</a>
                    </div>

                    <br>
                    <input type="submit" value="Log In" class="font-bold cursor-pointer mb-8 border-none rounded-lg">

                    <div class="register py-5 text-center flex justify-center font-bold">
                        <p>Don't Have an Account Yet?
                            <span><a href="../../../app/views/borrower/register.php">Register Account</a></span>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <main id="forgotSection" class="flex justify-center items-center <?= $current_view === 'forgot' ? '' : 'hidden-section' ?>">
        <div class="form-container flex justify-center">
            
            <div class="info-section w-1/2 hidden md:flex flex-col justify-center items-center bg-gray-50">
                <div class="image">
                    <img src="../../../public/assets/images/bg.png" alt="Background Image" class="max-w-full h-auto">
                </div>
            </div>

            <div class="form-section md:w-1/2 flex flex-col justify-center items-center p-8">
                <h1 class="font-extrabold text-2xl mb-6">Forgot Password</h1>
                <p class="text-center mb-4 text-sm text-gray-600">Enter your email to receive a verification code.</p>
                
                <form action="../../../app/controllers/forgotPassController.php?action=send_otp" method="POST" class="w-full">
                    
                    <?php if (!empty($errors["invalid"])): ?>
                        <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg text-center">
                            <?= $errors["invalid"] ?>
                        </div>
                    <?php endif; ?>

                    <div class="input mb-6">
                        <label for="email" class="block mb-1 font-medium">Email:</label>
                        <input type="email" class="input-field w-full border p-2 rounded" name="email" placeholder="Please enter your email" required>
                    </div>

                    <input type="submit" value="Send Code" class="font-bold cursor-pointer mb-8 border-none rounded-lg">
                    
                    <div class="text-center">
                        <a href="#" id="triggerLogin" class="text-sm text-gray-500 hover:text-blue-600">Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <main id="verifySection" class="flex justify-center items-center <?= $current_view === 'verify' ? '' : 'hidden-section' ?>">
        <div class="form-container flex justify-center">
            <div class="info-section w-1/2 hidden md:flex flex-col justify-center items-center bg-gray-50">
                <div class="image">
                    <img src="../../../public/assets/images/bg.png" alt="Background" class="max-w-full h-auto">
                </div>
            </div>

            <div class="form-section w-full md:w-1/2 flex flex-col justify-center items-center p-8">
                <h1 class="font-extrabold text-2xl mb-2">Check your Email</h1>
                <p class="text-sm text-gray-500 mb-2 text-center">
                    We sent a 6-digit code to <br><strong><?= htmlspecialchars($reset_email) ?></strong>
                </p>
                
                <form action="../../../app/controllers/forgotPassController.php?action=verify_otp" method="POST" class="w-full">
                    
                    <?php if (!empty($success)): ?>
                        <div class="p-3 mb-4 text-sm text-green-700 bg-green-100 rounded-lg text-center">
                            <?= $success ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors["otp"])): ?>
                        <div class="p-3 mb-2 text-sm text-red-700 bg-red-100 rounded-lg text-center">
                            <?= $errors["otp"] ?>
                        </div>
                    <?php endif; ?>

                    <div class="input mb-6">
                        <label for="otp_code" class="block mb-1 font-medium">Enter Verification Code:</label>
                        <input type="text" 
                               name="otp_code" 
                               class="input-field w-full border p-2 rounded text-center text-lg tracking-widest" 
                               placeholder="e.g. 123456" 
                               maxlength="6" 
                               pattern="\d{6}"
                               required 
                               autofocus>
                    </div>

                    <input type="submit" value="Verify Code" class="font-bold cursor-pointer mb-4 border-none rounded-lg">
                    
                    <div class="text-center">
                        <p id="timerText" class="text-sm text-gray-500">
                            Resend code in <span id="timer" class="font-bold text-red-600">02:00</span>
                        </p>
                        
                        <a href="../../../app/controllers/forgotPassController.php?action=resend_otp" 
                           id="resendBtn" 
                           class="hidden text-sm text-blue-600 hover:underline font-bold">
                           Resend OTP
                        </a>
                        
                        <div class="mt-4">
                            <a href="login.php" class="text-xs text-gray-400 hover:text-gray-600">Change Email</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <main id="resetSection" class="flex justify-center items-center <?= $current_view === 'reset' ? '' : 'hidden-section' ?>">
        <div class="form-container flex justify-center">

            <div class="info-section w-1/2 hidden md:flex flex-col justify-center items-center bg-gray-50">
                <div class="image">
                    <img src="../../../public/assets/images/bg.png" alt="Background Image" class="max-w-full h-auto">
                </div>
            </div>

            <div class="form-section w-full md:w-1/2 flex flex-col justify-center items-center p-8">
                <h1 class="font-extrabold text-2xl mb-6">Reset Password</h1>

                <form action="../../../app/controllers/forgotPassController.php?action=reset" method="POST" class="w-full">

                    <?php if (!empty($errors["invalid"])): ?>
                        <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg text-center">
                            <?= $errors["invalid"] ?>
                        </div>
                    <?php endif; ?>

                    <div class="w-full mb-4 text-center">
                        <p class="text-sm text-gray-500">Resetting password for:</p>
                        <p class="font-bold text-gray-700"><?= htmlspecialchars($reset_email) ?></p>
                    </div>

                    <div class="input mb-4">
                        <label for="password" class="block mb-1 font-medium">New Password:</label>
                        <input type="password" class="input-field w-full border p-2 rounded" name="password"
                            placeholder="Enter your new password" required>
                        <?php if (!empty($errors["password"])): ?>
                            <span class="text-xs text-red-600"><?= $errors["password"] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="input mb-6">
                        <label for="confirm_password" class="block mb-1 font-medium">Confirm New Password:</label>
                        <input type="password" class="input-field w-full border p-2 rounded" name="confirm_password"
                            placeholder="Confirm your new password" required>
                        <?php if (!empty($errors["confirm_password"])): ?>
                            <span class="text-xs text-red-600"><?= $errors["confirm_password"] ?></span>
                        <?php endif; ?>
                    </div>

                    <input type="submit" value="Update Password" class="font-bold cursor-pointer mb-8 border-none rounded-lg">

                </form>
            </div>
        </div>
    </main>

    <div id="pendingModal" class="modal <?= $open_modal == 'pendingModal' ? 'open' : '' ?>">
        <div class="modal-content text-center">
            <h2 class="text-2xl font-bold mb-4 text-orange-700">Account Pending</h2>
            <p class="mb-6 text-gray-700">Your account is currently <strong class="text-orange-600">Pending Approval</strong>.</p>
            <p class="mb-6 text-gray-700 font-semibold">Please wait for the administrator to review and confirm your registration.</p>
            <div class="flex justify-center mt-6">
                <button id="closePendingModalBtn" class="bg-red-800 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors">Close</button>
            </div>
        </div>
    </div>

    <div id="rejectedModal" class="modal <?= $open_modal == 'rejectedModal' ? 'open' : '' ?>">
        <div class="modal-content text-center">
            <h2 class="text-2xl font-bold mb-4 text-red-700">Account Rejected</h2>
            <p class="mb-6 text-gray-700">Your account is <strong class="text-red-600">REJECTED</strong>.</p>
            <p class="mb-6 text-gray-700 font-semibold">Please contact for the administrator to review your registration.</p>
            <div class="flex justify-center mt-6">
                <button id="closeRejectedModalBtn" class="bg-red-800 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors">Close</button>
            </div>
        </div>
    </div>

    <div id="blockedModal" class="modal <?= $open_modal == 'blockedModal' ? 'open' : '' ?>">
        <div class="modal-content text-center">
            <h2 class="text-2xl font-bold mb-4 text-red-700">Account Blocked</h2>
            <p class="mb-6 text-gray-700">Your account is <strong class="text-red-600">BLOCKED</strong>.</p>
            <?php if (is_array($borrow_detail) && !empty($borrow_detail)) { ?>
                <p class="mb-6 text-gray-700 font-semibold">Please settle your unpaid fine.</p>
                <div class="flex justify-center mt-6">
                    <?php $_SESSION['temp_blocked_user_id'] = $userID; ?>
                    <a href="../../../app/views/borrower/blockedPage.php" class="text-red-800 px-6 py-3 font-semibold">Check Unpaid -></a>
                </div>
            <?php } else { ?>
                <p class="mb-6 text-gray-700 font-semibold">Please contact the administrator to review your account status.</p>
            <?php } ?>
            <div class="flex justify-center mt-6">
                <button id="closeBlockedModalBtn" class="bg-red-800 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors">Close</button>
            </div>
        </div>
    </div>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
</body>
<script src="../../../public/assets/js/header_footer.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- VIEW TOGGLE LOGIC ---
        const loginSection = document.getElementById('loginSection');
        const forgotSection = document.getElementById('forgotSection');
        const triggerForgot = document.getElementById('triggerForgot');
        const triggerLogin = document.getElementById('triggerLogin');

        if(triggerForgot){
            triggerForgot.addEventListener('click', function(e){
                e.preventDefault();
                document.querySelectorAll('main').forEach(el => el.classList.add('hidden-section'));
                forgotSection.classList.remove('hidden-section');
                
                const url = new URL(window.location);
                url.searchParams.set('view', 'forgot');
                window.history.pushState({}, '', url);
            });
        }

        if(triggerLogin){
            triggerLogin.addEventListener('click', function(e){
                e.preventDefault();
                document.querySelectorAll('main').forEach(el => el.classList.add('hidden-section'));
                loginSection.classList.remove('hidden-section');

                const url = new URL(window.location);
                url.searchParams.set('view', 'login');
                window.history.pushState({}, '', url);
            });
        }
        
        // --- TIMER LOGIC (Only runs if we are on the verify section) ---
        const timerElement = document.getElementById('timer');
        const timerText = document.getElementById('timerText');
        const resendBtn = document.getElementById('resendBtn');
        const verifySection = document.getElementById('verifySection');

        // Check if verify section is visible
        if (verifySection && !verifySection.classList.contains('hidden-section')) {
            let timeLeft = 120; // 2 minutes in seconds

            const countdown = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    // Hide timer text, show resend button
                    if(timerText) timerText.style.display = 'none';
                    if(resendBtn) resendBtn.classList.remove('hidden');
                } else {
                    timeLeft--;
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    // Format as 02:00
                    if(timerElement) {
                        timerElement.textContent = 
                            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    }
                }
            }, 1000);
        }
        // ------------------------

        // --- MODAL LOGIC (EXISTING) ---
        const pendingModal = document.getElementById('pendingModal');
        const rejectedModal = document.getElementById('rejectedModal');
        const blockedModal = document.getElementById('blockedModal');

        const closeModal = (modalElement) => {
            if (modalElement && modalElement.classList.contains('open')) {
                const url = new URL(window.location.href);
                url.searchParams.delete('status');
                url.searchParams.delete('userID'); 
                window.history.replaceState(null, '', url); 
                modalElement.classList.remove('open');
                modalElement.style.display = 'none';
            }
        };

        const setupModalListeners = (modalElement, buttonId) => {
            if (modalElement.classList.contains('open')) {
                const closeBtn = document.getElementById(buttonId);
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => closeModal(modalElement));
                }
                window.addEventListener('click', (e) => {
                    if (e.target === modalElement) {
                        closeModal(modalElement);
                    }
                });
            }
        };

        setupModalListeners(pendingModal, 'closePendingModalBtn');
        setupModalListeners(rejectedModal, 'closeRejectedModalBtn');
        setupModalListeners(blockedModal, 'closeBlockedModalBtn');
    });
</script>

</html>