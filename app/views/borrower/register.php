<?php
session_start();
$errors = $_SESSION["errors"] ?? [];
$register = $_SESSION["old"] ?? [];
unset($_SESSION["errors"], $_SESSION["old"]);

require_once(__DIR__ . "/../../models/userRegister.php");
$registerObj = new Register();
$userTypes = $registerObj->fetchUserType();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Account</title>
    <link rel="stylesheet" href="../../../public/assets/css/borrower/register.css" />
    <link rel="stylesheet" href="../../../public/assets/css/components/header_footer.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Licorice&display=swap" rel="stylesheet">
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
                <form action="../../../app/controllers/registerController.php" method="POST">
                    <div class="borrowerType">
                        <label for="borrowerType">Register as? <span>*</span></label>
                        <select name="userTypeID" id="borrowerType" class="h-12 w-full rounded-lg">
                            <option value="">--Select--</option>
                            <?php
                            foreach ($userTypes as $type) {
                                ?>
                                <option value="<?= $type["userTypeID"] ?>" <?= isset($register["userTypeID"]) && $register["userTypeID"] == $type["userTypeID"] ? "selected" : "" ?>>
                                    <?= $type["type_name"] ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <p class="errors"><?= $errors["userTypeID"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="lName">Last Name <span>*</span> : </label>
                        <input type="text" class="input-field" name="lName" id="lName"
                            value="<?= $register["lName"] ?? "" ?>">
                        <p class="errors"><?= $errors["lName"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="fName">First Name<span>*</span> : </label>
                        <input type="text" class="input-field" name="fName" id="fName"
                            value="<?= $register["fName"] ?? "" ?>">
                        <p class="errors"><?= $errors["fName"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="middleIn">Middle Initial : </label>
                        <input type="text" class="input-field" name="middleIn" id="middleIn"
                            value="<?= $register["middleIn"] ?? "" ?>">
                        <p class="errors"><?= $errors["middleIn"] ?? "" ?></p>
                    </div>
                    <div class="input hidden" id="college">
                        <label for="college" id="collegeLabel">College: </label>
                        <input type="text" class="input-field" name="college" value="<?= $register["college"] ?? "" ?>">
                        <p class="errors"><?= $errors["college"] ?? "" ?></p>
                    </div>
                    <div class="input hidden" id="department">
                        <label for="department">Department: </label>
                        <input type="text" class="input-field" name="department"
                            value="<?= $register["department"] ?? "" ?>">
                        <p class="errors"><?= $errors["department"] ?? "" ?></p>
                    </div>
                    <div class="input hidden" id="position">
                        <label for="position" id="positionLabel">Position: </label>
                        <input type="text" class="input-field" name="position"
                            value="<?= $register["position"] ?? "" ?>">
                        <p class="errors"><?= $errors["position"] ?? "" ?></p>
                    </div>
                    <div class="input">
                        <label for="contact_no">Contact Number<span>*</span> : </label>
                        <input type="text" class="input-field" name="contact_no" id="contact_no"
                            value="<?= $register["contact_no"] ?? "" ?>">
                        <p class="errors"><?= $errors["contact_no"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="email">Email<span>*</span> : </label>
                        <p id="emailMessage"></p>
                        <input type="text" class="input-field" name="email" id="email"
                            value="<?= $register["email"] ?? "" ?>">
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
                        <input type="checkbox" name="agreement" id="agreement" value="yes">
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
    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
</body>
<script src="../../../public/assets/js/borrower/register.js"></script>
<script src="../../../public/assets/js/components/header_footer.js"></script>

</html>