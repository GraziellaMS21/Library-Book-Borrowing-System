<?php
session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/catalogue.php");
    exit;
}

$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["errors"]);

// Get the status from the URL query
$status_message = $_GET['status'] ?? '';
$open_modal = '';

if ($status_message === 'pending') {
    $open_modal = 'pendingModal';
}else if ($status_message === 'rejected') {
    $open_modal = 'rejectedModal';
}
// Note: We don't set a modal for 'blocked' since the controller redirects directly to blockedPage.php
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
        /* Basic modal styling (copied from register.php) */
        .modal {
            display: none;
            /* Hidden by default */
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

    <!-- NEW PENDING MODAL -->
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

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
</body>
<script src="../../../public/assets/js/header_footer.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Custom JS for the pending modal ---
        const pendingModal = document.getElementById('pendingModal');
        const rejectedModal = document.getElementById('rejectedModal');

        if (pendingModal.classList.contains('open')) {
            // Function to close the modal and remove the URL parameter
            const closeModal = () => {
                const url = new URL(window.location.href);
                url.searchParams.delete('status');
                window.history.replaceState(null, '', url); // Clean the URL without reloading
                pendingModal.classList.remove('open');
                pendingModal.style.display = 'none';
            };

            // Close when clicking the button
            const closeBtn = document.getElementById('closePendingModalBtn');
            closeBtn.addEventListener('click', closeModal);

            // Close when clicking outside the modal
            window.addEventListener('click', (e) => {
                if (e.target === pendingModal) {
                    closeModal();
                }
            });
        }

        if (rejectedModal.classList.contains('open')) {
            // Function to close the modal and remove the URL parameter
            const closeModal = () => {
                const url = new URL(window.location.href);
                url.searchParams.delete('status');
                window.history.replaceState(null, '', url); // Clean the URL without reloading
                rejectedModal.classList.remove('open');
                rejectedModal.style.display = 'none';
            };

            // Close when clicking the button
            const closeBtn = document.getElementById('closeRejectedModalBtn');
            closeBtn.addEventListener('click', closeModal);

            // Close when clicking outside the modal
            window.addEventListener('click', (e) => {
                if (e.target === rejectedModal) {
                    closeModal();
                }
            });
        }
    });
</script>

</html>
