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

            if($registerObj->addUser()){
                header("location: view.php");
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
    <style>
        label {
            display: block;
        }
        span, .errors{
            color: red;
        }
        #emailMessage {
            color: blue;
            display: none; /* Hide initially */
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Register Account</h1>
    <form action="" method="POST">
        <div class="borrowerType">
            <label for="borrowerType">Register as? <span>*</span></label>
            <select name="borrowerTypeID" id="borrowerType" onchange="borrowerType()">
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
        <label for="lName">Last Name <span>*</span> : </label>
        <input type="text" name="lName" id="lName" value="<?= $register["lName"] ?? "" ?>">
        <p class="errors"><?= $errors["lName"] ?? "" ?></p>

        <label for="fName">First Name<span>*</span> : </label>
        <input type="text" name="fName" id="fName" value="<?= $register["fName"] ?? "" ?>">
        <p class="errors"><?= $errors["fName"] ?? ""?></p>
        
        <label for="middleIn">Middle Initial : </label>
        <input type="text" name="middleIn" id="middleIn" value="<?= $register["middleIn"] ?? "" ?>">
        <p class="errors"><?= $errors["middleIn"] ?? ""?></p>
        
        <label for="contactNo">Contact Number<span>*</span> : </label>
        <input type="text" name="contactNo" id="contactNo" value="<?= $register["contactNo"] ?? "" ?>">
        <p class="errors"><?= $errors["contactNo"] ?? ""?></p>

        <label for="email">Email<span>*</span> : </label>
        <p id = "emailMessage" >Hello</p>
        <input type="text" name="email" id="email" value="<?= $register["email"] ?? "" ?>">
        <p class="errors"><?= $errors["email"] ?? ""?></p>

        <label for="password">Password<span>*</span> : </label>
        <input type="text" name="password" id="password" value="<?= $register["password"] ?? "" ?>">
        <p class="errors"><?= $errors["password"] ?? ""?></p>

        <label for="conPass">Confirm Password<span>*</span> : </label>
        <input type="text" name="conPass" id="conPass" value="<?= $register["conPass"] ?? "" ?>">
        <p class="errors"><?= $errors["conPass"] ?? ""?></p>
        
        <br>
        <input type="submit" value="Register Account">
    </form>
</body>
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

    // attach listener and run once on load (handles preselected option)
    select.addEventListener('change', updateEmailMessage);
    updateEmailMessage();
  });
</script>
</html>