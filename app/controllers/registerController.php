<?php 
    session_start();
    require_once(__DIR__ . "/../models/userRegister.php");
    $registerObj = new Register();

    $register = [];
    $errors = [];
    $userTypes = $registerObj->fetchUserType();
    
    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $register["userTypeID"] = trim(htmlspecialchars($_POST["userTypeID"]));
        $register["lName"] = trim(htmlspecialchars($_POST["lName"]));
        $register["fName"] = trim(htmlspecialchars($_POST["fName"]));
        $register["middleIn"] = trim(htmlspecialchars($_POST["middleIn"]));
        $register["college"] = trim(htmlspecialchars($_POST["college"]));
        $register["department"] = trim(htmlspecialchars($_POST["department"]));
        $register["position"] = trim(htmlspecialchars($_POST["position"]));
        $register["contact_no"] = trim(htmlspecialchars($_POST["contact_no"]));
        $register["email"] = trim(htmlspecialchars($_POST["email"]));
        $register["password"] = trim(htmlspecialchars($_POST["password"]));
        $register["conPass"] = trim(htmlspecialchars($_POST["conPass"]));
        $register["agreement"] = isset($_POST["agreement"]) ? trim(htmlspecialchars($_POST["agreement"])) : "";



         if (empty($register["userTypeID"])) {
            $errors["userTypeID"] = "Please Choose From the Following";
        }


        if (empty($register["lName"])) {
            $errors["lName"] = "Last Name is required";
        }

        if (empty($register["fName"])) {
            $errors["fName"] = "First Name is required";
        }

         if(($register["userTypeID"] == 1)){
             if (empty($register["college"])) {
                 $errors["college"] = "College is required";
             }
         }

         if(($register["userTypeID"] == 2)){
             if (empty($register["position"])) {
                 $errors["position"] = "Position is required";
             }
         }

        if (empty($register["contact_no"])) {
            $errors["contact_no"] = "Contact Number is required";
        }elseif(!is_numeric($register["contact_no"]) || strlen($register["contact_no"])!=11){
            $errors["contact_no"] = "Contact Number Format is Invalid";
        }

        if (empty($register["email"])) {
            $errors["email"] = "Email is required";
        } else if ($registerObj->isEmailExist($register["email"])){
            $errors["email"] = "Email already exist";
            
        }
        
        if(filter_var($register["email"], FILTER_VALIDATE_EMAIL)){
            $domain = substr(strrchr($register["email"], "@"), 1);

            if(($register["userTypeID"] == 1 || $register["userTypeID"] == 2) && $domain !== "wmsu.edu.ph"){
                $errors["email"] = "Please use your WMSU email address";
            } else if (($register["userTypeID"] == 3) && $domain !== "gmail.com"){
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
            $registerObj->userTypeID = $register["userTypeID"];
            $registerObj->lName = $register["lName"];
            $registerObj->fName = $register["fName"];
            if(empty($register["middleIn"])){
                $registerObj->middleIn = NULL;
            } else {
                $registerObj->middleIn = $register["middleIn"];
            }
            if(empty($register["college"])){
                $registerObj->college = NULL;
            }else{
                $registerObj->college = $register["college"];
            }
            if(empty($register["department"])){
                $registerObj->department = NULL;
            } else{
                $registerObj->department = $register["department"];
            }
            if(empty($register["position"])){
                $registerObj->position = NULL;
            } else {
                $registerObj->position = $register["position"];
            }
            $registerObj->contact_no = $register["contact_no"];
            $registerObj->email = $register["email"];
            $registerObj->password = $register["password"];

            $registerObj->date_registered = date("Y-m-d");

            if($registerObj->addUser($register["position"])){
                 header("Location: ../../app/views/borrower/register.php");
                exit;
            }else {
                echo "FAILED";
            }
        }
        $_SESSION["errors"] = $errors;
        $_SESSION["old"] = $register;
        header("Location: ../../app/views/borrower/register.php");
        exit;
    }
?>