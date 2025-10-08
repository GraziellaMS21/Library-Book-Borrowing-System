<?php 
    require_once "../config/database.php";
    require_once "../classes/userRegister.php";
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
    <link rel="stylesheet" href="../public/css/addUser.css"/>
    <link rel="stylesheet" href="../public/css/components/navbar_and_footer.css"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Licorice&display=swap" rel="stylesheet">
</head>
<body class="h-screen">
    <div class="color-layer"></div>
    <?php require_once '../includes/header.php'; ?>

    <main class="flex justify-center items-center">
        <div class="form-container flex justify-center">
            <div class="info-section w-1/2 flex flex-col justify-center items-center">
                <div class="image">
                    <img src="../public/images/bg.png" alt="">
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
                            <p>Already Have an Account? <span>Log In</span></p>
                        </div>
                        <br>
                        <input type="submit" value="Register Account" class="font-bold cursor-pointer mb-8 border-none rounded-lg">
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php require_once '../includes/footer.php'; ?>
</body>
<script src="../public/js/register.js"></script>
</html>