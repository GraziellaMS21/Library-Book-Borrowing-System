<?php 
    require_once "../classes/database.php";
    require_once "../classes/register.php";
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
        if ($register["password"] !== $register["conPass"]) {
            $errors["conPass"] = "Passwords do not match";
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
    <link rel="stylesheet" href="../borrowbook/css/addUser.css"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Licorice&display=swap" rel="stylesheet">
</head>
<body class="h-screen">
    <div class="color-layer"></div>
        <nav class="navbar flex justify-between items-center bg-white fixed top-0 left-0 w-full z-10">
            <div class="logo-section flex items-center gap-3">
                <img src="../borrowbook/images/logo.png" alt="Logo" class="logo">
                <h2 class="title">WMSU LIBRARY</h2>
            </div>

            <ul class="nav-links flex gap-8 list-none">
                <li><a href="#">Home</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Services</a></li>
                <li><a href="#">Contact</a></li>
            </ul>

            <div class="burger flex flex-col justify-between cursor-pointer">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <main class="flex justify-center items-center">
        <div class="form-container flex justify-center">
            <div class="info-section w-1/2 flex flex-col justify-center items-center">
                <div class="image">
                    <img src="../borrowbook/images/bg.png" alt="">
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
                        
                        <br>
                        <input type="submit" value="Register Account" class="font-bold cursor-pointer mb-8 border-none rounded-lg">
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer mt-12 text-white">
        <div class="footer-container flex flex-col md:flex-row justify-between items-center gap-4 px-8 py-6">
            <div class="footer-left text-center md:text-left">
                <h3 class="text-xl font-bold tracking-wide">WMSU Library System</h3>
                <p class="line opacity-90">Home of a Wealthy Knowledge</p>
            </div>

            <div class="footer-center flex gap-6 text-sm font-semibold">
                <a href="#">Home</a>
                <a href="#">About</a>
                <a href="#">Contact</a>
            </div>

            <div class="footer-right text-sm text-center md:text-right opacity-90">
                <p>©2025 WMSU Library. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

</body>
<script>
  const burger = document.querySelector('.burger');
  const navLinks = document.querySelector('.nav-links');

  burger.addEventListener('click', () => {
    burger.classList.toggle('active');
    navLinks.classList.toggle('active');
  });
</script>
<script>
     document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('borrowerType');
    const emailMsg = document.getElementById('emailMessage');

    function updateEmailMessage() {
      const val = select.value;
      if (val === '1' || val === '2') {
        emailMsg.textContent = 'Use Your WMSU Email Address';
        emailMsg.style.display = 'block';
      } else if (val === '3') {
        emailMsg.textContent = 'Use Your Personal Email Address';
        emailMsg.style.display = 'block';
      } else {
        emailMsg.textContent = '';
        emailMsg.style.display = 'none';
      }
    }

    select.addEventListener('change', updateEmailMessage);
    updateEmailMessage();
  });
</script>
</html>