<?php
session_start();
$errors = $_SESSION["errors"] ?? [];
$register = $_SESSION["old"] ?? [];
unset($_SESSION["errors"], $_SESSION["old"]);

require_once(__DIR__ . "/../../models/userRegister.php");
$registerObj = new Register();
$userTypes = $registerObj->fetchUserType();

// Check for the success parameter in the URL
$current_modal = $_GET['modal'] ?? '';
$success_message = $_GET['success'] ?? '';
$open_modal = '';

if ($success_message === 'pending') {
    $open_modal = 'successPendingModal';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Account</title>
    <link rel="stylesheet" href="../../../public/assets/css/register.css" />
    <link rel="stylesheet" href="../../../public/assets/css/header_footer2.css" />
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
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

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
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
                    <img src="../../../public/assets/images/bg.png" alt="">
                </div>
            </div>

            <div class="form-section w-1/2 flex flex-col justify-center items-center">
                <h1 class="font-extrabold">REGISTER YOUR ACCOUNT</h1>

                <form action="../../../app/controllers/registerController.php" method="POST"
                    enctype="multipart/form-data">
                    <div class="borrowerType">
                        <label for="borrowerType">Register as? <span>*</span></label>
                        <select name="userTypeID" id="borrowerType" class="h-12 w-full rounded-lg">
                            <option value="">--Select--</option>
                            <?php
                            foreach ($userTypes as $type) {
                                ?>
                                <option value="<?= $type["userTypeID"] ?>" <?= isset($register["userTypeID"]) && $register["userTypeID"] == $type["userTypeID"] ? "selected" : "" ?>>
                                    <?= $type["type_name"] ?>
                                </option>
                                <?php
                            }
                            ?>
                        </select>
                        <p class="errors"><?= $errors["userTypeID"] ?? "" ?></p>
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
                    <div class="input" id="id_number">
                        <label for="id_number" id="id_numberLabel">ID Number<span>*</span> : </label>
                        <input type="text" class="input-field" name="id_number" placeholder="e.g. 20241234"
                            value="<?= $register["id_number"] ?? "" ?>">
                        <p class="errors"><?= $errors["id_number"] ?? "" ?></p>
                    </div>
                    <div class="input" id="college_department">
                        <label for="college_department" id="collegeLabel">College/Department<span>*</span> : </label>
                        <input type="text" class="input-field" name="college_department"
                            placeholder="e.g. College of Computing Studies"
                            value="<?= $register["college_department"] ?? "" ?>">
                        <p class="errors"><?= $errors["college_department"] ?? "" ?></p>
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
                        <input type="checkbox" name="agreement" id="agreement" value="yes"
                            <?= isset($register["agreement"]) ? "checked" : "" ?>>
                        <label for="agreement">I agree to the</label>
                        <button type="button" data-modal-target="termsModal" data-modal-toggle="termsModal"
                            id="openModal" class="terms-and-con text-xs text-blue-600 underline">Terms and
                            Conditions</button>
                        <p class="errors"><?= $errors["agreement"] ?? "" ?></p>
                    </div>

                    <div class="login py-5 flex justify-center font-bold">
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

    <div class="modal-bg fixed inset-0 hidden bg-black bg-opacity-50 flex justify-center items-center z-50"
        id="termsModal">
        <div
            class="modal-content bg-white rounded-lg w-11/12 md:w-2/3 lg:w-1/2 max-h-[80vh] overflow-y-auto p-6 relative shadow-lg">
            <button id="closeModal"
                class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-xl">&times;</button>

            <h2 class="text-xl font-semibold mb-4 p-6 flex text-gray-800 justify-center">TERMS AND CONDITIONS</h2>
            <div class="pb-6">
                <ol class="numbered-list list-decimal pl-5 space-y-4">
                    <li>Acceptance of Terms</li>
                    <p>By registering an account in the Library Book Borrowing System, you agree to comply with and be
                        bound by these Terms and Conditions. If you do not agree with any part of these terms, please do
                        not create an account or use the system.</p>
                    <li>Account Registration</li>
                    <ul>
                        <li>You must provide accurate, complete, and up-to-date information during registration.</li>
                        <li>You agree not to use false information, another person’s identity, or unauthorized
                            credentials.</li>
                        <li>Each user is allowed to create only one account. Duplicate or fraudulent accounts will be
                            removed or suspended.</li>
                    </ul>
                    <li>Use of the System</li>
                    <ul>
                        <li>The system is intended solely for borrowing and returning library books.</li>
                        <li>You agree not to use the system for illegal, fraudulent, or abusive activities.</li>
                        <li>Any attempt to manipulate data, bypass system security, or misuse privileges may result in
                            account suspension or legal action.</li>
                    </ul>
                    <li>Account Responsibility</li>
                    <ul>
                        <li>Any attempt to manipulate data, bypass system security, or misuse privileges may result in
                            account suspension or legal action.</li>
                        <li>If you suspect unauthorized access, you must immediately notify library staff.</li>
                        <li>Any attempt to manipulate data, bypass system security, or misuse privileges may result in
                            account suspension or legal action.</li>
                    </ul>
                    <li>Privacy and Data Protection</li>
                    <ul>
                        <li>The library respects your privacy and protects your personal information in accordance with
                            the Data Privacy Act of 2012 (RA 10173).</li>
                        <li>Information collected (such as name, ID number, email address, and borrowing history) will
                            only be used for official library transactions.</li>
                        <li>Your data will not be shared or disclosed without your consent, except as required by law or
                            internal policy compliance.</li>
                    </ul>
                    <li>Violation and Suspension</li>
                    <ul>
                        <li>Violation of any part of these Terms may result in temporary or permanent suspension of your
                            account.</li>
                        <li>The library reserves the right to take disciplinary or legal action for serious offenses,
                            such as system tampering or misuse of borrowed materials.</li>
                    </ul>
                    <li>Contact Information</li>
                    <ul>
                        <li class="font-bold">For inquiries or assistance regarding your account or these terms, please
                            contact:</li>
                        <li class="font-bold">Library Administrator</li>
                        <li class="list-none"><span class="font-bold">Email: </span>library@wmsu.edu.ph</li>
                        <li class="list-none"><span class="font-bold">Address: </span>WMSU Main Campus Library, Normal
                            Road, Zamboanga City</li>
                    </ul>
                </ol>
            </div>
            <button id="closeBtn" class="px-4 py-2 bg-red-800 text-white rounded transition-all hover:bg-red-600">
                Close
            </button>
        </div>

    </div>

    <!-- NEW SUCCESS MODAL -->
    <div id="successPendingModal" class="modal <?= $open_modal == 'successPendingModal' ? 'open' : '' ?>">
        <div class="modal-content text-center">
            <h2 class="text-2xl font-bold mb-4 text-green-700">Registration Successful!</h2>

            <p class="mb-6 text-gray-700">
                Thank you for registering. Your account is currently <strong class="text-green-600">Pending
                    Approval</strong>.
            </p>
            <p class="mb-6 text-gray-700 font-semibold">
                Please wait for the administrator to confirm your registration. You can try logging in after a few
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
        const id_number = document.getElementById('id_number');
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
        modal.classList.remove('hidden');
    })

    closeModal.addEventListener("click", () => {
        modal.classList.add('hidden');
    })

    closeBtn.addEventListener("click", () => {
        modal.classList.add('hidden');
    })

    modal.addEventListener("click", (e) => {
        if (e.target === modal) {
            modal.classList.add("hidden");
        }
    });
</script>

</html>