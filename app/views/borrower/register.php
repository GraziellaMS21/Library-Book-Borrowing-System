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
$view_mode = $_GET['view'] ?? ''; // NEW: Check if we are in verification mode
$open_modal = '';

if ($success_message === 'pending') {
    $open_modal = 'successPendingModal';
} else if ($current_modal === 'terms') {
    $open_modal = 'termsModal';
} else if ($view_mode === 'verify') {
    $open_modal = 'verifyEmailModal'; // NEW: Set modal to verify email
}

// Get OTP specific errors if any
$otp_error = $_SESSION['otp_error'] ?? '';
unset($_SESSION['otp_error']);
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
        /* NEW STYLES: For OTP Input Fields */
        .otp-input-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .otp-input {
            width: 45px;
            height: 55px;
            text-align: center;
            font-size: 24px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }

        .otp-input:focus {
            border-color: #991b1b;
            background-color: #fff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(153, 27, 27, 0.1);
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
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-6 h-6 text-red-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Terms and Conditions
                </h2>
                <button id="closeModal"
                    class="text-gray-400 hover:text-red-600 transition-colors focus:outline-none p-1 rounded-full hover:bg-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div class="p-6 overflow-y-auto text-gray-700 space-y-6 leading-relaxed">
                <p class="text-sm text-gray-500 italic border-l-4 border-red-800 pl-3">
                    Please read these terms carefully before creating an account.
                </p>

                <section>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">1. Acceptance of Terms</h3>
                    <p class="text-sm text-gray-600">
                        By registering an account in the Library Book Borrowing System, you agree to comply with and be
                        bound by these Terms and Conditions. If you do not agree with any part of these terms, please do
                        not create an account or use the system.
                    </p>
                </section>

                <section>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">2. Account Registration</h3>
                    <ul class="list-disc list-outside pl-5 space-y-1 text-sm text-gray-600 marker:text-red-800">
                        <li>You must provide accurate, complete, and up-to-date information during registration.</li>
                        <li>You agree not to use false information, another person’s identity, or unauthorized
                            credentials.</li>
                        <li>Each user is allowed to create only one account. Duplicate or fraudulent accounts will be
                            removed or suspended.</li>
                    </ul>
                </section>

                <section>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">3. Use of the System</h3>
                    <ul class="list-disc list-outside pl-5 space-y-1 text-sm text-gray-600 marker:text-red-800">
                        <li>The system is intended solely for borrowing and returning library books.</li>
                        <li>You agree not to use the system for illegal, fraudulent, or abusive activities.</li>
                        <li>Any attempt to manipulate data, bypass system security, or misuse privileges may result in
                            account suspension or legal action.</li>
                    </ul>
                </section>

                <section>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">4. Account Responsibility</h3>
                    <ul class="list-disc list-outside pl-5 space-y-1 text-sm text-gray-600 marker:text-red-800">
                        <li>You are responsible for maintaining the confidentiality of your account credentials.</li>
                        <li>If you suspect unauthorized access, you must immediately notify library staff.</li>
                        <li>You are liable for any activity that occurs under your account until reported.</li>
                    </ul>
                </section>

                <section>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">5. Privacy and Data Protection</h3>
                    <ul class="list-disc list-outside pl-5 space-y-1 text-sm text-gray-600 marker:text-red-800">
                        <li>The library respects your privacy and protects your personal information in accordance with
                            the Data Privacy Act of 2012 (RA 10173).</li>
                        <li>Information collected (such as name, ID number, email address, and borrowing history) will
                            only be used for official library transactions.</li>
                        <li>Your data will not be shared or disclosed without your consent, except as required by law or
                            internal policy compliance.</li>
                    </ul>
                </section>

                <section>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">6. Violation and Suspension</h3>
                    <ul class="list-disc list-outside pl-5 space-y-1 text-sm text-gray-600 marker:text-red-800">
                        <li>Violation of any part of these Terms may result in temporary or permanent suspension of your
                            account.</li>
                        <li>The library reserves the right to take disciplinary or legal action for serious offenses,
                            such as system tampering or misuse of borrowed materials.</li>
                    </ul>
                </section>

                <section class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900 mb-3">7. Contact Information</h3>
                    <p class="text-sm text-gray-600 mb-2">For inquiries or assistance regarding your account or these
                        terms, please contact:</p>
                    <div class="text-sm">
                        <p class="font-bold text-gray-800">Library Administration</p>
                        <p class="flex items-center gap-2 mt-1">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                </path>
                            </svg>
                            <span class="text-blue-600">library@wmsu.edu.ph</span>
                        </p>
                        <p class="flex items-start gap-2 mt-1">
                            <svg class="w-4 h-4 text-gray-500 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="text-gray-600">WMSU Main Campus Library, Normal Road, Zamboanga City</span>
                        </p>
                    </div>
                </section>
            </div>

            <div
                class="p-5 border-t border-gray-200 bg-gray-50 rounded-b-xl flex justify-end gap-3 sticky bottom-0 z-10">
                <button id="closeBtn"
                    class="px-6 py-2.5 bg-red-800 hover:bg-red-900 text-white font-semibold rounded-lg shadow-md transition-all duration-200 transform hover:scale-105 focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    I Understand & Close
                </button>
            </div>
        </div>
    </div>

    <div id="verifyEmailModal" class="modal <?= $open_modal == 'verifyEmailModal' ? 'open' : '' ?>">
        <div class="modal-content text-center">
            <h2 class="text-2xl font-bold mb-4 text-gray-800">Verify Your Email</h2>
            <p class="mb-6 text-gray-600">
                We have sent a verification code to your email address.<br>
                Please enter the 6-digit code below to complete your registration.
            </p>

            <form action="../../../app/controllers/registerController.php?action=verify_otp" method="POST">
                <div class="otp-input-group">
                    <input type="text" name="otp[]" class="otp-input" maxlength="1"
                        oninput="this.value=this.value.replace(/[^0-9]/g,''); if(this.value.length==1) this.nextElementSibling.focus()"
                        required>
                    <input type="text" name="otp[]" class="otp-input" maxlength="1"
                        oninput="this.value=this.value.replace(/[^0-9]/g,''); if(this.value.length==1) this.nextElementSibling.focus()"
                        required>
                    <input type="text" name="otp[]" class="otp-input" maxlength="1"
                        oninput="this.value=this.value.replace(/[^0-9]/g,''); if(this.value.length==1) this.nextElementSibling.focus()"
                        required>
                    <input type="text" name="otp[]" class="otp-input" maxlength="1"
                        oninput="this.value=this.value.replace(/[^0-9]/g,''); if(this.value.length==1) this.nextElementSibling.focus()"
                        required>
                    <input type="text" name="otp[]" class="otp-input" maxlength="1"
                        oninput="this.value=this.value.replace(/[^0-9]/g,''); if(this.value.length==1) this.nextElementSibling.focus()"
                        required>
                    <input type="text" name="otp[]" class="otp-input" maxlength="1"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'')" required>
                </div>

                <?php if ($otp_error): ?>
                    <p class="text-red-600 mb-4 font-bold bg-red-100 p-2 rounded"><?= $otp_error ?></p>
                <?php endif; ?>

                <button type="submit"
                    class="bg-red-800 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 w-full transition-colors shadow-lg">
                    Verify Code
                </button>
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
    //email message js
    document.addEventListener('DOMContentLoaded', function () {
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
        updateFields();
        select.addEventListener('change', updateFields);

        // --- Custom JS for the success modal ---
        const successModal = document.getElementById('successPendingModal');
        if (successModal.classList.contains('open')) {
            // Function to close the modal and remove the URL parameter
            const closeModal = () => {
                const url = new URL(window.location.href);
                url.searchParams.delete('success');
                window.history.replaceState(null, '', url); // Clean the URL without reloading
                successModal.classList.remove('open');
                successModal.style.display = 'none';
            };

            // Close when clicking the login button (it redirects anyway, but cleans the URL history)
            const loginBtn = successModal.querySelector('a');
            loginBtn.addEventListener('click', () => {
                closeModal(); // Only cleans URL, actual redirect handled by <a> tag
            });

            // Close when clicking outside the modal
            window.addEventListener('click', (e) => {
                if (e.target === successModal) {
                    closeModal();
                }
            });
        }
    });

    //modal js for terms and conditions
    const openModal = document.getElementById("openModal");
    const closeModal = document.getElementById("closeModal");
    const closeBtn = document.getElementById("closeBtn");
    const modal = document.getElementById("termsModal");
    openModal.addEventListener("click", () => {
        modal.classList.add("open");

    })

    closeModal.addEventListener("click", () => {
        modal.classList.remove("open");

    })

    closeBtn.addEventListener("click", () => {
        modal.classList.remove("open");
    })

    modal.addEventListener("click", (e) => {
        if (e.target === modal) {
            modal.classList.remove("open");
        }
    });
</script>

</html>