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

// Get the status and user ID from the URL query
$status_message = $_GET['status'] ?? '';
$redirect_userID = $_GET['userID'] ?? null;
$open_modal = '';

if ($status_message === 'pending') {
    $open_modal = 'pendingModal';
} else if ($status_message === 'rejected') {
    $open_modal = 'rejectedModal';
} else if ($status_message === 'blocked') {
    $open_modal = 'blockedModal';
    // Use the ID passed in the URL to fetch the borrow details if the modal is blocked
    if ($redirect_userID) {
        // NOTE: We rely on the database access here instead of the session
        $userID = $redirect_userID;
        $user = $userObj->fetchUser($userID); // Fetch user data
        $borrow_detail = $borrowObj->fetchUserBorrowDetails($userID, 'unpaid');
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
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
    </style>
</head>

<body>
    <div class="color-layer"></div>

    <?php require_once(__DIR__ . '/../shared/header.php'); ?>

    <main class="flex justify-center items-center">
        <div class="form-container flex justify-center">
            <div class="info-section w-1/2 flex flex-col justify-center items-center">
                <div class="image">
                    <img src="../../../public/assets/images/bg.png" alt="Background Image">
                </div>
            </div>

            <div class="form-section w-1/2 flex flex-col justify-center items-center">
                <h1 class="font-extrabold">LOG IN</h1>
                <form action="../../../app/controllers/loginController.php" method="POST">
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

    <div id="pendingModal" class="modal <?= $open_modal == 'pendingModal' ? 'open' : '' ?>">
        <div class="modal-content text-center">
            <h2 class="text-2xl font-bold mb-4 text-orange-700">Account Pending</h2>

            <p class="mb-6 text-gray-700">
                Your account is currently <strong class="text-orange-600">Pending Approval</strong>.
            </p>
            <p class="mb-6 text-gray-700 font-semibold">
                Please wait for the administrator to review and confirm your registration.
            </p>

            <div class="flex justify-center mt-6">
                <button id="closePendingModalBtn"
                    class="bg-red-800 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <div id="rejectedModal" class="modal <?= $open_modal == 'rejectedModal' ? 'open' : '' ?>">
        <div class="modal-content text-center">
            <h2 class="text-2xl font-bold mb-4 text-red-700">Account Rejected</h2>

            <p class="mb-6 text-gray-700">
                Your account is <strong class="text-red-600">REJECTED</strong>.
            </p>
            <p class="mb-6 text-gray-700 font-semibold">
                Please contact for the administrator to review your registration.
            </p>

            <div class="flex justify-center mt-6">
                <button id="closeRejectedModalBtn"
                    class="bg-red-800 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <div id="blockedModal" class="modal <?= $open_modal == 'blockedModal' ? 'open' : '' ?>">
        <div class="modal-content text-center">
            <h2 class="text-2xl font-bold mb-4 text-red-700">Account Blocked</h2>

            <p class="mb-6 text-gray-700">
                Your account is <strong class="text-red-600">BLOCKED</strong>.
            </p>


            <?php if (is_array($borrow_detail) && !empty($borrow_detail)) { ?>
                <p class="mb-6 text-gray-700 font-semibold">
                    Please settle your unpaid fine.
                </p>
                <div class="flex justify-center mt-6">
                    <?php $_SESSION['temp_blocked_user_id'] = $userID; ?>
                    <a href="../../../app/views/borrower/blockedPage.php" class="text-red-800 px-6 py-3 font-semibold">
                        Check Unpaid ->
                    </a>
                </div>

            <?php } else { ?>
                <p class="mb-6 text-gray-700 font-semibold">
                    Please contact the administrator to review your account status.
                </p>
            <?php } ?>

            <div class="flex justify-center mt-6">
                <button id="closeBlockedModalBtn"
                    class="bg-red-800 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
</body>
<script src="../../../public/assets/js/header_footer.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const pendingModal = document.getElementById('pendingModal');
        const rejectedModal = document.getElementById('rejectedModal');
        const blockedModal = document.getElementById('blockedModal');

        // Function to close any modal and clean the URL (including the userID parameter)
        const closeModal = (modalElement) => {
            if (modalElement && modalElement.classList.contains('open')) {
                const url = new URL(window.location.href);
                url.searchParams.delete('status');
                url.searchParams.delete('userID'); // Clear the ID as well
                window.history.replaceState(null, '', url); // Clean the URL without reloading
                modalElement.classList.remove('open');
                modalElement.style.display = 'none';
            }
        };

        // Helper function to set up listeners
        const setupModalListeners = (modalElement, buttonId) => {
            if (modalElement.classList.contains('open')) {
                const closeBtn = document.getElementById(buttonId);

                // Close when clicking the button
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => closeModal(modalElement));
                }

                // Close when clicking outside the modal
                window.addEventListener('click', (e) => {
                    if (e.target === modalElement) {
                        closeModal(modalElement);
                    }
                });
            }
        };

        // Initialize listeners
        setupModalListeners(pendingModal, 'closePendingModalBtn');
        setupModalListeners(rejectedModal, 'closeRejectedModalBtn');
        setupModalListeners(blockedModal, 'closeBlockedModalBtn');
    });
</script>

</html>