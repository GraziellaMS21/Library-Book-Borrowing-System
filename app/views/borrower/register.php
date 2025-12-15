<?php
session_start();
$errors = $_SESSION["errors"] ?? [];
$register = $_SESSION["old"] ?? [];
unset($_SESSION["errors"], $_SESSION["old"]);

require_once(__DIR__ . "/../../models/userRegister.php");
$registerObj = new Register();
$userTypes = $registerObj->fetchUserType();
$departments = $registerObj->fetchDepartments();

// Check for parameters in the URL
$current_modal = $_GET['modal'] ?? '';
$success_message = $_GET['success'] ?? '';
$view_mode = $_GET['view'] ?? '';
$open_modal = '';

if ($success_message === 'pending') {
    $open_modal = 'successPendingModal';
} else if ($current_modal === 'terms') {
    $open_modal = 'termsModal';
} else if ($view_mode === 'verify') {
    $open_modal = 'verifyEmailModal';
}

// Get OTP specific errors/success if any
$otp_error = $_SESSION['otp_error'] ?? '';
$otp_success = $_SESSION['otp_success'] ?? ''; // New: For resend success message
unset($_SESSION['otp_error'], $_SESSION['otp_success']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Account</title>
    <link rel="stylesheet" href="../../../public/assets/css/login_register.css" />
    <link rel="stylesheet" href="../../../public/assets/css/header_footer.css" />
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <style>
        /* Shared Modal Styles (If not already in CSS) */
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
            padding: 30px;
            border: 1px solid #888;
            width: 90%;
            max-width: 450px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            position: relative;
        }
    </style>
</head>

