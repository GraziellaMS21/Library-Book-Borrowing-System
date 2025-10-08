<?php 
    require_once(__DIR__ . "/../../config/database.php");
    require_once(__DIR__ . "/../../classes/userRegister.php");
    $registerObj = new Register();

    
    $register = [];
    $errors = [];
    $borrowerTypes = $registerObj->fetchBorrowerType();
    
    

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $register["borrowerTypeID"] = trim(htmlspecialchars($_POST["borrowerTypeID"]));
        $register["lName"] = trim(htmlspecialchars($_POST["lName"]));
        $register["fName"] = trim(htmlspecialchars($_POST["fName"]));
        $register["middleIn"] = trim(htmlspecialchars($_POST["middleIn"]));
        $register["contactNo"] = trim(htmlspecialchars($_POST["contactNo"]));
        $register["email"] = trim(htmlspecialchars($_POST["email"]));
        $register["password"] = trim(htmlspecialchars($_POST["password"]));
        $register["conPass"] = trim(htmlspecialchars($_POST["conPass"]));
        $register["agreement"] = trim(htmlspecialchars($_POST["agreement"]));


         if (empty($register["borrowerTypeID"])) {
            $errors["borrowerTypeID"] = "Please Choose From the Following";
        }


        if (empty($register["lName"])) {
            $errors["lName"] = "Last Name is required";
        }

        if (empty($register["fName"])) {
            $errors["fName"] = "First Name is required";
        }

        if (empty($register["contactNo"])) {
            $errors["contactNo"] = "Contact Number is required";
        }elseif(!is_numeric($register["contactNo"]) || strlen($register["contactNo"])!=11){
            $errors["contactNo"] = "Contact Number Format is Invalid";
        }

        if (empty($register["email"])) {
            $errors["email"] = "Email is required";
        } else if ($registerObj->isEmailExist($register["email"])){
            $errors["email"] = "Email already exist";
            
        }
        
        if(filter_var($register["email"], FILTER_VALIDATE_EMAIL)){
            $domain = substr(strrchr($register["email"], "@"), 1);

            if(($register["borrowerTypeID"] == 1 || $register["borrowerTypeID"] == 2) && $domain !== "wmsu.edu.ph"){
                $errors["email"] = "Please use your WMSU email address";
            } else if (($register["borrowerTypeID"] == 3) && $domain !== "gmail.com"){
                $errors["email"] = "Invalid Email format";
            }
        } else  $errors["email"] = "Invalid Email format";  

        if (empty($register["password"])) {
            $errors["password"] = "Password is required";
        }
        
        if (empty($register["conPass"])) {
            $errors["conPass"] = "Please Confirm Your Password";
        } else if ($register["password"] !== $register["conPass"]) {
            $errors["conPass"] = "Passwords do not match";
        }

        if(empty($register["agreement"])){
            $errors["agreement"] = "You must Agree to the Terms and Conditions";
        }


        if(empty(array_filter($errors))){
            $registerObj->borrowerTypeID = $register["borrowerTypeID"];
            $registerObj->lName = $register["lName"];
            $registerObj->fName = $register["fName"];
            $registerObj->middleIn = $register["middleIn"];
            $registerObj->contactNo = $register["contactNo"];
            $registerObj->email = $register["email"];
            $registerObj->password = $register["password"];

            $registerObj->dateRegistered = date("Y-m-d");

            if($registerObj->addUser()){
                header("location: view.php");
                exit;
            }else {
                echo "FAILED";
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Account</title>
    <link rel="stylesheet" href="../../assets/css/register.css"/>
    <link rel="stylesheet" href="../../assets/css/components/header_footer.css"/>
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
                    <img src="../../assets/images/bg.png" alt="">
                </div>
            </div>
            
            <div class="form-section w-1/2 flex flex-col justify-center items-center">
                <h1 class="font-extrabold">REGISTER YOUR ACCOUNT</h1>
                    <form action="" method="POST">
                        <div class="borrowerType">
                            <label for="borrowerType">Register as? <span>*</span></label>
                            <select name="borrowerTypeID" id="borrowerType" onchange="borrowerType()" class="h-12 w-full rounded-lg">
                                <option value="">--Select--</option>
                                <?php
                                    forEach($borrowerTypes as $type){
                                ?>
                                    <option value="<?= $type["id"]?>" <?= isset($register["borrowerTypeID"])  && $register["borrowerTypeID"] == $type["id"] ? "selected" : "" ?> ><?= $type["typeName"]?></option>
                                <?php
                                    }
                                ?>
                            </select>
                            <p class="errors"><?= $errors["borrowerTypeID"] ?? ""?></p>
                        </div>
                        
                        <div class="input">
                            <label for="lName">Last Name <span>*</span> : </label>
                            <input type="text" class="input-field" name="lName" id="lName" value="<?= $register["lName"] ?? "" ?>">
                            <p class="errors"><?= $errors["lName"] ?? "" ?></p>
                        </div>
                
                        <div class="input">
                            <label for="fName">First Name<span>*</span> : </label>
                            <input type="text" class="input-field" name="fName" id="fName" value="<?= $register["fName"] ?? "" ?>">
                            <p class="errors"><?= $errors["fName"] ?? ""?></p>
                        </div>
                        
                        <div class="input">
                            <label for="middleIn">Middle Initial : </label>
                            <input type="text" class="input-field" name="middleIn" id="middleIn" value="<?= $register["middleIn"] ?? "" ?>">
                            <p class="errors"><?= $errors["middleIn"] ?? ""?></p>
                        </div>
                        <div class="input">
                            <label for="contactNo">Contact Number<span>*</span> : </label>
                            <input type="text" class="input-field" name="contactNo" id="contactNo" value="<?= $register["contactNo"] ?? "" ?>">
                            <p class="errors"><?= $errors["contactNo"] ?? ""?></p>
                        </div>
                
                        <div class="input">
                            <label for="email">Email<span>*</span> : </label>
                            <p id = "emailMessage" >Hello</p>
                            <input type="text" class="input-field" name="email" id="email" value="<?= $register["email"] ?? "" ?>">
                            <p class="errors"><?= $errors["email"] ?? ""?></p>
                        </div>
                
                        <div class="input">
                            <label for="password">Password<span>*</span> : </label>
                            <input type="text" class="input-field" name="password" id="password" value="<?= $register["password"] ?? "" ?>">
                            <p class="errors"><?= $errors["password"] ?? ""?></p>
                        </div>
                
                        <div class="input">
                            <label for="conPass">Confirm Password<span>*</span> : </label>
                            <input type="text" class="input-field" name="conPass" id="conPass" value="<?= $register["conPass"] ?? "" ?>">
                            <p class="errors"><?= $errors["conPass"] ?? ""?></p>
                        </div>
                        
                        <div class="agreement pt-5">
                            <input type="checkbox" name="agreement" id="agreement">
                            <label for="agreement">I agree to the</label>
                            <button type="button" data-modal-target="termsModal" data-modal-toggle="termsModal" id="openModal"class="terms-and-con text-xs text-blue-600 underline">Terms and Conditions</button>
                            <p class="errors"><?= $errors["agreement"] ?? ""?></p>
                        </div>

                        <div class="login py-5 flex justify-center font-bold">
                            <p>Already Have an Account? <span><a href="../../views/borrower/login.php">Log In</a></span></p>
                        </div>
                        <br>
                        <input type="submit" value="Register Account" class="font-bold cursor-pointer mb-8 border-none rounded-lg">
                    </form>
                </div>
            </div>
        </div>
    </main>

    <div class="modal-bg fixed inset-0 hidden bg-black bg-opacity-50 flex justify-center items-center z-50" id="termsModal">
  <div class="modal-content bg-white rounded-lg w-11/12 md:w-2/3 lg:w-1/2 max-h-[80vh] overflow-y-auto p-6 relative shadow-lg">
            <button id="closeModal" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-xl">&times;</button>

            <h2 class="text-xl font-semibold mb-4 p-6 flex text-gray-800 justify-center">TERMS AND CONDITIONS</h2>
            <div class="pb-6">
                <ol class="numbered-list list-decimal pl-5 space-y-4">
                    <li>Acceptance of Terms</li>
                        <p>By registering an account in the Library Book Borrowing System, you agree to comply with and be bound by these Terms and Conditions. If you do not agree with any part of these terms, please do not create an account or use the system.</p>
                    <li>Account Registration</li>
                        <ul>
                            <li>You must provide accurate, complete, and up-to-date information during registration.</li>
                            <li>You agree not to use false information, another person’s identity, or unauthorized credentials.</li>
                            <li>Each user is allowed to create only one account. Duplicate or fraudulent accounts will be removed or suspended.</li>
                        </ul>
                    <li>Use of the System</li>
                        <ul>
                            <li>The system is intended solely for borrowing and returning library books.</li>
                            <li>You agree not to use the system for illegal, fraudulent, or abusive activities.</li>
                            <li>Any attempt to manipulate data, bypass system security, or misuse privileges may result in account suspension or legal action.</li>
                        </ul>
                    <li>Account Responsibility</li>
                        <ul>
                            <li>Any attempt to manipulate data, bypass system security, or misuse privileges may result in account suspension or legal action.</li>
                            <li>If you suspect unauthorized access, you must immediately notify library staff.</li>
                            <li>Any attempt to manipulate data, bypass system security, or misuse privileges may result in account suspension or legal action.</li>
                        </ul>
                    <li>Privacy and Data Protection</li>
                        <ul>
                            <li>The library respects your privacy and protects your personal information in accordance with the Data Privacy Act of 2012 (RA 10173).</li>
                            <li>Information collected (such as name, ID number, email address, and borrowing history) will only be used for official library transactions.</li>
                            <li>Your data will not be shared or disclosed without your consent, except as required by law or internal policy compliance.</li>
                        </ul>
                    <li>Violation and Suspension</li>
                        <ul>
                            <li>Violation of any part of these Terms may result in temporary or permanent suspension of your account.</li>
                            <li>The library reserves the right to take disciplinary or legal action for serious offenses, such as system tampering or misuse of borrowed materials.</li>
                        </ul>
                    <li>Contact Information</li>
                        <ul>
                            <li class="font-bold">For inquiries or assistance regarding your account or these terms, please contact:</li>
                            <li class="font-bold">Library Administrator</li>
                            <li class="list-none"><span class="font-bold">Email: </span>library@wmsu.edu.ph</li>
                            <li class="list-none"><span class="font-bold">Address: </span>WMSU Main Campus Library, Normal Road, Zamboanga City</li>
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
<script src="../assets/js/register.js"></script>
<script src="../assets/js/components/header_footer.js"></script>
</html>