<body>

    <?php require_once(__DIR__ . '/../shared/header.php'); ?>

    <main class="flex justify-center items-center">
        <div class="form-container flex justify-center">
            <div class="info-section w-1/2 flex flex-col justify-center items-center">
                <div class="image">
                    <img src="../../../public/assets/images/bg.png" alt="">
                </div>
            </div>

            <div class="form-section w-1/2 flex flex-col justify-center items-center">
                <h1 class="font-extrabold">REGISTER YOUR ACCOUNT</h1>

                <form action="../../../app/controllers/registerController.php?action=register" method="POST"
                    enctype="multipart/form-data">
                    <div class="borrowerType">
                        <label for="borrowerType">Register as? <span>*</span></label>
                        <select name="borrowerTypeID" id="borrowerType" class="h-12 w-full rounded-lg">
                            <option value="">--Select--</option>
                            <?php
                            foreach ($userTypes as $type) {
                                ?>
                                <option value="<?= $type["borrowerTypeID"] ?>" <?= isset($register["borrowerTypeID"]) && $register["borrowerTypeID"] == $type["borrowerTypeID"] ? "selected" : "" ?>>
                                    <?= $type["borrower_type"] ?>
                                </option>
                                <?php
                            }
                            ?>
                        </select>
                        <p class="errors"><?= $errors["borrowerTypeID"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="lName">Last Name <span>*</span> : </label>
                        <input type="text" class="input-field" name="lName" id="lName" placeholder="e.g. Dela Cruz"
                            value="<?= $register["lName"] ?? "" ?>">
                        <p class="errors"><?= $errors["lName"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="fName">First Name<span>*</span> : </label>
                        <input type="text" class="input-field" name="fName" id="fName" placeholder="e.g. Juan"
                            value="<?= $register["fName"] ?? "" ?>">
                        <p class="errors"><?= $errors["fName"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="middleIn">Middle Initial : </label>
                        <input type="text" class="input-field" name="middleIn" id="middleIn" placeholder="e.g. M."
                            value="<?= $register["middleIn"] ?? "" ?>">
                        <p class="errors"><?= $errors["middleIn"] ?? "" ?></p>
                    </div>
                    <div class="input" id="id_number_container">
                        <label for="id_number" id="id_numberLabel">ID Number<span>*</span> : </label>
                        <input type="text" class="input-field" name="id_number" placeholder="e.g. 20241234"
                            value="<?= $register["id_number"] ?? "" ?>">
                        <p class="errors"><?= $errors["id_number"] ?? "" ?></p>
                    </div>
                    <div class="input" id="departmentID">
                        <label for="college_department" id="collegeLabel">College/Department<span>*</span> : </label>
                        <select name="departmentID" class="input-field">
                            <option value="">--Select Department--</option>
                            <?php
                            foreach ($departments as $dept) {
                                ?>
                                <option value="<?= $dept["departmentID"] ?>" <?= isset($register["departmentID"]) && $register["departmentID"] == $dept["departmentID"] ? "selected" : "" ?>>
                                    <?= $dept["department_name"] ?>
                                </option>
                                <?php
                            }
                            ?>
                        </select>

                        <p class="errors"><?= $errors["departmentID"] ?? "" ?></p>
                    </div>
                    <div class="input" id="imageID">
                        <label for="imageID" id="uploadLabel"></label>
                        <input type="file" name="imageID" id="imageID" accept="image/*">
                        <p class="errors"><?= $errors["imageID"] ?? "" ?></p>
                    </div>
                    <div class="input">
                        <label for="contact_no">Contact Number<span>*</span> : </label>
                        <input type="text" class="input-field" name="contact_no" id="contact_no"
                            placeholder="e.g. 09xxxxxxxxx" value="<?= $register["contact_no"] ?? "" ?>">
                        <p class="errors"><?= $errors["contact_no"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="email">Email<span>*</span> : </label>
                        <p id="emailMessage"></p>
                        <input type="text" class="input-field" name="email" id="email"
                            placeholder="e.g. juandelacruz123@gmail.com" value="<?= $register["email"] ?? "" ?>">
                        <p class="errors"><?= $errors["email"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="password">Password<span>*</span> : </label>
                        <input type="password" class="input-field" name="password" id="password"
                            value="<?= $register["password"] ?? "" ?>">
                        <p class="errors"><?= $errors["password"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="conPass">Confirm Password<span>*</span> : </label>
                        <input type="password" class="input-field" name="conPass" id="conPass"
                            value="<?= $register["conPass"] ?? "" ?>">
                        <p class="errors"><?= $errors["conPass"] ?? "" ?></p>
                    </div>

                    <div class="agreement pt-5">
                        <input type="checkbox" name="agreement" id="agreement" value="<?= isset($register["agreement"]) ? "checked" : "" ?>>
                        <label for=" agreement"> I agree to the</label>
                        <button type="button" data-modal-target="termsModal" data-modal-toggle="termsModal"
                            id="openModal" class="terms-and-con text-xs text-blue-600 underline">Terms and
                            Conditions</button>
                        <p class="errors"><?= $errors["agreement"] ?? "" ?></p>
                    </div>

                    <div class="login-register py-5 flex justify-center font-bold">
                        <p>Already Have an Account? <span><a href="../../../app/views/borrower/login.php">Log
                                    In</a></span></p>
                    </div>
                    <br>
                    <input type="submit" value="Register Account"
                        class="font-bold cursor-pointer mb-8 border-none rounded-lg">
                </form>
            </div>
        </div>
        </div>
    </main>

    <div class="modal <?= $open_modal == 'termsModal' ? 'open' : '' ?>" id="termsModal">
        <div
            class="modal-content bg-white rounded-xl shadow-2xl w-11/12 md:w-3/4 lg:w-1/2 max-h-[90vh] flex flex-col transform transition-all scale-100">
            <div
                class="flex justify-between items-center p-5 border-b border-gray-200 bg-gray-50 rounded-t-xl sticky top-0 z-10">
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">Terms and Conditions</h2>
                <button id="closeModal"
                    class="text-gray-400 hover:text-red-600 transition-colors focus:outline-none p-1 rounded-full hover:bg-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto text-gray-700 space-y-6 leading-relaxed">
                <p>Please read these terms carefully...</p>
            </div>
            <div
                class="p-5 border-t border-gray-200 bg-gray-50 rounded-b-xl flex justify-end gap-3 sticky bottom-0 z-10">
                <button id="closeBtn" class="px-6 py-2.5 bg-red-800 text-white font-semibold rounded-lg">I Understand &
                    Close</button>
            </div>
        </div>
    </div>

    <div id="verifyEmailModal" class="modal <?= $open_modal == 'verifyEmailModal' ? 'open' : '' ?>">
        <div class="modal-content text-center p-8">
            <h2 class="text-2xl font-bold mb-2 text-gray-800">Verify Your Email</h2>
            <p class="mb-6 text-sm text-gray-600">
                We have sent a verification code to your email address.<br>
                Please enter the 6-digit code below.
            </p>

            <form action="../../../app/controllers/registerController.php?action=verify_otp" method="POST" class="!w-full">

                <?php if ($otp_success): ?>
                    <div class="p-3 mb-4 text-sm text-green-700 bg-green-100 rounded-lg text-center">
                        <?= $otp_success ?>
                    </div>
                <?php endif; ?>

                <div class="input mb-6">
                    <input type="text" name="otp_code"
                        class="input-field w-full border p-2 rounded text-center text-lg tracking-widest h-12"
                        placeholder="e.g. 123456" maxlength="6" pattern="\d{6}" required autofocus>
                </div>

                <?php if ($otp_error): ?>
                    <p class="text-red-600 mb-4 font-bold bg-red-100 p-2 rounded text-sm"><?= $otp_error ?></p>
                <?php endif; ?>

                <button type="submit"
                    class="bg-red-800 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 w-full transition-colors shadow-lg mb-4">
                    Verify Code
                </button>

                <div class="text-center">
                    <p id="timerText" class="text-sm text-gray-500">
                        Resend code in <span id="timer" class="font-bold text-red-600">02:00</span>
                    </p>

                    <a href="../../../app/controllers/registerController.php?action=resend_otp" id="resendBtn"
                        class="hidden text-sm text-blue-600 hover:underline font-bold">
                        Resend OTP
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div id="successPendingModal" class="modal <?= $open_modal == 'successPendingModal' ? 'open' : '' ?>">
        <div class="modal-content text-center">
            <h2 class="text-2xl font-bold mb-4 text-green-700">Email Verified!</h2>
            <p class="mb-6 text-gray-700">
                Thank you for verifying your email. Your account is now <strong class="text-green-600">Pending
                    Approval</strong>.
            </p>
            <p class="mb-6 text-gray-700 font-semibold">
                Please wait for the administrator to review your ID/Card validation. You can try logging in after a few
                hours.
            </p>
            <div class="flex justify-center mt-6">
                <a href="login.php"
                    class="bg-red-800 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors">
                    Go to Login
                </a>
            </div>
        </div>
    </div>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
</body>
<script src="../../../public/assets/js/header_footer.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Timer Logic for Verification Modal ---
        const verifyModal = document.getElementById('verifyEmailModal');
        const timerElement = document.getElementById('timer');
        const timerText = document.getElementById('timerText');
        const resendBtn = document.getElementById('resendBtn');

        // Only run timer if the verify modal is open
        if (verifyModal && verifyModal.classList.contains('open')) {
            let timeLeft = 120; // 2 minutes

            const countdown = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    if (timerText) timerText.style.display = 'none';
                    if (resendBtn) resendBtn.classList.remove('hidden');
                    if (resendBtn) resendBtn.style.display = 'inline-block'; // Ensure visibility
                } else {
                    timeLeft--;
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    if (timerElement) {
                        timerElement.textContent =
                            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    }
                }
            }, 1000);
        }

        // --- Existing Form Logic ---
        const id_number = document.getElementById('id_number_container');
        const select = document.getElementById('borrowerType');
        const college_department_div = document.getElementById('college_department');
        const uploadLabel = document.getElementById('uploadLabel');

        function updateFields() {
            const val = select.value;
            if (val === '1') {
                uploadLabel.innerHTML = "Upload COR Image<span>*</span> : ";
                college_department_div.classList.remove('hidden');
                id_number.classList.remove('hidden');
            } else if (val === '2') {
                uploadLabel.innerHTML = "Upload Employee ID Image<span>*</span> : ";
                college_department_div.classList.remove('hidden');
                id_number.classList.remove('hidden');
            }
            else if (val === '3') {
                uploadLabel.innerHTML = "Upload Valid ID Image<span>*</span> : ";
                college_department_div.classList.add('hidden');
                id_number.classList.add('hidden');
            }
        }

        if (select) {
            updateFields();
            select.addEventListener('change', updateFields);
        }

        // --- Success Modal Logic ---
        const successModal = document.getElementById('successPendingModal');
        if (successModal && successModal.classList.contains('open')) {
            const closeModal = () => {
                const url = new URL(window.location.href);
                url.searchParams.delete('success');
                window.history.replaceState(null, '', url);
                successModal.classList.remove('open');
                successModal.style.display = 'none';
            };
            const loginBtn = successModal.querySelector('a');
            if (loginBtn) loginBtn.addEventListener('click', () => { closeModal(); });
            window.addEventListener('click', (e) => { if (e.target === successModal) closeModal(); });
        }

        // --- Terms Modal Logic ---
        const openModal = document.getElementById("openModal");
        const closeModalTerms = document.getElementById("closeModal");
        const closeBtnTerms = document.getElementById("closeBtn");
        const modalTerms = document.getElementById("termsModal");

        if (openModal) openModal.addEventListener("click", () => modalTerms.classList.add("open"));
        if (closeModalTerms) closeModalTerms.addEventListener("click", () => modalTerms.classList.remove("open"));
        if (closeBtnTerms) closeBtnTerms.addEventListener("click", () => modalTerms.classList.remove("open"));
        if (modalTerms) modalTerms.addEventListener("click", (e) => {
            if (e.target === modalTerms) modalTerms.classList.remove("open");
        });
    });
</script>

</html>